<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view-reports');
        
        // Log in the user
        $this->actingAs($this->user);
    }

    public function test_authorized_user_can_access_reports_page()
    {
        $response = $this->get('/reports');
        
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_reports_page()
    {
        // Create a user without permissions
        $unauthorizedUser = User::factory()->create();
        
        // Log in the unauthorized user
        $this->actingAs($unauthorizedUser);
        
        $response = $this->get('/reports');
        
        $response->assertStatus(403);
    }

    public function test_can_generate_equipment_report()
    {
        $response = $this->post('/reports/equipment', [
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'category_id' => 'all',
            'format' => 'pdf',
        ]);
        
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_validates_report_parameters()
    {
        $response = $this->post('/reports/equipment', [
            // Missing required parameters
        ]);
        
        $response->assertStatus(302) // Redirects back with errors
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }
}