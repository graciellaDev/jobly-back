<?php
use App\Http\Controllers\api\RabotaRuController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/auth', [RabotaRuController::class, 'auth']);
    Route::get('/code', [RabotaRuController::class, 'code']);

    Route::group(['middleware' => 'rabota-auth'], function () {
        Route::get('/profile', [RabotaRuController::class, 'getProfile']);
        Route::get('/publications', [RabotaRuController::class, 'getPublicationList']);
        Route::get('/publications/{id}', [RabotaRuController::class, 'getPublication']);
        Route::get('/publications/{id}/count-visitors', [RabotaRuController::class, 'getCountVisitors']);
        Route::get('/drafts', [RabotaRuController::class, 'getDraftList']);
        Route::post('/drafts', [RabotaRuController::class, 'addDraft']);
        Route::post('/avalibale-types', [RabotaRuController::class, 'getAvailableTypes']);
        Route::get('/roles', [RabotaRuController::class, 'getProfessionals']);
        Route::get('/vacancy-responses/{id}', [RabotaRuController::class, 'getVacancyResponses']);
        Route::get('/vacancy-response/{id}', [RabotaRuController::class, 'getVacancyResponse']);
        Route::get('/vacancies', [RabotaRuController::class, 'getVacancies']);
        Route::post('/send-url', [RabotaRuController::class, 'sendUrl']);
        Route::delete('auth', [RabotaRuController::class, 'closeAuth']);
        Route::get('/addresses', [RabotaRuController::class, 'getAddresses']);
    });
});
