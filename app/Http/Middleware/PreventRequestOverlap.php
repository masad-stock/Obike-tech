<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestOverlap
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to specific routes or methods that need protection
        if ($this->shouldPreventOverlap($request)) {
            // Generate a unique key for this request
            $key = $this->getLockKey($request);
            
            // Use Redis for distributed locking if available
            if (extension_loaded('redis') && config('database.redis.client') !== 'predis') {
                return Redis::funnel($key)
                    ->limit(1)
                    ->block(5)
                    ->then(
                        fn () => $next($request),
                        fn () => response()->json(['error' => 'Too many concurrent requests'], 429)
                    );
            }
            
            // Fallback to file-based locking
            $lockFile = storage_path('framework/cache/locks/' . md5($key));
            $directory = dirname($lockFile);
            
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            $handle = fopen($lockFile, 'c+');
            
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                fclose($handle);
                return response()->json(['error' => 'Too many concurrent requests'], 429);
            }
            
            try {
                $response = $next($request);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
            
            return $response;
        }
        
        return $next($request);
    }
    
    /**
     * Determine if the request should be protected from overlapping.
     */
    protected function shouldPreventOverlap(Request $request): bool
    {
        // Only apply to POST/PUT/PATCH requests to specific endpoints
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }
        
        // List of endpoints that should be protected
        $protectedEndpoints = [
            'api/rentals/agreements',
            'api/procurement/orders',
            'api/mechanical/maintenance',
            'api/projects/tasks',
        ];
        
        foreach ($protectedEndpoints as $endpoint) {
            if ($request->is($endpoint) || $request->is($endpoint . '/*')) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get a unique lock key for the request.
     */
    protected function getLockKey(Request $request): string
    {
        // For authenticated requests, include the user ID
        $userId = $request->user() ? $request->user()->id : 'guest';
        
        // For resource operations, include the resource ID
        $resourceId = $request->route('id') ?? 
                     $request->route('taskId') ?? 
                     $request->route('projectId') ?? 
                     'none';
        
        return 'request:' . $request->method() . ':' . $request->path() . ':' . $userId . ':' . $resourceId;
    }
}