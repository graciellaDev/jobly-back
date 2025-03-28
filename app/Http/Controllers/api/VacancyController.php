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
    public function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
//        var_dump(Vacancy::where('customer_id', $customerId)->select(['id', 'name as title', 'location as city'])->paginate());
        $vacancies = Vacancy::where('customer_id', $customerId)->select(['id', 'name as title', 'location as city'])->paginate();
        $vacancies->getCollection()->transform(function ($vacancy) {
            $vacancy->footerData = [
                'sites' => 0,
                'responsible' => 'Не назначен',
                'itemId' => $vacancy->id . ' ID'
            ];
            return $vacancy;
        });


        return response()->json([
            'message' => 'Success',
            'data' => $vacancies
        ]);
    }
    public function fields()
    {
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

    public function show(Request $request, int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $vacancy = Vacancy::where('customer_id', $customerId)->find($id);
        if (!empty($vacancy)) {
            $vacancy['conditions'] = $vacancy->conditions;
            $vacancy['drivers'] = $vacancy->drivers;
            $vacancy['additions'] = $vacancy->additions;
        }

        return json_encode([
            'message' => 'Success',
            'data' => $vacancy
        ]);
    }

    public function create(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'description' => 'required|string|min:3|max:255',
                'code' => 'nullable|string|max:255',
                'specializations' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'employment' => 'nullable|string|max:255',
                'schedule' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'education' => 'nullable|string|max:255',
                'salary_from' => 'nullable|string|max:255',
                'salary_to' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'place' => 'nullable|numeric|max:255',
                'location' => 'nullable|string|max:255',
                'phrases' => 'nullable|string|max:255',
                'customer_id' => 'nullable|numeric',
                'customer_name' => 'nullable|string',
                'customer_phone' => 'nullable|regex:/^\+7\d{10}$/',
                'customer_email' => 'nullable|string'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        $isExists = Vacancy::where('code', $request->code)->where('customer_id', $customerId)->exists();

        if ($isExists) {
            return response()->json([
                'massage' => 'Вакансия с кодом ' . $request->code . ' уже существует'
            ], 409);
        }

        $isExists = Vacancy::where('name', $request->name)->where('customer_id', $customerId)->exists();
        if ($isExists) {
            return response()->json([
                'massage' => 'Вакансия с названием ' . $request->name . ' уже существует'
            ], 409);
        }

        unset($data['place']);
        $data['customer_id'] = $request->attributes->get('customer_id');

        try {
            $vacancy = Vacancy::create($data);
        } catch (\Throwable $th) {
            return response()->json([
                'massage' => 'Ошибка создания вакансии ' . $request->name
            ], 500);
        }

        if(isset($request->conditions)) {
            $vacancy->conditions()->attach($request->conditions);
        }
        if(isset($request->additions)) {
            $vacancy->additions()->attach($request->additions);
        }
        if(isset($request->drivers)) {
            $vacancy->drivers()->attach($request->drivers);
        }
        if(isset($request->place)) {
            $place = Place::all()->find($request->place);
            if (!empty($place)) {
                $vacancy->places = $request->place;
                $vacancy->save();
            }
        }

        $place = Place::find($vacancy->places);
        $vacancy->place = $place;
        $vacancy->makeHidden('places');

        $conditions = Condition::whereIn('id', $request->conditions)->get();
        $vacancy->conditions = $conditions->toArray();

        $drivers = Driver::whereIn('id', $request->drivers)->get();
        $vacancy->drivers = $drivers;

        return response()->json([
            'message' => 'Вакансия ' . $request->name . ' успешно создана',
            'data' => $vacancy
        ]);
    }

    public function delete (Request $request, int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $vacancy = Vacancy::where('customer_id', $customerId)->find($id);
        if (!empty($vacancy)) {
            $name = $vacancy->name;
            $vacancy->delete();
            $vacancy->conditions()->detach();
            $vacancy->additions()->detach();
            $vacancy->drivers()->detach();

            return response()->json([
                'massage' => 'Вакансия ' . $name . ' успешно удалена'
            ]);
        } else {
            return response()->json([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }

    public function update (Request $request, int $id): mixed
    {
        $customerId = $request->attributes->get('customer_id');
        $vacancy = Vacancy::where('customer_id', $customerId)->find($id);

        if (!empty($vacancy)) {
            try {
                $data = $request->validate([
                    'name' => 'nullable|string|min:3|max:255',
                    'description' => 'nullable|string|min:3|max:65535',
                    'code' => 'nullable|string|max:255',
                    'specializations' => 'nullable|string|max:255',
                    'industry' => 'nullable|string|max:255',
                    'employment' => 'nullable|string|max:255',
                    'schedule' => 'nullable|string|max:255',
                    'experience' => 'nullable|string|max:255',
                    'education' => 'nullable|string|max:255',
                    'salary_from' => 'nullable|string|max:255',
                    'salary_to' => 'nullable|string|max:255',
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

            if (isset($request->place)) {
                $data['places'] = $data['place'];
                unset($data['place']);
            }
            if (!empty($data)) {
                if (empty($request->name)) {
                    $data['name'] = $vacancy->name;
                }
                if (empty($request->description)) {
                    $data['description'] = $vacancy->description;
                }
            }

            if(isset($request->place)) {
                $place = Place::all()->find($request->place);
                if (!empty($place)) {
                    $vacancy->places = $request->place;
                }
            }
            $vacancy->update($data);

            $place = Place::find($vacancy->places);
            $vacancy->place = $place;
            $vacancy->makeHidden('places');

            try{
                if (isset($request->conditions)) {
                    $relatedFields = array_map(fn($el) => intval($el), $request->conditions);

                    var_dump($vacancy);
                }
//                if (isset($request->drivers)) {
//                    $relatedFields = array_map(fn($el) => intval($el), $request->drivers);
//                    $vacancy->drivers()->sync($relatedFields);
//                }
//                if (isset($request->additions)) {
//                    $relatedFields= array_filter($request->additions);
//                    $vacancy->additions()->sync($relatedFields);
//                }
            } catch (\Throwable $th) {

                return response()->json([
                    'message' => 'Ошибка обновления связаных данных',
                    ], 409);
            }

//            $vacancy = Vacancy::with(['conditions', 'drivers', 'additions'])->find($vacancy->id);

            return response()->json([
                'massage' => 'Вакансия ' . $vacancy->name . ' успешно обновлена',
                'data' => $vacancy
            ]);
        } else {
            return response()->json([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }
}
