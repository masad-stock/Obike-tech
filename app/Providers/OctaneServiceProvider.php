<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Laravel\Octane\Facades\Octane;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;

class OctaneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Reset singletons on each request to prevent state leakage
        Octane::whenRequestReceived(function (RequestReceived $event) {
            // Reset any global state that might persist between requests
        });

        // Clean up after each request
        Octane::whenRequestTerminated(function (RequestTerminated $event) {
            // Clean up any resources that were used during the request
        });

        // Initialize the Octane cache when the worker starts
        Octane::whenWorkerStarting(function (WorkerStarting $event) {
            // Register the Octane cache driver
            if (config('octane.server') === 'swoole') {
                $this->registerOctaneCache();
            }
        });

        // Set up concurrent task handling
        $this->setupConcurrentTasks();
    }

    /**
     * Register the Octane cache driver.
     */
    protected function registerOctaneCache(): void
    {
        // Define some common cache intervals that will be refreshed automatically
        Cache::store('octane')->interval('app_stats', function () {
            return [
                'users_count' => \App\Models\User::count(),
                'active_projects' => \App\Models\Project::where('status', 'active')->count(),
                'pending_tasks' => \App\Models\Task::where('status', 'pending')->count(),
            ];
        }, seconds: 60);

        // Define a cache for frequently accessed configuration
        Cache::store('octane')->interval('system_config', function () {
            return \App\Models\SystemConfig::pluck('value', 'key')->toArray();
        }, seconds: 300);
    }

    /**
     * Set up concurrent task handling.
     */
    protected function setupConcurrentTasks(): void
    {
        // Register any concurrent task handlers
    }
}