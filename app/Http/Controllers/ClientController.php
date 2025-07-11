<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-clients')->only(['index', 'show']);
        $this->middleware('permission:create-clients')->only(['create', 'store']);
        $this->middleware('permission:edit-clients')->only(['edit', 'update']);
        $this->middleware('permission:delete-clients')->only(['destroy']);
        $this->middleware('permission:manage-client-contacts')->only(['addContact', 'updateContact', 'deleteContact']);
    }

    public function index()
    {
        $activeClients = Client::where('status', 'active')->count();
        $inactiveClients = Client::where('status', 'inactive')->count();
        $potentialClients = Client::where('status', 'potential')->count();
        
        $clients = Client::withCount('projects')
            ->orderBy('name')
            ->paginate(15);
            
        return view('clients.index', compact(
            'clients', 
            'activeClients', 
            'inactiveClients', 
            'potentialClients'
        ));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,potential',
            'logo' => 'nullable|image|max:2048', // 2MB max
        ]);
        
        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('client-logos');
        }
        
        // Add created_by field
        $validated['created_by'] = Auth::id();
        
        $client = Client::create($validated);
        
        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully');
    }

    public function show(Client $client)
    {
        $client->load('contacts', 'projects');
        
        // Get active projects count
        $activeProjects = $client->projects->where('status', 'active')->count();
        
        // Calculate total project value
        $totalProjectValue = $client->projects->sum('budget');
        
        // Get recent projects
        $recentProjects = $client->projects->sortByDesc('created_at')->take(5);
        
        return view('clients.show', compact(
            'client', 
            'activeProjects', 
            'totalProjectValue', 
            'recentProjects'
        ));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,potential',
            'logo' => 'nullable|image|max:2048', // 2MB max
        ]);
        
        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($client->logo_path) {
                Storage::delete($client->logo_path);
            }
            
            $validated['logo_path'] = $request->file('logo')->store('client-logos');
        }
        
        $client->update($validated);
        
        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully');
    }

    public function destroy(Client $client)
    {
        // Check if client has active projects
        $activeProjects = $client->projects()->where('status', 'active')->count();
        
        if ($activeProjects > 0) {
            return back()->withErrors(['error' => 'Cannot delete client with active projects. Please complete or reassign all projects first.']);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete related records
            $client->contacts()->delete();
            
            // Delete logo if exists
            if ($client->logo_path) {
                Storage::delete($client->logo_path);
            }
            
            // Delete client
            $client->delete();
            
            DB::commit();
            
            return redirect()->route('clients.index')
                ->with('success', 'Client deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete client: ' . $e->getMessage()]);
        }
    }

    public function addContact(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        // If this is marked as primary, unmark other primary contacts
        if ($validated['is_primary'] ?? false) {
            $client->contacts()->where('is_primary', true)->update(['is_primary' => false]);
        }
        
        $client->contacts()->create($validated);
        
        return redirect()->route('clients.show', $client)
            ->with('success', 'Contact added successfully');
    }

    public function updateContact(Request $request, Client $client, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        
        // If this is marked as primary, unmark other primary contacts
        if ($validated['is_primary'] ?? false) {
            $client->contacts()->where('id', '!=', $contact->id)->where('is_primary', true)->update(['is_primary' => false]);
        }
        
        $contact->update($validated);
        
        return redirect()->route('clients.show', $client)
            ->with('success', 'Contact updated successfully');
    }

    public function deleteContact(Client $client, Contact $contact)
    {
        $contact->delete();
        
        return redirect()->route('clients.show', $client)
            ->with('success', 'Contact deleted successfully');
    }

    public function clientProjects(Client $client)
    {
        $projects = $client->projects()->with('manager')->paginate(10);
        
        return view('clients.projects', compact('client', 'projects'));
    }

    public function clientInvoices(Client $client)
    {
        $invoices = $client->invoices()->orderBy('created_at', 'desc')->paginate(10);
        
        // Calculate total paid and outstanding amounts
        $totalPaid = $client->invoices()->where('status', 'paid')->sum('amount');
        $totalOutstanding = $client->invoices()->whereIn('status', ['pending', 'overdue'])->sum('amount');
        
        return view('clients.invoices', compact('client', 'invoices', 'totalPaid', 'totalOutstanding'));
    }
}