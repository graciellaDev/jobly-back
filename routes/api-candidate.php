<?php
use App\Http\Controllers\api\CandidateController;
use App\Http\Controllers\api\TagController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('candidates/{id}', [CandidateController::class, 'show']);
    Route::post('candidates', [CandidateController::class, 'create']);
    Route::put('candidates/{id}', [CandidateController::class, 'update']);
    Route::delete('candidates/{id}', [CandidateController::class, 'delete']);

    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{id}', [TagController::class, 'show']);
    Route::post('tags', [TagController::class, 'create']);
    Route::delete('tags/{id}', [TagController::class, 'delete']);
});
