<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\CacheService;
use App\Services\ConcurrencyService;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    protected $concurrencyService;
    protected $cacheService;
    
    public function __construct(ConcurrencyService $concurrencyService, CacheService $cacheService)
    {
        $this->concurrencyService = $concurrencyService;
        $this->cacheService = $cacheService;
    }
    
    public function dashboard()
    {
        // Use cache for dashboard data
        $recentPurchaseOrders = $this->cacheService->remember('procurement:recent_orders', 300, function () {
            return PurchaseOrder::with('supplier', 'requestedBy')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        });
        
        $counts = $this->cacheService->remember('procurement:counts', 600, function () {
            return [
                'pending' => PurchaseOrder::where('status', 'pending')->count(),
                'approved' => PurchaseOrder::where('status', 'approved')->count(),
                'completed' => PurchaseOrder::where('status', 'completed')->count(),
            ];
        });
        
        // For more complex or time-sensitive data, use concurrent operations
        [$monthlySpending, $topSuppliers] = $this->concurrencyService->runConcurrently([
            function () {
                return $this->cacheService->remember('procurement:monthly_spending', 3600, function () {
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
            },
            
            function () {
                return $this->cacheService->remember('procurement:top_suppliers', 3600, function () {
                    return PurchaseOrder::with('supplier')
                        ->where('status', 'completed')
                        ->whereDate('completed_at', '>=', now()->subMonths(3))
                        ->selectRaw('supplier_id, SUM(total_amount) as total')
                        ->groupBy('supplier_id')
                        ->orderByDesc('total')
                        ->limit(5)
                        ->get();
                });
            }
        ]);
        
        return view('procurement.dashboard', compact(
            'recentPurchaseOrders',
            'counts',
            'monthlySpending',
            'topSuppliers'
        ));
    }
}




