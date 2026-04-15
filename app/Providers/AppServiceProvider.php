<?php

namespace App\Providers;

use App\Auth\EmployeeUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

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
        Auth::provider('employee', function ($app, array $config) {
            return new EmployeeUserProvider();
        });

        // Locale is applied per-request via App\Http\Middleware\SetLocale
        // (session is not yet available here at boot time)
    }
}
