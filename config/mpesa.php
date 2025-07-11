<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-PESA Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the environment to use for M-PESA API calls.
    | When set to true, the sandbox environment will be used.
    |
    */
    'sandbox' => env('MPESA_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | M-PESA API Credentials
    |--------------------------------------------------------------------------
    |
    | These are the credentials provided by Safaricom for accessing the M-PESA API.
    |
    */
    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
    'passkey' => env('MPESA_PASSKEY', ''),
    'shortcode' => env('MPESA_SHORTCODE', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    |
    | These are the URLs that M-PESA will use to send transaction results.
    |
    */
    'callback_url' => env('MPESA_CALLBACK_URL', ''),
    'timeout_url' => env('MPESA_TIMEOUT_URL', ''),
    'result_url' => env('MPESA_RESULT_URL', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Transaction Type
    |--------------------------------------------------------------------------
    |
    | This is the transaction type to be used in the M-PESA API calls.
    | CustomerPayBillOnline - For paybill transactions
    | CustomerBuyGoodsOnline - For till transactions
    |
    */
    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
    
    /*
    |--------------------------------------------------------------------------
    | STK Push Settings
    |--------------------------------------------------------------------------
    |
    | These are the settings for STK Push transactions.
    |
    */
    'stk_push_timeout' => env('MPESA_STK_PUSH_TIMEOUT', 60), // seconds
    
    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | These are the settings for storing M-PESA transactions in the database.
    |
    */
    'store_transactions' => env('MPESA_STORE_TRANSACTIONS', true),
];