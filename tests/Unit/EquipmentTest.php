<?php

namespace Tests\Unit;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_belongs_to_category()
    {
        // Create a category
        $category = EquipmentCategory::factory()->create();
        
        // Create equipment with that category
        $equipment = Equipment::factory()->create([
            'equipment_category_id' => $category->id
        ]);
        
        // Assert the relationship works correctly
        $this->assertEquals($category->id, $equipment->category->id);
        $this->assertInstanceOf(EquipmentCategory::class, $equipment->category);
    }

    public function test_equipment_has_correct_casts()
    {
        $equipment = new Equipment();
        
        $this->assertEquals('date', $equipment->getCasts()['purchase_date']);
        $this->assertEquals('date', $equipment->getCasts()['warranty_expiry']);
        $this->assertEquals('decimal:2', $equipment->getCasts()['purchase_cost']);
    }

    public function test_equipment_has_maintenance_schedules_relationship()
    {
        $equipment = Equipment::factory()->create();
        
        $this->assertIsObject($equipment->maintenanceSchedules());
    }

    public function test_equipment_has_maintenance_logs_relationship()
    {
        $equipment = Equipment::factory()->create();
        
        $this->assertIsObject($equipment->maintenanceLogs());
    }

    public function test_equipment_has_assignments_relationship()
    {
        $equipment = Equipment::factory()->create();
        
        $this->assertIsObject($equipment->assignments());
    }
}