<?php
use App\Http\Controllers\api\AvitoController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/auth', [AvitoController::class, 'auth']);
    Route::get('/code', [AvitoController::class, 'code']);

    Route::group(['middleware' => 'avito-auth'], function () {
        Route::get('/profile', [AvitoController::class, 'getProfile']);
        Route::get('/publications', [AvitoController::class, 'getPublicationList']);
        Route::get('/publications/{id}', [AvitoController::class, 'getPublication']);
        Route::post('/publications', [AvitoController::class, 'addPublication']);
        Route::get('/drafts', [AvitoController::class, 'getDraftList']);
        Route::post('/drafts', [AvitoController::class, 'addDraft']);
        Route::post('/send-url', [AvitoController::class, 'sendUrl']);
    });
});
