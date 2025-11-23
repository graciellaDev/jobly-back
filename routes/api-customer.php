<?php
use App\Http\Controllers\api\VacancyController;
use App\Http\Controllers\api\FunnelController;
use App\Http\Controllers\api\CustomFieldController;
use App\Http\Controllers\api\CustomFieldTypeController;
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\ClientController;
use App\Http\Controllers\api\DepartmentController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('vacancy-fields', [VacancyController::class, 'fields']);
    Route::get('vacancies', [VacancyController::class, 'index']);
    Route::post('vacancies', [VacancyController::class, 'create']);
    Route::get('vacancies/{id}', [VacancyController::class, 'show']);
    Route::put('vacancies/{id}', [VacancyController::class, 'update']);
    Route::delete('vacancies/{id}', [VacancyController::class, 'delete']);

    Route::get('funnels', [FunnelController::class, 'index']);
    Route::post('funnels', [FunnelController::class, 'create']);
    Route::delete('funnels/{id}', [FunnelController::class, 'delete']);
    Route::get('funnels/stages/{id}', [FunnelController::class, 'indexStage']);
    Route::post('funnels/stages/{id}', [FunnelController::class, 'createStage']);
    Route::delete('funnels/stages/{id}', [FunnelController::class, 'deleteStage']);

    Route::get('custom-fields', [CustomFieldController::class, 'index']);
    Route::post('custom-fields', [CustomFieldController::class, 'create']);
    Route::get('custom-fields-types', [CustomFieldTypeController::class, 'index']);

    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{id}', [ClientController::class, 'show']);

    Route::get('managers', [CustomerController::class, 'getManagers']);
    Route::get('executors', [CustomerController::class, 'getExecutors']);
    Route::get('responsibles', [CustomerController::class, 'getResponsibles']);
    Route::get('team/{vacancy_id}', [CustomerController::class, 'getTeam']);

    Route::get('departments', [DepartmentController::class, 'index']);
    Route::get('departments/{id}', [DepartmentController::class, 'show']);
    Route::post('departments', [DepartmentController::class, 'create']);
    Route::post('departments/{id}/divisions', [DepartmentController::class, 'createDivision']);

    Route::get('profile', [CustomerController::class, 'getProfile']);
});

