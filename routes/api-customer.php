<?php
use App\Http\Controllers\api\VacancyController;
use App\Http\Controllers\api\FunnelController;
use App\Http\Controllers\api\CustomFieldController;
use App\Http\Controllers\api\CustomFieldTypeController;

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
});

