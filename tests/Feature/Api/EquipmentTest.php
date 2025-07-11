<?php

namespace Tests\Feature\Api;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view-equipment');
        
        // Create a token for the user
        $this->token = $this->user->createToken('testing')->plainTextToken;
    }

    public function test_can_get_equipment_list()
    {
        // Create some equipment
        Equipment::factory()->count(3)->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/mechanical/equipment');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'serial_number',
                        'category',
                        'status',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_get_single_equipment()
    {
        // Create equipment
        $equipment = Equipment::factory()->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/mechanical/equipment/' . $equipment->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'model_number',
                'serial_number',
                'category',
                'purchase_date',
                'purchase_cost',
                'status',
            ]);
    }

    public function test_returns_404_for_nonexistent_equipment()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/mechanical/equipment/999');
        
        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_access_equipment()
    {
        // Create a user without permissions
        $unauthorizedUser = User::factory()->create();
        $token = $unauthorizedUser->createToken('testing')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/mechanical/equipment');
        
        $response->assertStatus(403);
    }
}