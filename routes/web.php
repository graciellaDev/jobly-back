<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\HeadHunterController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    //    Artisan::call('config:cache');
//    Artisan::call('view:clear');
//    Artisan::call('route:clear');
//    Artisan::call('backup:clean');
    return "Кэш очищен.";
});

Route::get('/reg-success/{id}', [CustomerController::class, 'regSuccess']);
Route::get('/code-hh', [HeadHunterController::class, 'code']);
//Route::get('/restore-success/{id}', CustomerController::class, 'restoreSuccess');
