<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\PurchaseOrder;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheWarmup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-warmup {--type=all : Type of cache to warm up (all, config, models)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache for better performance';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService)
    {
        $type = $this->option('type');
        
        $this->info('Starting cache warmup...');
        
        switch ($type) {
            case 'all':
                $this->warmupConfigCache($cacheService);
                $this->warmupModelCache($cacheService);
                break;
                
            case 'config':
                $this->warmupConfigCache($cacheService);
                break;
                
            case 'models':
                $this->warmupModelCache($cacheService);
                break;
                
            default:
                $this->error("Unknown cache type: {$type}");
                return 1;
        }
        
        $this->info('Cache warmup completed successfully!');
        
        return 0;
    }
    
    /**
     * Warm up configuration cache
     */
    protected function warmupConfigCache(CacheService $cacheService)
    {
        $this->info('Warming up configuration cache...');
        
        // Cache system configuration
        $cacheService->cacheSystemConfig();
        
        // Cache Laravel configuration
        $this->call('config:cache');
        
        // Cache routes
        $this->call('route:cache');
        
        $this->info('Configuration cache warmed up!');
    }
    
    /**
     * Warm up model cache
     */
    protected function warmupModelCache(CacheService $cacheService)
    {
        $this->info('Warming up model cache...');
        
        // Dashboard statistics
        $cacheService->remember('dashboard:statistics', 600, function () {
            return [
                'totalUsers' => User::count(),
                'activeUsers' => User::where('status', 'active')->count(),
                'inactiveUsers' => User::where('status', 'inactive')->count(),
                'totalClients' => Client::count(),
                'totalEquipment' => Equipment::count(),
            ];
        });
        
        // Recent users
        $cacheService->remember('dashboard:recent_users', 300, function () {
            return User::orderBy('created_at', 'desc')->limit(5)->get();
        });
        
        // Equipment categories
        $cacheService->remember('equipment_categories', 3600, function () {
            return EquipmentCategory::all();
        });
        
        // Client lists
        $cacheService->remember('clients:list:all', 600, function () {
            return Client::orderBy('name')->paginate(15);
        });
        
        $cacheService->remember('clients:list:active', 600, function () {
            return Client::where('status', 'active')->orderBy('name')->paginate(15);
        });
        
        // Procurement data
        $cacheService->remember('procurement:counts', 600, function () {
            return [
                'pending' => PurchaseOrder::where('status', 'pending')->count(),
                'approved' => PurchaseOrder::where('status', 'approved')->count(),
                'completed' => PurchaseOrder::where('status', 'completed')->count(),
            ];
        });
        
        // Monthly spending for procurement dashboard
        $cacheService->remember('procurement:monthly_spending', 3600, function () {
            // Calculate monthly spending for the last 6 months
            $result = [];
            for ($i = 0; $i < 6; $i++) {
                $month = now()->subMonths($i);
                $result[$month->format('M Y')] = PurchaseOrder::where('status', 'completed')
                    ->whereYear('completed_at', $month->year)
                    ->whereMonth('completed_at', $month->month)
                    ->sum('total_amount');
            }
            return $result;
        });
        
        // Top suppliers for procurement dashboard
        $cacheService->remember('procurement:top_suppliers', 3600, function () {
            return PurchaseOrder::with('supplier')
                ->where('status', 'completed')
                ->whereDate('completed_at', '>=', now()->subMonths(3))
                ->selectRaw('supplier_id, SUM(total_amount) as total')
                ->groupBy('supplier_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        });
        
        $this->info('Model cache warmed up!');
    }
}

