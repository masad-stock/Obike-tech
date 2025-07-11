<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

class CacheService
{
    /**
     * Default cache duration in seconds
     */
    protected int $defaultDuration = 3600; // 1 hour

    /**
     * Cache prefix for all keys
     */
    protected string $prefix = 'app_cache:';

    /**
     * Cache driver to use
     */
    protected string $store;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Use Octane cache if available, otherwise use the default cache
        $this->store = extension_loaded('swoole') && config('octane.server') === 'swoole' 
            ? 'octane' 
            : config('cache.default');
    }

    /**
     * Get an item from the cache, or store the default value
     *
     * @param string $key
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $key, $ttl, \Closure $callback)
    {
        $cacheKey = $this->prefix . $key;
        
        try {
            return Cache::store($this->store)->remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            // If caching fails, just execute the callback
            return $callback();
        }
    }

    /**
     * Get an item from the cache, or store the default value forever
     *
     * @param string $key
     * @param \Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, \Closure $callback)
    {
        $cacheKey = $this->prefix . $key;
        
        try {
            return Cache::store($this->store)->rememberForever($cacheKey, $callback);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            // If caching fails, just execute the callback
            return $callback();
        }
    }

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @return bool
     */
    public function put(string $key, $value, $ttl = null)
    {
        $cacheKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultDuration;
        
        try {
            return Cache::store($this->store)->put($cacheKey, $value, $ttl);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            return false;
        }
    }

    /**
     * Retrieve an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->prefix . $key;
        
        try {
            return Cache::store($this->store)->get($cacheKey, $default);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            return $default;
        }
    }

    /**
     * Remove an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key)
    {
        $cacheKey = $this->prefix . $key;
        
        try {
            return Cache::store($this->store)->forget($cacheKey);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            return false;
        }
    }

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        $cacheKey = $this->prefix . $key;
        
        try {
            return Cache::store($this->store)->has($cacheKey);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'exception' => $e
            ]);
            
            return false;
        }
    }

    /**
     * Cache a model by its ID
     *
     * @param string $modelClass
     * @param int|string $id
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @return Model|null
     */
    public function rememberModel(string $modelClass, $id, $ttl = null)
    {
        $key = strtolower(class_basename($modelClass)) . ':' . $id;
        $ttl = $ttl ?? $this->defaultDuration;
        
        return $this->remember($key, $ttl, function () use ($modelClass, $id) {
            return $modelClass::find($id);
        });
    }

    /**
     * Cache a collection of models
     *
     * @param string $key
     * @param \Closure $query
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @return Collection
     */
    public function rememberCollection(string $key, \Closure $query, $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultDuration;
        
        return $this->remember($key, $ttl, $query);
    }

    /**
     * Cache a paginated result
     *
     * @param string $key
     * @param \Closure $query
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function rememberPagination(string $key, \Closure $query, $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultDuration;
        
        return $this->remember($key, $ttl, $query);
    }

    /**
     * Cache system configuration
     *
     * @param int|\DateTimeInterface|\DateInterval|null $ttl
     * @return array
     */
    public function cacheSystemConfig($ttl = null)
    {
        $ttl = $ttl ?? 86400; // 24 hours by default for system config
        
        return $this->remember('system_config', $ttl, function () {
            return \App\Models\SystemConfig::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get a cached system configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSystemConfig(string $key, $default = null)
    {
        $config = $this->cacheSystemConfig();
        
        return $config[$key] ?? $default;
    }

    /**
     * Clear all application cache
     *
     * @return bool
     */
    public function flush()
    {
        try {
            return Cache::store($this->store)->flush();
        } catch (\Exception $e) {
            Log::error('Cache flush error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            return false;
        }
    }

    /**
     * Set up interval-based caches (for Octane)
     *
     * @return void
     */
    public function setupIntervals()
    {
        if (extension_loaded('swoole') && config('octane.server') === 'swoole') {
            // Cache frequently accessed statistics
            Cache::store('octane')->interval('app_stats', function () {
                return [
                    'users_count' => \App\Models\User::count(),
                    'active_clients' => \App\Models\Client::where('status', 'active')->count(),
                    'equipment_count' => \App\Models\Equipment::count(),
                    'projects_count' => \App\Models\Project::count(),
                ];
            }, seconds: 60);
            
            // Cache system configuration
            Cache::store('octane')->interval('system_config', function () {
                return \App\Models\SystemConfig::pluck('value', 'key')->toArray();
            }, seconds: 300);
            
            // Cache equipment categories
            Cache::store('octane')->interval('equipment_categories', function () {
                return \App\Models\EquipmentCategory::all();
            }, seconds: 3600);
        }
    }
}