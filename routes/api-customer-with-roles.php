<?php
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\ClientController;
use App\Http\Controllers\api\RecruiterController;
use App\Http\Controllers\api\ResponsibleControlles;
use App\Http\Controllers\api\EmployeesController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/customer-with-roles/register-client', [CustomerController::class, 'registerClient']);
    Route::post('/customer-with-roles/register-recruiter', [CustomerController::class, 'registerRecruiter']);

    Route::get('/customer-with-roles/clients', [ClientController::class, 'index']);
    Route::get('/customer-with-roles/clients/{id}', [ClientController::class, 'show']);

    Route::get('/customer-with-roles/recruiters', [RecruiterController::class, 'index']);
    Route::get('/customer-with-roles/recruiters/{id}', [RecruiterController::class, 'show']);

    Route::get('/customer-with-roles/responsibles', [ResponsibleControlles::class, 'index']);
    Route::get('/customer-with-roles/employees', [EmployeesController::class, 'index']);
});
