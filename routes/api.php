<?php

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\CustomerController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('login-jwt', 'login');
//     Route::post('register-jwt', 'register');
    Route::get('profile-jwt', 'profile');
});

Route::group(['middleware' => 'auth:api'], function () {
    //Route::middleware(['api'])->group(function() {
    // Route::controller(AuthController::class)->group(function () {
    //     Route::get('profile', 'profile');
    //     Route::get('refresh', 'refresh');
    //     Route::get('logout', 'logout');
    //     Route::post('register', 'register');
    // });
    Route::post('login', [CustomerController::class, 'login']);
    Route::post('register', [CustomerController::class, 'register']);
});
