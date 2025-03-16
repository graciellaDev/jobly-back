<?php
use App\Http\Controllers\api\VacancyController;
use App\Http\Controllers\api\FunnelController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('vacancy-fields', [VacancyController::class, 'fields']);
    Route::get('vacancies', [VacancyController::class, 'index']);
    Route::post('vacancies', [VacancyController::class, 'create']);
    Route::get('vacancies/{id?}', [VacancyController::class, 'show']);
    Route::put('vacancies/{id?}', [VacancyController::class, 'update']);
    Route::delete('vacancies/{id?}', [VacancyController::class, 'delete']);

    Route::get('funnels', [FunnelController::class, 'index']);
    Route::post('funnels', [FunnelController::class, 'create']);
    Route::delete('funnels/{id}', [FunnelController::class, 'delete']);
    Route::get('funnels/stages/{id}', [FunnelController::class, 'indexStage']);
    Route::post('funnels/stages/{id}', [FunnelController::class, 'createStage']);
    Route::delete('funnels/stages/{id}', [FunnelController::class, 'deleteStage']);
});

