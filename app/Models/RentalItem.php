<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'model', 'serial_number',
        'daily_rate', 'weekly_rate', 'monthly_rate',
        'replacement_cost', 'status', 'condition_notes'
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
    ];

    public function rentalAgreementItems()
    {
        return $this->hasMany(RentalAgreementItem::class);
    }

    public function activeRentals()
    {
        return $this->rentalAgreementItems()
            ->whereHas('rentalAgreement', function ($query) {
                $query->where('status', 'active');
            })
            ->where('is_returned', false);
    }

    public function isAvailable()
    {
        return $this->status === 'available' && $this->activeRentals()->count() === 0;
    }
}