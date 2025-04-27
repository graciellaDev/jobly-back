<?php
use App\Http\Controllers\api\RoleController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::post('roles', [RoleController::class, 'create']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'delete']);
});
