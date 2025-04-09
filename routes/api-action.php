<?php
use App\Http\Controllers\api\ActionController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('action', [ActionController::class, 'index']);
    Route::get('action-new', [ActionController::class, 'show']);
});
