<?php
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\ClientController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/customer-with-roles/register-client', [CustomerController::class, 'registerClient']);
    Route::post('/customer-with-roles/register-recruiter', [CustomerController::class, 'registerRecruiter']);

    Route::get('/customer-with-roles/clients', [ClientController::class, 'index']);
    Route::get('/customer-with-roles/clients/{id}', [ClientController::class, 'show']);
});
