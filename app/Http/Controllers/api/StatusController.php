<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index()
    {
        $statuses = Status::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $statuses
        ]);
    }

    public function show(int $id)
    {
        $status = Status::find($id);

        if (empty($status)) {
            return response()->json([
                'massage' => 'Статус заявки с id = ' . $id . ' не найден'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $status
        ]);
    }
}
