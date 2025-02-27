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
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'specializations' => 'nullable|string|max:255',
                'employment' => 'nullable|string|max:255',
                'schedule' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'education' => 'nullable|string|max:255',
                'salary_from' => 'nullable|string|max:255',
                'salary_to' => 'nullable|string|max:255',
                'salary' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'place' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'phrases' => 'nullable|string|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        $vacancy = Vacancy::create($data);

        return json_encode([
            'message' => 'Вакансия успешно создана',
            'data' => $vacancy
        ]);
    }

    public function delete (int $id) {
        $vacancy = Vacancy::find($id);
        if (!empty($vacancy)) {
            $name = $vacancy->name;
            $vacancy->delete();
            return json_encode([
                'massage' => 'Вакансия ' . $name . ' успешно удалена'
            ]);
        } else {
            return json_encode([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }

    public function update (int $id): mixed
    {
        $vacancy = Vacancy::find($id);
        if (!empty($vacancy)) {
            $name = $vacancy->name;

            return json_encode([
                'massage' => 'Вакансия ' . $name . ' успешно обновлена'
            ]);
        } else {
            return json_encode([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }
}
