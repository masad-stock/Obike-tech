<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MpesaService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $shortcode;
    protected $callbackUrl;
    protected $accessToken;

    public function __construct()
    {
        // Set environment based on config
        $this->baseUrl = config('mpesa.sandbox') 
            ? 'https://sandbox.safaricom.co.ke' 
            : 'https://api.safaricom.co.ke';
            
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->passkey = config('mpesa.passkey');
        $this->shortcode = config('mpesa.shortcode');
        $this->callbackUrl = config('mpesa.callback_url');
        
        // Get access token on initialization
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get M-PESA API access token
     */
    protected function getAccessToken()
    {
        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
                
            if ($response->successful()) {
                return $response->json()['access_token'];
            } else {
                Log::error('M-PESA access token error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('M-PESA access token exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate STK Push transaction
     * 
     * @param string $phoneNumber Customer phone number (format: 254XXXXXXXXX)
     * @param float $amount Amount to be charged
     * @param string $reference Payment reference
     * @param string $description Transaction description
     * @return array Response from M-PESA API
     */
    public function stkPush($phoneNumber, $amount, $reference, $description)
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Could not get access token'
            ];
        }

        try {
            // Format timestamp
            $timestamp = Carbon::now()->format('YmdHis');
            
            // Generate password
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            
            // Prepare request
            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => round($amount),
                    'PartyA' => $phoneNumber,
                    'PartyB' => $this->shortcode,
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => $this->callbackUrl,
                    'AccountReference' => $reference,
                    'TransactionDesc' => $description
                ]);
                
            if ($response->successful()) {
                $result = $response->json();
                
                // Store the checkout request ID for later verification
                if (isset($result['CheckoutRequestID'])) {
                    // You might want to store this in the database
                    // along with the transaction details
                }
                
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                Log::error('M-PESA STK push error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'STK push failed: ' . $response->json()['errorMessage'] ?? $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('M-PESA STK push exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'STK push exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check transaction status
     * 
     * @param string $checkoutRequestId The CheckoutRequestID from STK push
     * @return array Response from M-PESA API
     */
    public function checkTransactionStatus($checkoutRequestId)
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Could not get access token'
            ];
        }

        try {
            // Format timestamp
            $timestamp = Carbon::now()->format('YmdHis');
            
            // Generate password
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            
            // Prepare request
            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId
                ]);
                
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::error('M-PESA transaction status error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Transaction status check failed: ' . $response->json()['errorMessage'] ?? $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('M-PESA transaction status exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Transaction status exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process C2B transaction
     * 
     * @param string $phoneNumber Customer phone number (format: 254XXXXXXXXX)
     * @param float $amount Amount to be charged
     * @param string $reference Payment reference
     * @return array Response from M-PESA API
     */
    public function c2b($phoneNumber, $amount, $reference)
    {
        if (!$this->accessToken) {
            return [
                'success' => false,
                'message' => 'Could not get access token'
            ];
        }

        try {
            // Prepare request
            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . '/mpesa/c2b/v1/simulate', [
                    'ShortCode' => $this->shortcode,
                    'CommandID' => 'CustomerPayBillOnline',
                    'Amount' => round($amount),
                    'Msisdn' => $phoneNumber,
                    'BillRefNumber' => $reference
                ]);
                
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                Log::error('M-PESA C2B error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'C2B transaction failed: ' . $response->json()['errorMessage'] ?? $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('M-PESA C2B exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'C2B exception: ' . $e->getMessage()
            ];
        }
    }
}