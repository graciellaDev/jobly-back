<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use App\Models\Stage;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function index(int $funnelId)
    {
        $stages = Funnel::find($funnelId)->toArray();
    }

    public function show(int $id)
    {
        $stage = Stage::find($id);
        if (empty($stage)) {
            return response()->json([
                'message' => 'Этап не найден'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $stage
        ]);
    }

    public function create()
    {
        $stage = new Stage();

        return response()->json([
            'message' => 'Этап воронки ' . $stage->name . ' создана',
            'data' => $stage
        ]);
    }

    public function delete(int $id)
    {
        $stage = Stage::find($id);

        if (!empty($stage)) {
            $name = $stage->name;
            if ($stage->fixed) {
                return response()->json([
                    'message' => 'Этап ' . $stage->name . ' удалять нельзя'
                ], 409);
            }

            return response()->json([
                'message' => 'Этап ' . $name . ' успешно удален',
                'data' => $stage
            ]);
        } else {
            return response()->json([
                'message' => 'Этап не найден'
            ], 404);
        }
    }
}
