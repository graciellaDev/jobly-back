<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\TaskType;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function getTypes()
    {
        $types = TaskType::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $types
        ]);
    }
}
