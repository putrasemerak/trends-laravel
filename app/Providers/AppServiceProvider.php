<?php

namespace App\Providers;

use App\Auth\EmployeeUserProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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

        // Apply locale from session on every request
        App::setLocale(Session::get('locale', 'ms'));
    }
}
