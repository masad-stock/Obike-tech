<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'model_number', 'serial_number', 'equipment_category_id',
        'purchase_date', 'purchase_cost', 'manufacturer', 'supplier',
        'warranty_expiry', 'status', 'specifications', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id');
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function assignments()
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->assignments()->where('status', 'assigned')->latest()->first();
    }
}