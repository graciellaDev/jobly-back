<?php
use App\Http\Controllers\api\CandidateController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('candidates/{id}', [CandidateController::class, 'show']);
    Route::post('candidates', [CandidateController::class, 'create']);
    Route::put('candidates/{id}', [CandidateController::class, 'update']);
//    Route::delete('candidates/{id}', [CandidateController::class], 'delete');
});
