<?php
use App\Http\Controllers\api\ActionController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('invite', [ActionController::class, 'invite']);
    Route::post('move-stage', [ActionController::class, 'moveStage']);
});
