<?php
use App\Http\Controllers\api\StatusController;
use App\Http\Controllers\api\ApplicationController;

Route::get('statuses', [StatusController::class, 'index']);
Route::get('statuses/{id}', [StatusController::class, 'show']);
Route::get('/', [ApplicationController::class, 'index']);
Route::get('/{id}', [ApplicationController::class, 'show']);
Route::post('/', [ApplicationController::class, 'create']);
Route::put('/{id}', [ApplicationController::class, 'update']);
Route::delete('/{id}', [ApplicationController::class, 'delete']);
Route::post('/{id}/approve', [ApplicationController::class, 'approve']);
Route::post('/{id}/reject', [ApplicationController::class, 'reject']);
