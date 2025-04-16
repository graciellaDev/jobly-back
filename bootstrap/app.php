<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\api\ApiCustomerMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::group(['middleware' => 'auth:api'], function () {
                Route::middleware('customer-auth')
                    ->prefix('api')
                    ->name('customer.')
                    ->group(base_path('routes/api-customer.php'));
                Route::middleware('customer-auth')
                    ->prefix('api')
                    ->name('candidate.')
                    ->group(base_path('routes/api-candidate.php'));
                Route::middleware('customer-auth')
                    ->prefix('api')
                    ->name('tag.')
                    ->group(base_path('routes/api-tag.php'));
                Route::middleware('customer-auth')
                    ->prefix('api/action')
                    ->name('action.')
                    ->group(base_path('routes/api-action.php'));
            });
        })
//        then: function () {
//            Route::group(['middleware' => 'auth:api'], function () {}
//            Route::middleware('auth:api')
//                ->Route::middleware('customer-auth')
//                ->prefix('api')
//                ->name('customer.')
//                ->group(base_path('routes/api-customer.php'));
//        })
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
