<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActionStage;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActionController extends Controller
{
    public function index()
    {
        ActionStage::dispatch()->delay(Carbon::now()->addMinutes(1));

        return response()->json([
            'message' => 'Задание отправлено в очередь',
        ]);
    }

    public function show()
    {
        ActionStage::dispatch()->delay(Carbon::now()->addMinutes(1));

        return response()->json([
            'message' => 'Еще одно задание отправлено в очередь',
        ]);
    }
}
