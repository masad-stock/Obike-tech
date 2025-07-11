<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\CacheService;
use Illuminate\Http\Request;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ClientCollection;

class ClientController extends Controller
{
    protected $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('permission:view-clients')->only(['index', 'show']);
        $this->middleware('permission:manage-clients')->only(['store', 'update', 'destroy']);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);
        
        // Generate a cache key based on the request parameters
        $cacheKey = 'clients:list:' . ($status ?? 'all') . ':' . $perPage . ':' . $request->input('page', 1);
        
        // Cache the paginated results
        $clients = $this->cacheService->rememberPagination($cacheKey, function () use ($status, $perPage) {
            $query = Client::query();
            
            if ($status) {
                $query->where('status', $status);
            }
            
            return $query->orderBy('name')->paginate($perPage);
        }, 600); // Cache for 10 minutes
        
        return new ClientCollection($clients);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'status' => 'required|in:active,inactive,potential',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $client = Client::create($validated);
        
        // Clear the clients list cache
        $this->cacheService->forget('clients:list:all');
        $this->cacheService->forget('clients:list:' . $client->status);
        
        return new ClientResource($client);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Use the cache service to remember the client
        $client = $this->cacheService->rememberModel(Client::class, $id, 1800); // Cache for 30 minutes
        
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        
        return new ClientResource($client);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_email' => 'sometimes|required|email|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'status' => 'sometimes|required|in:active,inactive,potential',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);
        
        $oldStatus = $client->status;
        $client->update($validated);
        
        // Clear the specific client cache
        $this->cacheService->forget('client:' . $id);
        
        // Clear the clients list cache
        $this->cacheService->forget('clients:list:all');
        $this->cacheService->forget('clients:list:' . $oldStatus);
        if ($oldStatus !== $client->status) {
            $this->cacheService->forget('clients:list:' . $client->status);
        }
        
        return new ClientResource($client);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        $status = $client->status;
        
        $client->delete();
        
        // Clear the specific client cache
        $this->cacheService->forget('client:' . $id);
        
        // Clear the clients list cache
        $this->cacheService->forget('clients:list:all');
        $this->cacheService->forget('clients:list:' . $status);
        
        return response()->json(['message' => 'Client deleted successfully']);
    }
}