<?php
use App\Http\Controllers\api\CandidateController;
// use App\Http\Controllers\api\TagController;
use App\Http\Controllers\api\EventController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::get('candidates/{id}', [CandidateController::class, 'show']);
    Route::post('candidates', [CandidateController::class, 'create']);
    Route::put('candidates/{id}', [CandidateController::class, 'update']);
    Route::delete('candidates/{id}', [CandidateController::class, 'delete']);
    Route::post('candidates/reply', [CandidateController::class, 'reply']);
    Route::delete('/candidates/{id}/tags/{tag}', [CandidateController::class, 'detachTag']);

    Route::get('candidates/{id}/events', [EventController::class, 'indexByCandidate']);
    Route::get('candidates/{id}/vacancies/{vacancyId}/events', [EventController::class, 'indexByCandidateVacancy']);

    Route::post('candidates/{id}/chats', [EventController::class, 'createChatMessage']);
    Route::post('candidates/{id}/vacancies/{vacancyId}/chats', [EventController::class, 'createChatMessage']);
});
