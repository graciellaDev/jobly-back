<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index()
    {
        $customFields = CustomField::with('type')->get();
        $customFields->makeHidden(['type_id']);

        return response()->json([
            'message' => 'Success',
            'data' => $customFields
        ]);
    }

    public function create(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:50',
                'require' => 'nullable|boolean',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        if (isset($request->type)) {
            $data['type_id'] = $request->type;
        }

        try {
            $customField = CustomField::create($data);

        } catch (\Throwable $th) {
            var_dump($th->getMessage());
            return response()->json([
                'message' => 'Ошибка создания пользовательского поля ' . $data['name'],
                'data' => []
            ], 500);
        }

        return response()->json([
            'message' => 'Пользовательское поле ' . $data['name'] . ' создано',
            'data' => $customField
        ]);
    }
}
