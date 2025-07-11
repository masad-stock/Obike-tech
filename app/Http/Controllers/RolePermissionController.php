<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-roles');
    }

    public function roles()
    {
        $roles = Role::withCount('users', 'permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function createRole()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('-', $permission->name)[0];
        });
        
        return view('roles.create', compact('permissions'));
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            $role = Role::create(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions']);
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create role: ' . $e->getMessage()]);
        }
    }

    public function editRole(Role $role)
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('-', $permission->name)[0];
        });
        
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions']);
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }

    public function destroyRole(Role $role)
    {
        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete role with assigned users. Remove users from this role first.']);
        }
        
        $role->delete();
        
        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully');
    }

    public function permissions()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('-', $permission->name)[0];
        });
        
        return view('permissions.index', compact('permissions'));
    }

    public function createPermission()
    {
        return view('permissions.create');
    }

    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);
        
        Permission::create(['name' => $validated['name']]);
        
        return redirect()->route('permissions.index')
            ->with('success', 'Permission created successfully');
    }

    public function editPermission(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);
        
        $permission->update(['name' => $validated['name']]);
        
        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully');
    }

    public function destroyPermission(Permission $permission)
    {
        // Check if permission is used by roles
        if ($permission->roles()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete permission that is assigned to roles. Remove from roles first.']);
        }
        
        $permission->delete();
        
        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully');
    }

    public function assignUsers(Role $role)
    {
        $users = \App\Models\User::all();
        $roleUsers = $role->users->pluck('id')->toArray();
        
        return view('roles.assign_users', compact('role', 'users', 'roleUsers'));
    }

    public function updateAssignedUsers(Request $request, Role $role)
    {
        $validated = $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Get all users
            $users = \App\Models\User::whereIn('id', $validated['users'])->get();
            
            // Remove role from all users
            \App\Models\User::role($role->name)->each(function ($user) use ($role) {
                $user->removeRole($role);
            });
            
            // Assign role to selected users
            foreach ($users as $user) {
                $user->assignRole($role);
            }
            
            DB::commit();
            
            return redirect()->route('roles.index')
                ->with('success', 'Users assigned to role successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to assign users: ' . $e->getMessage()]);
        }
    }
}