<?php
use App\Http\Controllers\api\HeadHunterController ;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/auth', [HeadHunterController::class, 'auth']);
    Route::get('/code', [HeadHunterController::class, 'code']);
    Route::get('/profile', [HeadHunterController::class, 'getProfile']);
});

