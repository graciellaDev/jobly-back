<?php
use App\Http\Controllers\api\CandidateController;
use App\Http\Controllers\api\TagController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('candidates/{id}', [CandidateController::class, 'show']);
    Route::post('candidates', [CandidateController::class, 'create']);
    Route::put('candidates/{id}', [CandidateController::class, 'update']);
    Route::delete('candidates/{id}', [CandidateController::class, 'delete']);
    Route::post('candidates/reply', [CandidateController::class, 'reply']);
});
