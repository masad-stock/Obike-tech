<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-inventory')->only(['index', 'show']);
        $this->middleware('permission:manage-inventory')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:delete-inventory')->only(['destroy']);
    }

    public function index()
    {
        $totalItems = InventoryItem::count();
        $lowStockItems = InventoryItem::where('quantity', '<=', DB::raw('reorder_level'))->count();
        
        $items = InventoryItem::with('category')
            ->orderBy('name')
            ->paginate(15);
            
        return view('inventory.index', compact('items', 'totalItems', 'lowStockItems'));
    }

    public function create()
    {
        $categories = InventoryCategory::orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        
        return view('inventory.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:inventory_items',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'nullable|string|max:20',
            'quantity' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ]);
        
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory-images');
        }
        
        $validated['created_by'] = Auth::id();
        
        DB::beginTransaction();
        
        try {
            $item = InventoryItem::create($validated);
            
            // Record initial inventory transaction
            if ($validated['quantity'] > 0) {
                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'transaction_type' => 'initial',
                    'quantity' => $validated['quantity'],
                    'notes' => 'Initial inventory setup',
                    'performed_by' => Auth::id(),
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('inventory.show', $item)
                ->with('success', 'Inventory item created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create inventory item: ' . $e->getMessage()]);
        }
    }

    public function show(InventoryItem $item)
    {
        $item->load('category', 'supplier', 'transactions.performedBy');
        
        // Get recent transactions
        $recentTransactions = $item->transactions()
            ->with('performedBy')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return view('inventory.show', compact('item', 'recentTransactions'));
    }

    public function edit(InventoryItem $item)
    {
        $categories = InventoryCategory::orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        
        return view('inventory.edit', compact('item', 'categories', 'suppliers'));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:inventory_items,sku,' . $item->id,
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'nullable|string|max:20',
            'reorder_level' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ]);
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($item->image_path) {
                Storage::delete($item->image_path);
            }
            
            $validated['image_path'] = $request->file('image')->store('inventory-images');
        }
        
        $item->update($validated);
        
        return redirect()->route('inventory.show', $item)
            ->with('success', 'Inventory item updated successfully');
    }

    public function destroy(InventoryItem $item)
    {
        // Check if item has transactions
        if ($item->transactions()->count() > 1) { // More than just the initial transaction
            return back()->withErrors(['error' => 'Cannot delete item with transaction history. Consider marking it as inactive instead.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Delete related records
            $item->transactions()->delete();
            
            // Delete image if exists
            if ($item->image_path) {
                Storage::delete($item->image_path);
            }
            
            // Delete item
            $item->delete();
            
            DB::commit();
            
            return redirect()->route('inventory.index')
                ->with('success', 'Inventory item deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete inventory item: ' . $e->getMessage()]);
        }
    }

    public function adjustStock(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:add,remove',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $transactionType = $validated['adjustment_type'] === 'add' ? 'stock_in' : 'stock_out';
        $quantity = $validated['quantity'];
        
        // Calculate new quantity
        $newQuantity = $validated['adjustment_type'] === 'add' 
            ? $item->quantity + $quantity 
            : $item->quantity - $quantity;
            
        // Prevent negative stock
        if ($newQuantity < 0) {
            return back()->withErrors(['error' => 'Cannot remove more than available quantity.']);
        }
        
        DB::beginTransaction();
        
        try {
            // Update item quantity
            $item->update(['quantity' => $newQuantity]);
            
            // Record transaction
            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'transaction_type' => $transactionType,
                'quantity' => $quantity,
                'notes' => $validated['reason'] . ($validated['notes'] ? ': ' . $validated['notes'] : ''),
                'performed_by' => Auth::id(),
            ]);
            
            DB::commit();
            
            return redirect()->route('inventory.show', $item)
                ->with('success', 'Stock adjusted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to adjust stock: ' . $e->getMessage()]);
        }
    }

    public function categories()
    {
        $categories = InventoryCategory::withCount('items')->paginate(15);
        return view('inventory.categories.index', compact('categories'));
    }

    public function createCategory()
    {
        return view('inventory.categories.create');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:inventory_categories',
            'description' => 'nullable|string',
        ]);
        
        InventoryCategory::create($validated);
        
        return redirect()->route('inventory.categories')
            ->with('success', 'Category created successfully');
    }

    public function editCategory(InventoryCategory $category)
    {
        return view('inventory.categories.edit', compact('category'));
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:inventory_categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
        
        $category->update($validated);
        
        return redirect()->route('inventory.categories')
            ->with('success', 'Category updated successfully');
    }

    public function lowStock()
    {
        $items = InventoryItem::where('quantity', '<=', DB::raw('reorder_level'))
            ->with('category', 'supplier')
            ->orderBy('quantity')
            ->paginate(15);
            
        return view('inventory.low_stock', compact('items'));
    }

    public function transactions()
    {
        $transactions = InventoryTransaction::with('item', 'performedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('inventory.transactions', compact('transactions'));
    }
}