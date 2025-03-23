<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldType;
use Illuminate\Http\Request;

class CustomFieldTypeController extends Controller
{
    public function index()
    {
        $types = CustomFieldType::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $types
        ]);
    }
}
