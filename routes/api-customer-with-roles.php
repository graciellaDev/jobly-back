<?php
use App\Http\Controllers\api\CustomerController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/customer-with-roles/register-client', [CustomerController::class, 'registerClient']);
    Route::post('/customer-with-roles/register-recruiter', [CustomerController::class, 'registerRecruiter']);
});
