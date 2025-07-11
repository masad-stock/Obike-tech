<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date_of_birth', 'national_id', 'tax_id',
        'emergency_contact_name', 'emergency_contact_phone',
        'hire_date', 'termination_date', 'bank_name', 'bank_account_number'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}