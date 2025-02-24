<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Driver;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Condition;
use App\Models\Place;
use App\Models\Schedule;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use App\Models\Employment;

class VacancyController extends Controller
{
    public function fields() {
        $data = [
            'employments' => Employment::all()->pluck('name', 'id')->all(),
            'schedules' => Schedule::all()->pluck('name', 'id'),
            'experiences' => Experience::all()->pluck('name', 'id'),
            'education' => Education::all()->pluck('name', 'id'),
            'condition' => Condition::all()->pluck('name', 'id'),
            'currencies' => Currency::all()->pluck('name', 'id'),
            'drivers' => Driver::all()->pluck('name', 'id'),
            'places' => Place::all(['id', 'name', 'description'])->toArray(),

        ];

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }

    public function show(int $id) {

        return json_encode([
            'message' => 'Success',
            'data' => !empty(Vacancy::all()->find($id)) ? Vacancy::all()->find($id) : []
        ]);
    }

    public function create(Request $request) {
//        var_dump($request->industry);

        return 'create vacancy';
    }
}
