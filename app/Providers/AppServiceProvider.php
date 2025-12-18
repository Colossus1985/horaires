<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurer la locale française pour Carbon
        \Carbon\Carbon::setLocale('fr');
        
        // Limiter la longueur des index pour MySQL
        Schema::defaultStringLength(191);
    }
}
