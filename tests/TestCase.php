<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions that might be needed in tests
        $this->createPermissions();
    }
    
    /**
     * Create common permissions used in tests.
     *
     * @return void
     */
    protected function createPermissions(): void
    {
        // Only create permissions if they don't exist
        if (Permission::where('name', 'view-reports')->count() === 0) {
            Permission::create(['name' => 'view-reports']);
        }
        
        if (Permission::where('name', 'view-equipment')->count() === 0) {
            Permission::create(['name' => 'view-equipment']);
        }
        
        if (Permission::where('name', 'manage-equipment')->count() === 0) {
            Permission::create(['name' => 'manage-equipment']);
        }
        
        if (Permission::where('name', 'view-clients')->count() === 0) {
            Permission::create(['name' => 'view-clients']);
        }
        
        if (Permission::where('name', 'manage-clients')->count() === 0) {
            Permission::create(['name' => 'manage-clients']);
        }
        
        if (Permission::where('name', 'view-projects')->count() === 0) {
            Permission::create(['name' => 'view-projects']);
        }
        
        if (Permission::where('name', 'manage-projects')->count() === 0) {
            Permission::create(['name' => 'manage-projects']);
        }
        
        // Create admin role if it doesn't exist
        if (Role::where('name', 'admin')->count() === 0) {
            $adminRole = Role::create(['name' => 'admin']);
            $adminRole->givePermissionTo(Permission::all());
        }
    }
}