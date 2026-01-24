<?php

namespace App\Providers;

use App\Http\Middleware\api\ApiCustomerMiddleware;
use App\Http\Middleware\api\AvitoMiddleware;
use App\Http\Middleware\api\CustomerPermissionMiddleware;
use App\Http\Middleware\api\HeadHunterMiddleware;
use App\Http\Middleware\api\RabotaRuMiddleware;
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
        $this->app['router']->aliasMiddleware('head-hunter-auth', HeadHunterMiddleware::class);
        $this->app['router']->aliasMiddleware('avito-auth', AvitoMiddleware::class);
        $this->app['router']->aliasMiddleware('rabota-auth', RabotaRuMiddleware::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
