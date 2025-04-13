<?php
use App\Http\Controllers\api\ActionController;
use App\Jobs\ActionStage;

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('action-invite', [ActionController::class, 'invite']);
    Route::get('action-new', [ActionController::class, 'show']);
});
