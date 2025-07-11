<?php

namespace App\Http\Controllers;

use App\Models\RentalAgreement;
use App\Models\RentalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-payments');
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    public function showPaymentForm(RentalAgreement $agreement)
    {
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        // Get Stripe intent
        $intent = Auth::user()->createSetupIntent();
        
        return view('payments.form', compact('agreement', 'remainingBalance', 'intent'));
    }

    public function processPayment(Request $request, RentalAgreement $agreement)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);
        
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        // Ensure payment doesn't exceed remaining balance
        if ($validated['amount'] > $remainingBalance) {
            return back()->withErrors(['amount' => 'Payment amount exceeds the remaining balance.']);
        }
        
        try {
            // Convert to cents for Stripe
            $amountInCents = (int)($validated['amount'] * 100);
            
            // Process payment with Stripe
            $payment = Auth::user()->charge(
                $amountInCents,
                $validated['payment_method'],
                [
                    'description' => "Payment for Rental Agreement #{$agreement->id}",
                    'metadata' => [
                        'agreement_id' => $agreement->id,
                        'customer_name' => $agreement->customer->name,
                    ],
                ]
            );
            
            // Create payment record
            RentalPayment::create([
                'rental_agreement_id' => $agreement->id,
                'amount' => $validated['amount'],
                'payment_date' => now(),
                'payment_method' => 'credit_card',
                'notes' => "Stripe Payment ID: {$payment->id}",
                'recorded_by' => Auth::id(),
                'transaction_id' => $payment->id,
            ]);
            
            return redirect()->route('rentals.agreements.show', $agreement)
                ->with('success', 'Payment processed successfully.');
                
        } catch (ApiErrorException $e) {
            return back()->withErrors(['stripe_error' => $e->getMessage()]);
        }
    }
    
    public function paymentHistory()
    {
        $payments = RentalPayment::with(['rentalAgreement', 'rentalAgreement.customer'])
            ->orderBy('payment_date', 'desc')
            ->paginate(15);
            
        return view('payments.history', compact('payments'));
    }
}