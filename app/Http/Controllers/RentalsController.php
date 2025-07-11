<?php

namespace App\Http\Controllers;

use App\Models\RentalItem;
use App\Models\RentalCustomer;
use App\Models\RentalAgreement;
use App\Models\RentalAgreementItem;
use App\Models\RentalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Pennant\Feature;

class RentalsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-rentals')->only(['index', 'showAgreement']);
        $this->middleware('permission:manage-rentals')->only(['createAgreement', 'updateAgreement', 'recordPayment']);
    }

    public function index()
    {
        $activeAgreements = RentalAgreement::where('status', 'active')->count();
        $overdueAgreements = RentalAgreement::where('status', 'active')
            ->where('expected_end_date', '<', now())
            ->count();
        $availableItems = RentalItem::where('status', 'available')->count();
        
        $agreements = RentalAgreement::with('customer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('rentals.index', compact(
            'agreements', 
            'activeAgreements', 
            'overdueAgreements', 
            'availableItems'
        ));
    }

    public function createAgreement(Request $request)
    {
        $customers = RentalCustomer::where('status', 'active')->get();
        $availableItems = RentalItem::where('status', 'available')->get();
        
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'customer_id' => 'required|exists:rental_customers,id',
                'agreement_number' => 'required|string|max:50|unique:rental_agreements',
                'start_date' => 'required|date',
                'expected_end_date' => 'required|date|after:start_date',
                'deposit_amount' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.rental_item_id' => 'required|exists:rental_items,id',
                'items.*.daily_rate' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string',
                'terms_conditions' => 'nullable|string',
            ]);
            
            try {
                DB::beginTransaction();
                
                // Calculate total amount
                $totalAmount = 0;
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['expected_end_date']);
                $days = $startDate->diffInDays($endDate) + 1;
                
                foreach ($validated['items'] as $item) {
                    $totalAmount += $item['daily_rate'] * $item['quantity'] * $days;
                }
                
                // Create rental agreement
                $agreement = RentalAgreement::create([
                    'customer_id' => $validated['customer_id'],
                    'agreement_number' => $validated['agreement_number'],
                    'start_date' => $validated['start_date'],
                    'expected_end_date' => $validated['expected_end_date'],
                    'deposit_amount' => $validated['deposit_amount'] ?? 0,
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                    'payment_status' => 'pending',
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                ]);
                
                // Add items to agreement
                foreach ($validated['items'] as $item) {
                    $itemTotal = $item['daily_rate'] * $item['quantity'] * $days;
                    
                    $agreement->items()->create([
                        'rental_item_id' => $item['rental_item_id'],
                        'daily_rate' => $item['daily_rate'],
                        'quantity' => $item['quantity'],
                        'total_amount' => $itemTotal,
                    ]);
                }
                
                DB::commit();
                
                return redirect()->route('rentals.agreements.show', $agreement)
                    ->with('success', 'Rental agreement created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to create rental agreement: ' . $e->getMessage()]);
            }
        }
        
        return view('rentals.agreements.create', compact('customers', 'availableItems'));
    }

    public function showAgreement(RentalAgreement $agreement)
    {
        $agreement->load('customer', 'items.rentalItem', 'payments', 'createdBy');
        
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        // Calculate days remaining or overdue
        $today = Carbon::today();
        $endDate = Carbon::parse($agreement->expected_end_date);
        $daysRemaining = $today->diffInDays($endDate, false);
        $isOverdue = $daysRemaining < 0 && $agreement->status === 'active';
        
        return view('rentals.agreements.show', compact(
            'agreement',
            'totalPaid',
            'remainingBalance',
            'daysRemaining',
            'isOverdue'
        ));
    }

    public function updateAgreement(Request $request, RentalAgreement $agreement)
    {
        $validated = $request->validate([
            'expected_end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
        
        // If completing or cancelling the agreement, return all items
        if (($validated['status'] ?? null) === 'completed' || ($validated['status'] ?? null) === 'cancelled') {
            foreach ($agreement->items as $item) {
                $rentalItem = $item->rentalItem;
                $rentalItem->available_quantity += $item->quantity;
                if ($rentalItem->available_quantity > 0) {
                    $rentalItem->status = 'available';
                }
                $rentalItem->save();
            }
            
            // Set actual end date
            $validated['actual_end_date'] = now();
        }
        
        $agreement->update($validated);
        
        return redirect()->route('rentals.agreements.show', $agreement)
            ->with('success', 'Rental agreement updated successfully.');
    }

    public function activateAgreement(RentalAgreement $agreement)
    {
        if ($agreement->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft agreements can be activated.']);
        }

        $agreement->update(['status' => 'active']);
        
        // Update all items to rented status if not already
        foreach ($agreement->items as $item) {
            RentalItem::where('id', $item->rental_item_id)
                ->update(['status' => 'rented']);
        }

        return redirect()->route('rentals.agreements.show', $agreement)
            ->with('success', 'Rental agreement activated successfully.');
    }

    public function recordPayment(Request $request, RentalAgreement $agreement)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit_card,bank_transfer,check',
            'notes' => 'nullable|string',
        ]);
        
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        // Ensure payment doesn't exceed remaining balance
        if ($validated['amount'] > $remainingBalance) {
            return back()->withErrors(['amount' => 'Payment amount exceeds the remaining balance.']);
        }
        
        // Create payment record
        RentalPayment::create([
            'rental_agreement_id' => $agreement->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'],
            'recorded_by' => Auth::id(),
        ]);
        
        return redirect()->route('rentals.agreements.show', $agreement)
            ->with('success', 'Payment recorded successfully.');
    }

    public function returnItems(Request $request, RentalAgreement $agreement)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:rental_agreement_items,id',
            'items.*.is_returned' => 'required|boolean',
            'items.*.return_date' => 'required_if:items.*.is_returned,1|date',
            'items.*.condition_in' => 'nullable|string',
            'items.*.damage_charges' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['items'] as $itemData) {
                $agreementItem = RentalAgreementItem::find($itemData['id']);
                
                if ($agreementItem && $agreementItem->rental_agreement_id === $agreement->id) {
                    $updateData = [
                        'is_returned' => $itemData['is_returned'],
                    ];
                    
                    if ($itemData['is_returned']) {
                        $updateData['return_date'] = $itemData['return_date'];
                        $updateData['condition_in'] = $itemData['condition_in'] ?? null;
                        $updateData['damage_charges'] = $itemData['damage_charges'] ?? 0;
                        
                        // Update rental item status back to available
                        RentalItem::where('id', $agreementItem->rental_item_id)
                            ->update(['status' => 'available']);
                    }
                    
                    $agreementItem->update($updateData);
                }
            }

            // Check if all items are returned to complete the agreement
            $allReturned = $agreement->items()->where('is_returned', false)->count() === 0;
            
            if ($allReturned) {
                $agreement->update([
                    'status' => 'completed',
                    'actual_end_date' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('rentals.agreements.show', $agreement)
                ->with('success', 'Items return status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update return status: ' . $e->getMessage()]);
        }
    }

    public function manageRentalItems()
    {
        $items = RentalItem::orderBy('name')->paginate(15);
        return view('rentals.items.index', compact('items'));
    }

    public function createRentalItem(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'daily_rate' => 'required|numeric|min:0',
                'weekly_rate' => 'nullable|numeric|min:0',
                'monthly_rate' => 'nullable|numeric|min:0',
                'replacement_cost' => 'nullable|numeric|min:0',
                'condition_notes' => 'nullable|string',
            ]);

            $validated['status'] = 'available';

            RentalItem::create($validated);

            return redirect()->route('rentals.items.index')
                ->with('success', 'Rental item created successfully.');
        }

        return view('rentals.items.create');
    }

    public function updateRentalItem(Request $request, RentalItem $item)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'daily_rate' => 'required|numeric|min:0',
                'weekly_rate' => 'nullable|numeric|min:0',
                'monthly_rate' => 'nullable|numeric|min:0',
                'replacement_cost' => 'nullable|numeric|min:0',
                'status' => 'required|in:available,rented,maintenance,retired',
                'condition_notes' => 'nullable|string',
            ]);

            $item->update($validated);

            return redirect()->route('rentals.items.show', $item)
                ->with('success', 'Rental item updated successfully.');
        }

        return view('rentals.items.edit', compact('item'));
    }

    public function showRentalItem(RentalItem $item)
    {
        $item->load('agreements.agreement');
        
        // Get rental history
        $rentalHistory = $item->agreements()
            ->with('agreement.customer')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('rentals.items.show', compact('item', 'rentalHistory'));
    }

    public function customers()
    {
        $customers = RentalCustomer::orderBy('name')->paginate(15);
        return view('rentals.customers.index', compact('customers'));
    }

    public function createCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'id_type' => 'required|in:national_id,passport,drivers_license',
            'id_number' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);
        
        $customer = RentalCustomer::create($validated);
        
        return redirect()->route('rentals.customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function showCustomer(RentalCustomer $customer)
    {
        $customer->load('agreements');
        
        // Calculate statistics
        $totalAgreements = $customer->agreements->count();
        $activeAgreements = $customer->agreements->where('status', 'active')->count();
        $totalSpent = $customer->agreements->sum('total_amount');
        
        return view('rentals.customers.show', compact(
            'customer',
            'totalAgreements',
            'activeAgreements',
            'totalSpent'
        ));
    }

    public function updateCustomer(Request $request, RentalCustomer $customer)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'email' => 'required|email|unique:rental_customers,email,' . $customer->id,
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'id_type' => 'required|string|max:50',
                'id_number' => 'required|string|max:50',
                'status' => 'required|in:active,inactive',
                'notes' => 'nullable|string',
            ]);

            $customer->update($validated);

            return redirect()->route('rentals.customers.show', $customer)
                ->with('success', 'Customer updated successfully');
        }

        return view('rentals.customers.edit', compact('customer'));
    }

    public function dashboard()
    {
        // Get active rental agreements
        $activeAgreements = RentalAgreement::where('status', 'active')->count();
        
        // Get overdue rental agreements
        $overdueAgreements = RentalAgreement::where('status', 'active')
            ->where('expected_end_date', '<', now())
            ->count();
        
        // Get available items
        $availableItems = RentalItem::where('status', 'available')->count();
        $totalItems = RentalItem::count();
        
        // Basic data for all users
        $viewData = [
            'activeAgreements' => $activeAgreements,
            'overdueAgreements' => $overdueAgreements,
            'availableItems' => $availableItems,
            'totalItems' => $totalItems,
        ];
        
        // Enhanced dashboard features for selected users
        if (Feature::active('enhanced-rental-dashboard')) {
            // Get recent agreements
            $recentAgreements = RentalAgreement::with('customer')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($agreement) {
                    return [
                        'id' => $agreement->id,
                        'customer_name' => $agreement->customer->name,
                        'start_date' => $agreement->start_date->format('Y-m-d'),
                        'expected_end_date' => $agreement->expected_end_date->format('Y-m-d'),
                        'status' => $agreement->status,
                        'total_amount' => $agreement->total_amount
                    ];
                });
            
            // Get upcoming returns
            $upcomingReturns = RentalAgreement::with(['customer', 'items'])
                ->where('status', 'active')
                ->orderBy('expected_end_date')
                ->limit(5)
                ->get()
                ->map(function ($agreement) {
                    return [
                        'id' => $agreement->id,
                        'customer_name' => $agreement->customer->name,
                        'expected_end_date' => $agreement->expected_end_date->format('Y-m-d'),
                        'items_count' => $agreement->items->count(),
                        'total_amount' => $agreement->total_amount
                    ];
                });
            
            // Get monthly revenue for current year
            $monthlyRevenue = RentalPayment::selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
                ->whereYear('payment_date', date('Y'))
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();
            
            // Add enhanced data to view
            $viewData = array_merge($viewData, [
                'recentAgreements' => $recentAgreements,
                'upcomingReturns' => $upcomingReturns,
                'monthlyRevenue' => $monthlyRevenue,
                'enhancedDashboard' => true,
            ]);
        }
        
        return view('rentals.dashboard', $viewData);
    }

    public function rentalAgreements()
    {
        $agreements = RentalAgreement::with('customer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($agreement) {
                return [
                    'id' => $agreement->id,
                    'customer_name' => $agreement->customer->name,
                    'start_date' => $agreement->start_date->format('Y-m-d'),
                    'end_date' => $agreement->end_date->format('Y-m-d'),
                    'status' => $agreement->status,
                    'total_amount' => $agreement->total_amount
                ];
            });
        
        return view('rentals.agreements.index', compact('agreements'));
    }

    public function rentalItems()
    {
        $items = RentalItem::orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'serial_number' => $item->serial_number,
                    'category' => $item->category ?? 'Uncategorized',
                    'daily_rate' => $item->daily_rate,
                    'status' => $item->status,
                    'image' => $item->image_path ? asset('storage/' . $item->image_path) : null
                ];
            });
        
        return view('rentals.items.index', compact('items'));
    }

    public function generateAgreementPdf(RentalAgreement $agreement)
    {
        $agreement->load('customer', 'items.rentalItem', 'payments', 'createdBy');
        
        // Generate PDF using a package like barryvdh/laravel-dompdf
        // This is a placeholder for the actual implementation
        
        return view('rentals.agreements.pdf', compact('agreement'));
    }
}





