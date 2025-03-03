<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Funnel;
use Illuminate\Http\Request;

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
            return json_encode([
                'massage' => 'Воронка ' . $name . ' успешно удалена'
            ]);
        } else {
            return json_encode([
                'message' => 'Воронка не найдена'
            ], 404);
        }
    }
}
