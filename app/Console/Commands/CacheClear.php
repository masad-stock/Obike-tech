<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-clear {type? : Type of cache to clear (all, config, models, views)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application cache with more granular control';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService)
    {
        $type = $this->argument('type') ?? 'all';
        
        switch ($type) {
            case 'all':
                $this->clearAllCache($cacheService);
                break;
                
            case 'config':
                $this->clearConfigCache($cacheService);
                break;
                
            case 'models':
                $this->clearModelCache($cacheService);
                break;
                
            case 'views':
                $this->clearViewCache();
                break;
                
            default:
                $this->error("Unknown cache type: {$type}");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Clear all application cache
     */
    protected function clearAllCache(CacheService $cacheService)
    {
        $this->info('Clearing all application cache...');
        
        $cacheService->flush();
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        
        $this->info('All cache cleared successfully!');
    }
    
    /**
     * Clear configuration cache
     */
    protected function clearConfigCache(CacheService $cacheService)
    {
        $this->info('Clearing configuration cache...');
        
        $cacheService->forget('system_config');
        $this->call('config:clear');
        
        $this->info('Configuration cache cleared successfully!');
    }
    
    /**
     * Clear model cache
     */
    protected function clearModelCache(CacheService $cacheService)
    {
        $this->info('Clearing model cache...');
        
        // Clear dashboard statistics
        $cacheService->forget('dashboard:statistics');
        $cacheService->forget('dashboard:recent_users');
        $cacheService->forget('dashboard:system_health');
        
        // Clear procurement cache
        $cacheService->forget('procurement:recent_orders');
        $cacheService->forget('procurement:counts');
        $cacheService->forget('procurement:monthly_spending');
        $cacheService->forget('procurement:top_suppliers');
        
        // Clear client cache
        $cacheService->forget('clients:list:all');
        $cacheService->forget('clients:list:active');
        $cacheService->forget('clients:list:inactive');
        $cacheService->forget('clients:list:potential');
        
        $this->info('Model cache cleared successfully!');
    }
    
    /**
     * Clear view cache
     */
    protected function clearViewCache()
    {
        $this->info('Clearing view cache...');
        
        $this->call('view:clear');
        
        $this->info('View cache cleared successfully!');
    }
}