<?php
use App\Http\Controllers\api\TagController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{id}', [TagController::class, 'show']);
    Route::post('tags', [TagController::class, 'create']);
    Route::delete('tags/{id}', [TagController::class, 'delete']);
    Route::get('tags/find/{name}', [TagController::class, 'find']);
});
