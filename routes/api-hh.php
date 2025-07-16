<?php
use App\Http\Controllers\api\HeadHunterController ;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/auth', [HeadHunterController::class, 'auth']);
    Route::get('/code', [HeadHunterController::class, 'code']);

    Route::group(['middleware' => 'head-hunter-auth'], function () {
        Route::get('/profile', [HeadHunterController::class, 'getProfile']);
        Route::get('/publications', [HeadHunterController::class, 'getPublicationList']);
        Route::get('/publications/{id}', [HeadHunterController::class, 'getPublication']);
        Route::get('/drafts', [HeadHunterController::class, 'getDraftList']);
        Route::post('/avalibale-types', [HeadHunterController::class, 'getAvailableTypes']);
    });
});

