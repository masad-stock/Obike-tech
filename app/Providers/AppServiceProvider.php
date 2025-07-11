<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MpesaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MpesaService::class, function ($app) {
            return new MpesaService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

