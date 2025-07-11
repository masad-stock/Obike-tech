<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Lottery;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureServiceProvider extends ServiceProvider
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
        // New rental dashboard with analytics
        Feature::define('enhanced-rental-dashboard', function (User $user) {
            // Always enable for admins and managers
            if ($user->hasRole(['admin', 'manager'])) {
                return true;
            }
            
            // Enable for 10% of other users
            return Lottery::odds(1, 10);
        });

        // New procurement workflow
        Feature::define('streamlined-procurement', function (User $user) {
            // Enable for all procurement staff
            if ($user->hasPermissionTo('manage-procurement')) {
                return true;
            }
            
            // Enable for 5% of other users
            return Lottery::odds(1, 20);
        });

        // Mobile-optimized interface
        Feature::define('mobile-optimized-ui', Lottery::odds(3, 10));

        // Advanced reporting features
        Feature::define('advanced-reporting', function (User $user) {
            // Enable for users with reporting permissions
            if ($user->hasPermissionTo('view-financial-reports')) {
                return true;
            }
            
            // Enable for 25% of managers
            if ($user->hasRole('manager')) {
                return Lottery::odds(1, 4);
            }
            
            return false;
        });

        // Equipment maintenance scheduling
        Feature::define('maintenance-scheduling', function (User $user) {
            // Enable for maintenance staff
            if ($user->hasPermissionTo('manage-maintenance')) {
                return true;
            }
            
            // Enable for 50% of managers
            if ($user->hasRole('manager')) {
                return Lottery::odds(1, 2);
            }
            
            return false;
        });
    }
}