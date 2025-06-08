<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Addition;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\ConditionVacancy;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverVacancy;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Condition;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\Phrase;
use App\Models\PhraseVacancy;
use App\Models\Place;
use App\Models\Schedule;
use App\Models\Stage;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use App\Models\Employment;
use App\Models\AdditionVacancy;

use function Symfony\Component\String\b;

class VacancyController extends Controller
{
    private $statuses = [
      'active',  'draft', 'archive'
    ];

    private array $sort = [
        'asc', 'desc'
    ];

    private array $filters = [
        'status',
        'city',
        'executor',
        'client',
        'id',
        'notCandidate',
        'isApplication',
        'notExecutor'
    ];
    public function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $sort = $request->get('sort');
        $sort = !empty($sort) && in_array($sort, $this->sort) ? $sort : null;
        $filters = $request->get('filters');

        $vacancies = Vacancy::where('customer_id', $customerId);
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                switch ($key) {
                    case $this->filters[0]:
                        if (in_array($value, $this->statuses)) {
                            $vacancies = $vacancies->where($key, $value);
                        }
                        break;
                    case $this->filters[1]:
                        $vacancies = $vacancies->where('location', 'like', "$value%");
                        break;
                    case $this->filters[2]:
                        if (!empty($value)) {
                            $vacancies->where('executor_id', $value);
                        }
                        break;
                    case $this->filters[3]:
                        $isApplications = Application::where('client_id', $value)
                            ->select('vacancy_id')
                            ->pluck('vacancy_id')
                            ->toArray();
                        $vacancies->whereIn('id', $isApplications);
                        break;
                    case $this->filters[4]:
                        $vacancies = $vacancies->where('id', $value);
                        break;
                    case $this->filters[5]:
                        if ($value == 'true') {
                            $notCandidate = Candidate::whereNotNull('vacancy_id')->select('vacancy_id')->pluck('vacancy_id')->toArray();
                            $vacancies->whereNotIn('id', $notCandidate);
                        }
                        break;
                    case $this->filters[6]:
                        $applications = Application::whereNotNull('vacancy_id')
                            ->select('vacancy_id')
                            ->pluck('vacancy_id')
                            ->toArray();
                        $vacancies->whereIn('id', $applications);
                        break;
                    case $this->filters[7]:
                        if ($value == 'true') {
                            $vacancies->whereNull('executor_id');
                        }
                        break;
                }
            }
        }

        $vacancies = $vacancies->select(['id', 'name as title', 'location as city', 'executor_id', 'customer_id']);
        if (!empty($sort)) {
            $vacancies = $vacancies->orderBy('title', $sort);
        }
        $vacancies = $vacancies->paginate();
        $vacancies->getCollection()->transform(function ($vacancy) {
            $responsible = 'Не назначен';
            if (!empty($vacancy->executor_id)) {
                $responsible = Customer::select(['id', 'name'])->find($vacancy->executor_id);
            }
            $candidates = Candidate::where('vacancy_id', $vacancy->id)->get();
           $vacancyStages = [
               [
                   'name' => 'Все',
                   'count' => $candidates->count()
               ]
           ];
            $stagesDefault = Stage::where('fixed', 1)->get();
            foreach ($stagesDefault as $stage) {
                $count = $stage->countVacancyCandidates($vacancy->id);
                if ($count) {
                    $vacancyStages[] = [
                        'name' => $stage->name,
                        'count' => $count
                    ];
                }
            }
            $stagesUser = FunnelStage::where('customer_id', $vacancy->customer_id)->pluck('stage_id')->toArray();
            $stagesUser = Stage::find($stagesUser);
            foreach ($stagesUser as $stage) {
                $count = $stage->countVacancyCandidates($vacancy->id);
                if ($count) {
                    $vacancyStages[] = [
                        'name' => $stage->name,
                        'count' => $count
                    ];
                }
            }

            $vacancy->footerData = [
                'sites' => 0,
                'responsible' => $responsible,
                'itemId' => $vacancy->id . ' ID'
            ];
            $vacancy->stages = $vacancyStages;
            unset($vacancy->executor_id);
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
            $conditions = ConditionVacancy::all()->where('vacancy_id', $id)->pluck(['condition_id']);
            $conditions = Condition::whereIn('id', $conditions)->get();
            $vacancy['conditions'] = $conditions;

            $drivers = DriverVacancy::all()->where('vacancy_id', $id)->pluck(['driver_id']);
            $drivers = Driver::whereIn('id', $drivers)->get();
            $vacancy['drivers'] = $drivers;

            $additions = AdditionVacancy::all()->where('vacancy_id', $id)->pluck(['addition_id']);
            $additions = Addition::whereIn('id', $additions)->get();
            $vacancy['additions'] = $additions;

            $phrases = PhraseVacancy::all()->where('vacancy_id', $id)->pluck(['phrase_id']);
            $phrases = Phrase::whereIn('id', $phrases)->get();
            $vacancy['phrases'] = $phrases;

            $vacancy['place'] = $vacancy->places;
            unset($vacancy['places']);
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
                'executor_id' => 'nullable|numeric',
                'executor_name' => 'nullable|string',
                'executor_phone' => 'nullable|regex:/^\+7\d{10}$/',
                'executor_email' => 'nullable|string'
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

        if(isset($request->place)) {
            $place = Place::all()->find($request->place);
            if (!empty($place)) {
                $data['places'] = $request->place;
            }
            unset($data['place']);
        }

        $data['customer_id'] = $request->attributes->get('customer_id');
        $data['status'] = 'active';

        try {
            $vacancy = Vacancy::create($data);
        } catch (\Throwable $th) {
            return response()->json([
                'massage' => 'Ошибка создания вакансии ' . $request->name
            ], 500);
        }

        if(isset($request->place)) {
            $place = Place::all()->find($request->place);
            if (!empty($place)) {
                $vacancy->places = $request->place;
            }
            unset($data['place']);
        }

        if(isset($request->conditions)) {
            $vacancy->conditions()->attach($request->conditions);
            $conditions = Condition::whereIn('id', $request->conditions)->get();
            $vacancy->conditions = $conditions->toArray();
        }
        if(isset($request->additions)) {
            $vacancy->additions()->attach($request->additions);
            $drivers = Driver::whereIn('id', $request->drivers)->get();
            $vacancy->drivers = $drivers;
        }
        if(isset($request->drivers)) {
            $vacancy->drivers()->attach($request->drivers);
            $additions = Addition::whereIn('id', $request->additions)->get();
            $vacancy->additions = $additions;
        }
        if (isset($request->phrases)) {
            $vacancy->phrases()->attach($request->phrases);
            $phrases = Phrase::whereIn('id', $request->phrases)->get();
            $vacancy->phrases = $phrases->toArray();
        }

        $place = Place::find($vacancy->places);
        $vacancy->place = $place;
        $vacancy->makeHidden('places');

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
                    'status' => 'nullable|string|in:active,draft,archive'
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
                    $vacancy->conditions()->detach();
                    $vacancy->conditions()->sync($relatedFields);
                    $conditions = Condition::whereIn('id', $relatedFields)->get();
                } else {
                    $conditions = ConditionVacancy::all()->where('vacancy_id', $id)->pluck(['condition_id']);
                    $conditions = Condition::whereIn('id', $conditions)->get();
                }
                    $vacancy['conditions'] = $conditions;

                if (isset($request->drivers)) {
                    $relatedFields = array_map(fn($el) => intval($el), $request->drivers);
                    $vacancy->drivers()->detach();
                    $vacancy->drivers()->sync($relatedFields);
                    $drivers = Driver::whereIn('id', $relatedFields)->get();
                } else {
                    $drivers = DriverVacancy::all()->where('vacancy_id', $id)->pluck(['driver_id']);
                    $drivers = Driver::whereIn('id', $drivers)->get();
                }
                $vacancy['drivers'] = $drivers;

                if (isset($request->additions)) {
                    $relatedFields= array_map(fn($el) => intval($el), $request->additions);
                    $vacancy->additions()->detach();
                    $vacancy->additions()->sync($relatedFields);
                    $additions = Addition::whereIn('id', $relatedFields)->get();
                } else {
                    $additions = DriverVacancy::all()->where('vacancy_id', $id)->pluck(['addition_id']);
                    $additions = Driver::whereIn('id', $additions)->get();
                }
                $vacancy['additions'] = $additions;

                if (isset($request->phrases)) {
                    $phrases= array_map(fn($el) => intval($el), $request->phrases);
                    $vacancy->phrases()->detach();
                    $vacancy->phrases()->sync($phrases);
                    $phrases = Phrase::whereIn('id', $phrases)->get();
                } else {
                    $phrases = PhraseVacancy::all()->where('vacancy_id', $id)->pluck(['phrase_id']);
                    $phrases = Phrase::whereIn('id', $phrases)->get();
                }
                $vacancy['phrases'] = $phrases;
            } catch (\Throwable $th) {

                return response()->json([
                    'message' => 'Ошибка обновления связаных данных',
                    ], 409);
            }

//            $vacancy = Vacancy::with(['conditions', 'drivers', 'additions'])->find($vacancy->id);

            return response()->json([
                'message' => 'Вакансия ' . $vacancy->name . ' успешно обновлена',
                'data' => $vacancy
            ]);
        } else {
            return response()->json([
                'message' => 'Вакансия не найдена'
            ], 404);
        }
    }
}
