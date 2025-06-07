<?php
use App\Http\Controllers\api\PhraseController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/', [PhraseController::class, 'index']);
    Route::get('/{id}', [PhraseController::class, 'show']);
    Route::post('/s', [PhraseController::class, 'create']);
    Route::delete('/{id}', [PhraseController::class, 'delete']);
});
