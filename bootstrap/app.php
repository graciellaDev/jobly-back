<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
                Route::group(['middleware' => 'customer-auth'], function () {
                    Route::middleware('customer-admin-auth')
                        ->prefix('api')
                        ->name('roles.')
                        ->group(base_path('routes/api-role.php'));
                });
                Route::middleware('customer-auth')
                    ->prefix('api/applications')
                    ->name('applications.')
                    ->group(base_path('routes/api-application.php'));
                Route::middleware('customer-auth')
                    ->prefix('api/tasks')
                    ->name('task.')
                    ->group(base_path('routes/api-task.php'));
                Route::middleware('customer-auth')
                    ->prefix('api/phrases')
                    ->name('phrases.')
                    ->group(base_path('routes/api-phrase.php'));
                Route::middleware('customer-auth')
                    ->prefix('api/hh')
                    ->name('hh.')
                    ->group(base_path('routes/api-hh.php'));
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
