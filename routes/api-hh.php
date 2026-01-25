<?php
use App\Http\Controllers\api\HeadHunterController ;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/auth', [HeadHunterController::class, 'auth']);
    Route::get('/code', [HeadHunterController::class, 'code']);

    Route::group(['middleware' => 'head-hunter-auth'], function () {
        Route::get('/profile', [HeadHunterController::class, 'getProfile']);
        Route::post('/publication', [HeadHunterController::class, 'addPublication']);
        Route::get('/publications', [HeadHunterController::class, 'getPublicationList']);
        Route::get('/publications/{id}', [HeadHunterController::class, 'getPublication']);
        Route::get('/publications/{id}/count-visitors', [HeadHunterController::class, 'getCountVisitors']);
        Route::get('/drafts', [HeadHunterController::class, 'getDraftList']);
        Route::post('/drafts', [HeadHunterController::class, 'addDraft']);
        Route::post('/avalibale-types', [HeadHunterController::class, 'getAvailableTypes']);
        Route::get('/roles', [HeadHunterController::class, 'getProfessionals']);
        Route::get('/vacancy-responses/{id}', [HeadHunterController::class, 'getVacancyResponses']);
        Route::get('/vacancy-response/{id}', [HeadHunterController::class, 'getVacancyResponse']);
        Route::get('/vacancies', [HeadHunterController::class, 'getVacancies']);
        Route::post('/send-url', [HeadHunterController::class, 'sendUrl']);
        Route::delete('auth', [HeadHunterController::class, 'closeAuth']);
        Route::get('/addresses', [HeadHunterController::class, 'getAddresses']);
    });
});

