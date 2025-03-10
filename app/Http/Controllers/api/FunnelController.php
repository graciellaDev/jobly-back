<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FunnelController extends Controller
{
    public function index() {

        return response()->json([
            'message' => 'Success',
            'data' => Funnel::all()->toArray()
        ]);
    }

    public function create(Request $request) {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        try {
            $funnel = Funnel::create($data);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Воронка ' . $request->name . ' уже создана создана',
            ], 409);
        }

        return response()->json([
            'message' => 'Воронка ' . $request->name . ' успешно создана',
            'data' => $funnel
        ]);
    }

    public function delete (int $id) {
        $funnel = Funnel::find($id);
        if (!empty($funnel)) {
            $name = $funnel->name;
            $funnel->delete();

            return response()->json([
                'massage' => 'Воронка ' . $name . ' успешно удалена'
            ]);
        } else {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }
    }

    public function createStage(Request $request, int $id) {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $stages = Stage::where('name', $request->name)->where('fixed', true)->get();
        if (!$stages->isEmpty()) {
            return response()->json([
                'message' => 'Этап ' . $request->name . ' нельзя создавать, он фиксирован'
            ], 409);
        }

        $funnel = Funnel::find($id);
        if (empty($funnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }

        $stages = Stage::all()->where('name', $request->name)->select(['id']);
        $stageIds = [];

        if (!$stages->isEmpty()) {
            foreach ($stages->toArray() as $stage) {
                $stageIds[] = $stage['id'];
            }

            $funnelStages = $funnel->stages;
            if (!empty($funnelStages)) {
//                $stages = $stages->whereIn($stageIds)->toArray();
                $stages = $funnelStages->select(['id'])->toArray();
                foreach ($stages as $stage) {
                    if (in_array($stage['id'], $stageIds)) {
                        return response()->json([
                            'message' => 'Этап ' . $request->name . ' уже существует в воронке ' . $funnel->name
                        ], 409);
                    }
                }
            }
        }

        $stages = Stage::create($data);
        $funnel->stages()->attach($stages->id);

        return response()->json([
            'message' => 'Этап ' . $request->name . ' в воронке ' . $funnel->name . ' успешно создан',
            'data' => $stages
        ]);
    }

    public function deleteStage(Request $request, int $id) {
        try {
            $data = $request->validate([
                'stage_id' => 'required|numeric'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $funnel = Funnel::find($id);
        if (empty($funnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }
        $stage = Stage::find($request->stage_id);
        if (empty($stage)) {
            return response()->json([
                'message' => 'Этап с идентификатором ' . $request->stage_id . ' в воронке ' . $funnel->name . ' не найден'
            ], 404);
        }
        $stageName = $stage->name;
        $funnel->stages()->detach();
        $stage->delete();

        return response()->json([
            'message' => 'Этап ' . $stageName . ' в воронке ' . $funnel->name . ' успешно удален',
        ]);
    }

    public function indexStage(int $id) {
        $fixStages = Stage::all()->where('fixed', 1)->toArray();

        $funnel = Funnel::find($id);
        if (empty($funnel)) {
            return response()->json([
                'message' => 'Воронка не найдена'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => array_merge($fixStages, $funnel->stages->toArray())
        ]);
    }
}
