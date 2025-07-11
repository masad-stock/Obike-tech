<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view-users')->only(['index', 'show']);
        $this->middleware('permission:manage-users')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:delete-users')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles', 'department')->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos');
        }
        
        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'position' => $validated['position'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'profile_photo_path' => $validated['profile_photo_path'] ?? null,
            'status' => $validated['status'],
        ]);
        
        // Assign roles
        $user->syncRoles($validated['roles']);
        
        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $user->load('roles', 'department', 'projects', 'tasks');
        
        // Get recent activity
        $recentActivity = collect(); // This would be populated from an activity log
        
        return view('users.show', compact('user', 'recentActivity'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $departments = Department::all();
        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::defaults()],
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }
            
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos');
        }
        
        // Update user
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'position' => $validated['position'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'status' => $validated['status'],
        ]);
        
        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        // Update profile photo if provided
        if (isset($validated['profile_photo_path'])) {
            $user->update(['profile_photo_path' => $validated['profile_photo_path']]);
        }
        
        // Sync roles
        $user->syncRoles($validated['roles']);
        
        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }
        
        // Check if user has related records
        $hasRelatedRecords = $user->projects()->exists() || 
                            $user->tasks()->exists() || 
                            $user->purchaseOrders()->exists();
        
        if ($hasRelatedRecords) {
            return back()->withErrors(['error' => 'This user has related records. Consider deactivating instead of deleting.']);
        }
        
        // Delete profile photo if exists
        if ($user->profile_photo_path) {
            Storage::delete($user->profile_photo_path);
        }
        
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('users.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
        ]);
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }
            
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos');
        }
        
        // Update user
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
        ]);
        
        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }
        
        // Update profile photo if provided
        if (isset($validated['profile_photo_path'])) {
            $user->update(['profile_photo_path' => $validated['profile_photo_path']]);
        }
        
        return redirect()->route('profile')
            ->with('success', 'Profile updated successfully');
    }
}

