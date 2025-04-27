<?php
use App\Http\Controllers\api\ActionController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('invite', [ActionController::class, 'invite']);
    Route::post('move-stage', [ActionController::class, 'moveStage']);
    Route::post('refuse', [ActionController::class, 'refuse']);
    Route::post('send-email', [ActionController::class, 'sendMail']);
    Route::post('change-manager', [ActionController::class, 'changeManager']);
});
