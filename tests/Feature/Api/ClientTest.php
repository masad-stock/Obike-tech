<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view-clients');
        
        // Create a token for the user
        $this->token = $this->user->createToken('testing')->plainTextToken;
    }

    public function test_can_get_clients_list()
    {
        // Create some clients
        Client::factory()->count(3)->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/clients');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'company_email',
                        'phone',
                        'status',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_can_get_single_client()
    {
        // Create a client
        $client = Client::factory()->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/clients/' . $client->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'company_email',
                'phone',
                'status',
                'address',
                'city',
                'state',
                'postal_code',
                'country',
            ]);
    }

    public function test_authorized_user_can_create_client()
    {
        // Give user permission to manage clients
        $this->user->givePermissionTo('manage-clients');
        
        $clientData = [
            'name' => 'New Test Client',
            'company_email' => 'test@example.com',
            'phone' => '1234567890',
            'status' => 'active',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'postal_code' => '12345',
            'country' => 'Test Country',
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/clients', $clientData);
        
        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New Test Client',
                'company_email' => 'test@example.com',
            ]);
    }

    public function test_unauthorized_user_cannot_create_client()
    {
        $clientData = [
            'name' => 'New Test Client',
            'company_email' => 'test@example.com',
            'phone' => '1234567890',
            'status' => 'active',
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/clients', $clientData);
        
        $response->assertStatus(403);
    }
}