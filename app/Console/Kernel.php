<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Warm up cache every day at midnight
        $schedule->command('app:cache-warmup')
            ->dailyAt('00:00')
            ->environments(['production', 'staging'])
            ->withoutOverlapping();
        
        // Refresh model cache every hour to keep data relatively fresh
        $schedule->command('app:cache-warmup --type=models')
            ->hourly()
            ->environments(['production', 'staging'])
            ->withoutOverlapping();
        
        // Clear and rebuild cache weekly to prevent stale data
        $schedule->command('app:cache-clear')
            ->weekly()
            ->sundays()
            ->at('23:00')
            ->environments(['production', 'staging'])
            ->withoutOverlapping()
            ->then(function () {
                \Artisan::call('app:cache-warmup');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}