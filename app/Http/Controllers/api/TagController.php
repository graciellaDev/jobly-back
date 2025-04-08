<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $tags
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $tag = Tag::find($id);
        if (empty($tag)) {
            return response()->json([
                'message' => 'Тэг с id = ' . $id . ' не найден',
                'data' => $tag
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $tag
        ]);
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

        $tag = Tag::where('name', $request->name)->get();
        if (!$tag->isEmpty()) {
            return response()->json([
                'massage' => 'Тэг ' . $data['name'] . ' уже существует'
            ], 409);
        }

        try {
            $tag = Tag::create($data);
        } catch (\Throwable $th) {
            echo $th->getMessage();
            return response()->json([
                'massage' => 'Ошибка создания тэга ' . $data['name']
            ], 500);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $tag
        ]);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $tag = Tag::find($id);
        if (empty($tag)) {
            return response()->json([
                'massage' => 'Тэг с id = ' . $id  . ' не существует'
            ], 404);
        }
        $name = $tag->name;

        $tag->delete();
        return response()->json([
            'message' => 'Тэг ' . $name . ' успешно удален',
        ]);
    }
}
