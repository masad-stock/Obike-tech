<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    
    public function index()
    {
        // Get dashboard statistics from cache
        $statistics = $this->cacheService->remember('dashboard:statistics', 600, function () {
            return [
                // User statistics
                'totalUsers' => User::count(),
                'activeUsers' => User::where('status', 'active')->count(),
                'inactiveUsers' => User::where('status', 'inactive')->count(),
                
                // Department statistics
                'departments' => Department::withCount('users')->get(),
                
                // System statistics
                'totalClients' => Client::count(),
                'totalSuppliers' => Supplier::count(),
                'totalEquipment' => Equipment::count(),
            ];
        });
        
        // Recent users - shorter cache time as this changes more frequently
        $recentUsers = $this->cacheService->remember('dashboard:recent_users', 300, function () {
            return User::orderBy('created_at', 'desc')->limit(5)->get();
        });
        
        // System health - very short cache time as this should be relatively fresh
        $systemHealth = $this->cacheService->remember('dashboard:system_health', 60, function () {
            // This would typically come from a monitoring service
            return [
                'cpu_usage' => '25%',
                'memory_usage' => '40%',
                'disk_usage' => '60%',
                'last_backup' => now()->subDays(1)->format('Y-m-d H:i:s'),
            ];
        });
        
        return view('dashboard.index', [
            'totalUsers' => $statistics['totalUsers'],
            'activeUsers' => $statistics['activeUsers'],
            'inactiveUsers' => $statistics['inactiveUsers'],
            'departments' => $statistics['departments'],
            'totalClients' => $statistics['totalClients'],
            'totalSuppliers' => $statistics['totalSuppliers'],
            'totalEquipment' => $statistics['totalEquipment'],
            'recentUsers' => $recentUsers,
            'systemHealth' => $systemHealth,
        ]);
    }
}



