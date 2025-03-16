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
    // Route::post('register-jwt', 'register');
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
    Route::post('restore-access', [CustomerController::class, 'restoreAccess']);
    Route::post('restore-success/{id}', [CustomerController::class, 'restoreSuccess']);

//    Route::middleware(['customer-auth'])->group(function () {
//        Route::get('vacancy-fields', [VacancyController::class, 'fields']);
//        Route::get('vacancies', [VacancyController::class, 'index']);
//        Route::post('vacancies', [VacancyController::class, 'create']);
//        Route::get('vacancies/{id?}', [VacancyController::class, 'show']);
//        Route::put('vacancies/{id?}', [VacancyController::class, 'update']);
//        Route::delete('vacancies/{id?}', [VacancyController::class, 'delete']);
//
//        Route::get('funnels', [FunnelController::class, 'index']);
//        Route::post('funnels', [FunnelController::class, 'create']);
//        Route::delete('funnels/{id}', [FunnelController::class, 'delete']);
//        Route::get('funnels/stages/{id}', [FunnelController::class, 'indexStage']);
//        Route::post('funnels/stages/{id}', [FunnelController::class, 'createStage']);
//        Route::delete('funnels/stages/{id}', [FunnelController::class, 'deleteStage']);
//    });
});
