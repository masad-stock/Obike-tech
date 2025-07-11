<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-suppliers')->only(['index', 'show']);
        $this->middleware('permission:create-suppliers')->only(['create', 'store']);
        $this->middleware('permission:edit-suppliers')->only(['edit', 'update']);
        $this->middleware('permission:delete-suppliers')->only(['destroy']);
    }

    public function index()
    {
        $activeSuppliers = Supplier::where('status', 'active')->count();
        $inactiveSuppliers = Supplier::where('status', 'inactive')->count();
        
        $suppliers = Supplier::withCount('purchaseOrders')
            ->orderBy('name')
            ->paginate(15);
            
        return view('suppliers.index', compact(
            'suppliers', 
            'activeSuppliers', 
            'inactiveSuppliers'
        ));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'logo' => 'nullable|image|max:2048', // 2MB max
        ]);
        
        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('supplier-logos');
        }
        
        // Add created_by field
        $validated['created_by'] = Auth::id();
        
        $supplier = Supplier::create($validated);
        
        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier created successfully');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('contacts', 'purchaseOrders');
        
        // Get active purchase orders count
        $activePurchaseOrders = $supplier->purchaseOrders->whereIn('status', ['pending', 'approved'])->count();
        
        // Calculate total purchase amount
        $totalPurchaseAmount = $supplier->purchaseOrders->where('status', 'completed')->sum('total_amount');
        
        // Get recent purchase orders
        $recentPurchaseOrders = $supplier->purchaseOrders->sortByDesc('created_at')->take(5);
        
        return view('suppliers.show', compact(
            'supplier', 
            'activePurchaseOrders', 
            'totalPurchaseAmount', 
            'recentPurchaseOrders'
        ));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'logo' => 'nullable|image|max:2048', // 2MB max
        ]);
        
        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($supplier->logo_path) {
                Storage::delete($supplier->logo_path);
            }
            
            $validated['logo_path'] = $request->file('logo')->store('supplier-logos');
        }
        
        $supplier->update($validated);
        
        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        // Check if supplier has active purchase orders
        $activePurchaseOrders = $supplier->purchaseOrders()->whereIn('status', ['pending', 'approved'])->count();
        
        if ($activePurchaseOrders > 0) {
            return back()->withErrors(['error' => 'Cannot delete supplier with active purchase orders. Please complete or cancel all purchase orders first.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete related records
            $supplier->contacts()->delete();
            
            // Delete logo if exists
            if ($supplier->logo_path) {
                Storage::delete($supplier->logo_path);
            }
            
            // Delete supplier
            $supplier->delete();
            
            DB::commit();
            
            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete supplier: ' . $e->getMessage()]);
        }
    }

    public function addContact(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        // If this is marked as primary, unmark other primary contacts
        if ($validated['is_primary'] ?? false) {
            $supplier->contacts()->where('is_primary', true)->update(['is_primary' => false]);
        }
        
        $supplier->contacts()->create($validated);
        
        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Contact added successfully');
    }

    public function updateContact(Request $request, Supplier $supplier, SupplierContact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        // If this is marked as primary, unmark other primary contacts
        if ($validated['is_primary'] ?? false) {
            $supplier->contacts()->where('id', '!=', $contact->id)->where('is_primary', true)->update(['is_primary' => false]);
        }
        
        $contact->update($validated);
        
        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Contact updated successfully');
    }

    public function deleteContact(Supplier $supplier, SupplierContact $contact)
    {
        $contact->delete();
        
        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Contact deleted successfully');
    }

    public function supplierPurchaseOrders(Supplier $supplier)
    {
        $purchaseOrders = $supplier->purchaseOrders()
            ->with('requestedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('suppliers.purchase_orders', compact('supplier', 'purchaseOrders'));
    }

    public function exportSuppliers()
    {
        $suppliers = Supplier::orderBy('name')->get();
        
        $csvFileName = 'suppliers_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        ];
        
        $handle = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($handle, [
            'Name', 'Contact Person', 'Email', 'Phone', 'Address', 
            'City', 'State', 'Postal Code', 'Country', 'Website',
            'Tax Number', 'Payment Terms', 'Status'
        ]);
        
        // Add supplier data
        foreach ($suppliers as $supplier) {
            fputcsv($handle, [
                $supplier->name,
                $supplier->contact_person,
                $supplier->email,
                $supplier->phone,
                $supplier->address,
                $supplier->city,
                $supplier->state,
                $supplier->postal_code,
                $supplier->country,
                $supplier->website,
                $supplier->tax_number,
                $supplier->payment_terms,
                $supplier->status
            ]);
        }
        
        fclose($handle);
        
        return response()->stream(function() {
            // This is handled by Laravel
        }, 200, $headers);
    }

    public function importSuppliers(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);
        
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        $importCount = 0;
        $errorCount = 0;
        
        DB::beginTransaction();
        
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Validate required fields
                if (empty($row[0])) { // Name is required
                    $errorCount++;
                    continue;
                }
                
                Supplier::create([
                    'name' => $row[0],
                    'contact_person' => $row[1] ?? null,
                    'email' => $row[2] ?? null,
                    'phone' => $row[3] ?? null,
                    'address' => $row[4] ?? null,
                    'city' => $row[5] ?? null,
                    'state' => $row[6] ?? null,
                    'postal_code' => $row[7] ?? null,
                    'country' => $row[8] ?? null,
                    'website' => $row[9] ?? null,
                    'tax_number' => $row[10] ?? null,
                    'payment_terms' => $row[11] ?? null,
                    'status' => $row[12] ?? 'active',
                    'created_by' => Auth::id(),
                ]);
                
                $importCount++;
            }
            
            DB::commit();
            
            return redirect()->route('suppliers.index')
                ->with('success', "Import completed: $importCount suppliers imported, $errorCount errors");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to import suppliers: ' . $e->getMessage()]);
        } finally {
            fclose($handle);
        }
    }
}

