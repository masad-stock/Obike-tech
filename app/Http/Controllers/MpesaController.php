<?php

namespace App\Http\Controllers;

use App\Models\RentalAgreement;
use App\Models\RentalPayment;
use App\Models\MpesaTransaction;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->middleware('auth')->except(['callback', 'timeout']);
        $this->middleware('permission:manage-payments')->except(['callback', 'timeout']);
        $this->mpesaService = $mpesaService;
    }

    /**
     * Show M-PESA payment form
     */
    public function showPaymentForm(RentalAgreement $agreement)
    {
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        return view('payments.mpesa-form', compact('agreement', 'remainingBalance'));
    }

    /**
     * Initiate M-PESA STK Push
     */
    public function initiatePayment(Request $request, RentalAgreement $agreement)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'phone_number' => 'required|regex:/^254[0-9]{9}$/', // Format: 254XXXXXXXXX
        ]);
        
        // Calculate remaining balance
        $totalPaid = $agreement->payments->sum('amount');
        $remainingBalance = $agreement->total_amount - $totalPaid;
        
        // Ensure payment doesn't exceed remaining balance
        if ($validated['amount'] > $remainingBalance) {
            return back()->withErrors(['amount' => 'Payment amount exceeds the remaining balance.']);
        }
        
        // Reference for the transaction
        $reference = "RA-{$agreement->id}";
        
        // Description for the transaction
        $description = "Payment for Rental Agreement #{$agreement->id}";
        
        // Initiate STK Push
        $response = $this->mpesaService->stkPush(
            $validated['phone_number'],
            $validated['amount'],
            $reference,
            $description
        );
        
        if ($response['success']) {
            // Store transaction details in the database
            $transaction = MpesaTransaction::create([
                'checkout_request_id' => $response['data']['CheckoutRequestID'],
                'merchant_request_id' => $response['data']['MerchantRequestID'],
                'amount' => $validated['amount'],
                'phone_number' => $validated['phone_number'],
                'reference' => $reference,
                'description' => $description,
                'status' => 'pending',
                'user_id' => Auth::id(),
                'rental_agreement_id' => $agreement->id,
            ]);
            
            return redirect()->route('mpesa.status', $transaction)
                ->with('success', 'M-PESA payment initiated. Please check your phone to complete the transaction.');
        } else {
            return back()->withErrors(['mpesa_error' => $response['message']]);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(MpesaTransaction $transaction)
    {
        // If transaction is already completed or failed, just show the status
        if (in_array($transaction->status, ['completed', 'failed'])) {
            return view('payments.mpesa-status', compact('transaction'));
        }
        
        // Check status from M-PESA
        $response = $this->mpesaService->checkTransactionStatus($transaction->checkout_request_id);
        
        if ($response['success']) {
            $resultCode = $response['data']['ResultCode'] ?? null;
            
            // Update transaction status based on result code
            if ($resultCode === 0) {
                $transaction->update([
                    'status' => 'completed',
                    'result_code' => $resultCode,
                    'result_description' => $response['data']['ResultDesc'] ?? null,
                ]);
                
                // Create payment record
                RentalPayment::create([
                    'rental_agreement_id' => $transaction->rental_agreement_id,
                    'amount' => $transaction->amount,
                    'payment_date' => now(),
                    'payment_method' => 'mpesa',
                    'notes' => "M-PESA Transaction: {$transaction->checkout_request_id}",
                    'recorded_by' => $transaction->user_id,
                    'transaction_id' => $transaction->checkout_request_id,
                ]);
            } elseif ($resultCode !== null) {
                $transaction->update([
                    'status' => 'failed',
                    'result_code' => $resultCode,
                    'result_description' => $response['data']['ResultDesc'] ?? null,
                ]);
            }
        }
        
        return view('payments.mpesa-status', compact('transaction'));
    }

    /**
     * M-PESA callback endpoint
     */
    public function callback(Request $request)
    {
        Log::info('M-PESA callback received', $request->all());
        
        $callbackData = $request->json()->all();
        
        // Extract the necessary data
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? null;
        $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
        $resultDesc = $callbackData['Body']['stkCallback']['ResultDesc'] ?? null;
        
        if ($checkoutRequestId) {
            // Find the transaction
            $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();
            
            if ($transaction) {
                // Update transaction status
                if ($resultCode === 0) {
                    $transaction->update([
                        'status' => 'completed',
                        'result_code' => $resultCode,
                        'result_description' => $resultDesc,
                    ]);
                    
                    // Create payment record if not already created
                    if (!RentalPayment::where('transaction_id', $checkoutRequestId)->exists()) {
                        RentalPayment::create([
                            'rental_agreement_id' => $transaction->rental_agreement_id,
                            'amount' => $transaction->amount,
                            'payment_date' => now(),
                            'payment_method' => 'mpesa',
                            'notes' => "M-PESA Transaction: {$transaction->checkout_request_id}",
                            'recorded_by' => $transaction->user_id,
                            'transaction_id' => $transaction->checkout_request_id,
                        ]);
                    }
                } else {
                    $transaction->update([
                        'status' => 'failed',
                        'result_code' => $resultCode,
                        'result_description' => $resultDesc,
                    ]);
                }
            }
        }
        
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * M-PESA timeout endpoint
     */
    public function timeout(Request $request)
    {
        Log::info('M-PESA timeout received', $request->all());
        
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}