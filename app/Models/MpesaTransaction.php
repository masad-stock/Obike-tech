<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'merchant_request_id',
        'amount',
        'phone_number',
        'reference',
        'description',
        'status',
        'result_code',
        'result_description',
        'user_id',
        'rental_agreement_id',
    ];

    /**
     * Get the user that initiated the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rental agreement associated with the transaction.
     */
    public function rentalAgreement()
    {
        return $this->belongsTo(RentalAgreement::class);
    }
}