<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Phrase;
use Illuminate\Http\JsonResponse;

class PhraseController extends Controller
{
    public function index(): JsonResponse
    {
        $phrases = Phrase::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $phrases
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $phrase = Phrase::find($id);

        if (empty($phrase)) {
            return response()->json([
                'message' => 'Тэг с id = ' . $id . ' не найден',
                'data' => $phrase
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $phrase
        ], 200);
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:50'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $phrase = Phrase::where('name', $request->name)->get();
        if (!$phrase->isEmpty()) {
            return response()->json([
                'massage' => 'Фраза ' . $data['name'] . ' уже существует'
            ], 409);
        }

        try {
            $phrase = Phrase::create($data);
        } catch (\Throwable $th) {
            echo $th->getMessage();
            return response()->json([
                'massage' => 'Ошибка создания фразы ' . $data['name']
            ], 500);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $phrase
        ]);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $phrase = Phrase::find($id);
        if (empty($phrase)) {
            return response()->json([
                'massage' => 'Фраза с id = ' . $id  . ' не существует'
            ], 404);
        }
        $name = $phrase->name;

        $phrase->delete();
        return response()->json([
            'message' => 'Фраза ' . $name . ' успешно удалена',
        ]);
    }
}
