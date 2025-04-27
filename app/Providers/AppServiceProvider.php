<?php

namespace App\Providers;

use App\Http\Middleware\api\ApiCustomerMiddleware;
use App\Http\Middleware\api\CustomerPermissionMiddleware;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app['router']->aliasMiddleware('customer-auth', ApiCustomerMiddleware::class);
        $this->app['router']->aliasMiddleware('customer-admin-auth', CustomerPermissionMiddleware::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
