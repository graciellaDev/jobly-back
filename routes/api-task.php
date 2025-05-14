<?php
use App\Http\Controllers\api\TaskController;

Route::get('types', [TaskController::class, 'getTypes']);
