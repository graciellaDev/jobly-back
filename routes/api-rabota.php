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
        // Справочники для создания вакансии
        Route::get('/dictionaries/areas', [RabotaRuController::class, 'getAreas']);
        Route::get('/dictionaries/employment-forms', [RabotaRuController::class, 'getEmploymentForms']);
        Route::get('/dictionaries/education-levels', [RabotaRuController::class, 'getEducationLevels']);
        Route::get('/dictionaries/experience', [RabotaRuController::class, 'getExperience']);
        Route::get('/dictionaries/driver-license-types', [RabotaRuController::class, 'getDriverLicenseTypes']);
        Route::get('/dictionaries/billing-types', [RabotaRuController::class, 'getBillingTypes']);
        Route::get('/dictionaries/work-formats', [RabotaRuController::class, 'getWorkFormats']);
        Route::get('/dictionaries/working-hours', [RabotaRuController::class, 'getWorkingHours']);
        Route::get('/dictionaries/schedules', [RabotaRuController::class, 'getSchedules']);
    });
});
