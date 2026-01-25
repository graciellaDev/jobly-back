<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Addition;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\ConditionVacancy;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerDepartment;
use App\Models\CustomerRelation;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\String\b;

class VacancyController extends Controller
{
    private $statuses = [
        'active',
        'draft',
        'archive',
    ];

    private array $sort = [
        'asc',
        'desc',
        'new',
        'old',
        'urgent',
        'non-urgent'
    ];

    private array $filters = [
        'status',
        'city',
        'executor',
        'client',
        'id',
        'notCandidate',
        'isApplication',
        'notExecutor',
        'responsible',
        'create',
        'department',
        'platforms',
        'baseVacancyId',
    ];

    private array $validRole = [1, 3, 5];

    public function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $sort = $request->get('sort');
        $sort = !empty($sort) && in_array($sort, $this->sort) ? $sort : null;
        $filters = $request->get('filters');
        $needPlatforms = !empty($filters['baseVacancyId']); // Флаг для загрузки платформ

        $customer = Customer::find($customerId);

        $arrUsers = [$customerId];
        $arrVacancies = [];

        if ($customer) {
            if ($customer->role_id == CustomerController::$roleAdmin) {
                $users = CustomerRelation::where('user_id', $customerId)->pluck('customer_id')->toArray();
                if (count($users)) {
                    $arrUsers = array_merge($arrUsers, $users);
                }
            }
            if ($customer->role_id == CustomerController::$roleRecruiter) {
                $application = Application::with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                    ->where('responsable_id', $customerId)
                    ->whereNotNull('vacancy_id')
                    ->pluck('vacancy_id')
                    ->toArray();
                if (count($application)) {
                    $arrVacancies = $application;
                }
            }
        }

        //        $vacancies = Vacancy::with('clients')->whereIn('customer_id', $arrUsers);
//        $vacancies = Vacancy::with('platforms')->whereIn('customer_id', $arrUsers);


        $vacancies = Vacancy::whereIn('customer_id', $arrUsers);

        if (count($arrVacancies)) {
            $vacancies->orWhereIn('id', $arrVacancies);
        }

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                switch ($key) {
                    case $this->filters[0]:
                        if (is_string($value) && in_array($value, $this->statuses)) {
                            $vacancies->where($key, $value);
                        }
                        break;
                    case $this->filters[1]:
                        $vacancies->where('location', 'like', "$value%");
                        break;
                    case $this->filters[2]:
                        if (!empty($value)) {
                            $vacancies->where('executor_id', $value);
                        }
                        break;
                    case $this->filters[3]:
                        $isApplications = Application::where('customer_id', $value)
                            ->whereNotNull('vacancy_id')
                            ->select('vacancy_id')
                            ->pluck('vacancy_id')
                            ->toArray();
                        if (count($isApplications)) {
                            $vacancies = $vacancies->whereIn('id', $isApplications);
                        } else {
                            $vacancies = $vacancies->where('id', 0);
                        }
                        break;
                    case $this->filters[4]:
                        if (!empty($value)) {
                            $vacancies->where('id', $value);
                        }
                        break;
                    case $this->filters[5]:
                        if ($value == 'true') {
                            $notCandidate = Candidate::whereNotNull('vacancy_id')->select('vacancy_id')->pluck('vacancy_id')->toArray();
                            if (count($notCandidate)) {
                                $vacancies->whereNotIn('id', $notCandidate);
                            }
                        }
                        break;
                    case $this->filters[6]:
                        if ($value == 'true') {
                            $applications = Application::whereNotNull('vacancy_id')
                                ->select('vacancy_id')
                                ->pluck('vacancy_id')
                                ->toArray();
                            if (count($applications)) {
                                $vacancies->whereIn('id', $applications);
                            }
                        }
                        break;
                    case $this->filters[7]:
                        if ($value == 'true') {
                            $vacancies->whereNull('executor_id');
                        }
                        break;
                    case $this->filters[8]:
                        $applications = Application::where('responsible_id', $value)
                            ->select('vacancy_id')
                            ->pluck('vacancy_id')
                            ->toArray();
                        $vacancies->whereIn('id', $applications);
                        break;
                    case $this->filters[9]:
                        $dates = explode(';', $value);
                        if (count($dates) == 2) {
                            $dateFrom = Carbon::createFromFormat('d.m.Y', $dates[0])->startOfDay();
                            $dateTo = Carbon::createFromFormat('d.m.Y', $dates[1])->endOfDay();
                            $vacancies->whereBetween('created_at', [$dateFrom, $dateTo]);
                        }
                        break;
                    case $this->filters[10]:
                        $customers = CustomerDepartment::where('department_id', $value)->pluck('customer_id')->toArray();
                        break;
                    case $this->filters[11]:
                        if (!empty($value)) {
                            $platformId = (int) $value;
                            $vacancyIds = DB::table('vacancy_platform')
                                ->where('platform_id', $platformId)
                                ->pluck('vacancy_id')
                                ->toArray();
                            if (count($vacancyIds)) {
                                $vacancies->whereIn('id', $vacancyIds);
                            } else {
                                $vacancies->where('id', 0);
                            }
                        } else {
                            $vacancies->whereHas('platforms');
                        }
                        break;
                    case $this->filters[12]:
                        // Фильтр по базовой вакансии: выбираем только вакансии, привязанные к указанной базовой вакансии и имеющие платформу
                        if (!empty($value)) {
                            $baseVacancyId = (int) $value;
                            $vacancies->forBaseVacancyWithPlatform($baseVacancyId);
                        }
                        break;
                }
            }
        }

        $vacancies->select([
            'id',
            'name as title',
            'location as city',
            'executor_id',
            'customer_id',
            'created_at',
            'dateEnd'
        ]);
        if (!empty($sort)) {
            if ($sort == 'asc' || $sort == 'desc') {
                $vacancies->orderBy('title', $sort);
            }
            if ($sort == 'new' || $sort == 'old') {
                $typeSort = $sort == 'new' ? 'desc' : 'asc';
                $vacancies->orderBy('created_at', $typeSort);
            }
            if ($sort == 'urgent' || $sort == 'non-urgent') {
                $typeSort = $sort == 'urgent' ? 'asc' : 'desc';
                $vacancies->orderBy('dateEnd', $typeSort);
            }
        }
        $vacancies = $vacancies->paginate();
        
        // Загружаем платформы после пагинации, если используется фильтр baseVacancyId
        if ($needPlatforms && !$vacancies->isEmpty()) {
            $this->loadPlatformsForVacancies($vacancies);
        }
        
        $vacancies->getCollection()->transform(function ($vacancy) use ($customerId, $needPlatforms) {
            $responsible = 'Не назначен';
            if (!empty($vacancy->executor_id)) {
                $responsible = Customer::select(['id', 'name'])->find($vacancy->executor_id);
            }
            $candidates = Candidate::where('vacancy_id', $vacancy->id)->where('customer_id', $customerId)->get();
            if (!$candidates || !($candidates instanceof \Illuminate\Support\Collection)) {
                $candidates = collect([]);
            }
            $vacancyStages = [
                [
                    'name' => 'Все',
                    'count' => $candidates->count()
                ]
            ];
            $stagesDefault = Stage::where('fixed', 1)->get();
            foreach ($stagesDefault as $stage) {
                $count = $stage->countVacancyCandidates($vacancy->id, $customerId);
                $vacancyStages[] = [
                    'name' => $stage->name,
                    'count' => $count
                ];
            }
            $stagesUser = FunnelStage::where('customer_id', $vacancy->customer_id)->pluck('stage_id')->toArray();
            $stagesUser = Stage::find($stagesUser);
            foreach ($stagesUser as $stage) {
                if ($stage) {
                    $count = $stage->countVacancyCandidates($vacancy->id, $customerId);
                    if ($count) {
                        $vacancyStages[] = [
                            'name' => $stage->name,
                            'count' => $count
                        ];
                    }
                }
            }
            // Обработка платформ: добавляем информацию о платформе и ID на сторонней платформе только для фильтра baseVacancyId
            $this->processPlatformsForVacancy($vacancy, $needPlatforms);

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

    /**
     * Загружает платформы для вакансий и добавляет их во временное свойство _platforms_temp
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $vacancies
     * @return void
     */
    private function loadPlatformsForVacancies($vacancies): void
    {
        $vacancyIds = $vacancies->getCollection()->pluck('id')->toArray();
        if (empty($vacancyIds)) {
            $vacancyIds = [];
        }
        
        $platformsData = DB::table('vacancy_platform')
            ->whereIn('vacancy_id', $vacancyIds)
            ->join('platforms', 'vacancy_platform.platform_id', '=', 'platforms.id')
            ->select(
                'vacancy_platform.vacancy_id',
                'platforms.id as platform_id',
                'platforms.name as platform_name',
                'vacancy_platform.vacancy_platform_id',
                'vacancy_platform.base_vacancy_id'
            )
            ->get()
            ->groupBy('vacancy_id');
        
        // Добавляем платформы к каждой вакансии (временно для обработки в transform)
        $vacancies->getCollection()->each(function ($vacancy) use ($platformsData) {
            $vacancy->_platforms_temp = collect($platformsData->get($vacancy->id, collect()))->map(function ($item) {
                return (object) [
                    'id' => $item->platform_id,
                    'name' => $item->platform_name,
                    'pivot' => (object) [
                        'vacancy_platform_id' => $item->vacancy_platform_id,
                        'base_vacancy_id' => $item->base_vacancy_id,
                    ]
                ];
            });
        });
    }

    /**
     * Обрабатывает платформы для вакансии в зависимости от флага needPlatforms
     *
     * @param mixed $vacancy
     * @param bool $needPlatforms
     * @return void
     */
    private function processPlatformsForVacancy($vacancy, bool $needPlatforms): void
    {
        if ($needPlatforms) {
            if (isset($vacancy->_platforms_temp) && $vacancy->_platforms_temp instanceof \Illuminate\Support\Collection) {
                // Платформы уже загружены через DB запрос (для фильтра baseVacancyId)
                if ($vacancy->_platforms_temp->isNotEmpty()) {
                    $vacancy->platforms_data = $vacancy->_platforms_temp->map(function ($platform) {
                        return [
                            'id' => $platform->id ?? null,
                            'name' => $platform->name ?? null,
                            'platform_id' => $platform->pivot->vacancy_platform_id ?? null, // ID вакансии на сторонней платформе
                            'base_vacancy_id' => $platform->pivot->base_vacancy_id ?? null, // ID базовой вакансии в нашей системе
                        ];
                    })->toArray();
                } else {
                    $vacancy->platforms_data = [];
                }
                unset($vacancy->_platforms_temp);
            } else {
                // Если платформы не загружены, загружаем их для текущей вакансии
                $platforms = $vacancy->platforms()->withPivot('base_vacancy_id', 'vacancy_platform_id')->get();
                if ($platforms->isNotEmpty()) {
                    $vacancy->platforms_data = $platforms->map(function ($platform) {
                        return [
                            'id' => $platform->id ?? null,
                            'name' => $platform->name ?? null,
                            'platform_id' => $platform->pivot->vacancy_platform_id ?? null,
                            'base_vacancy_id' => $platform->pivot->base_vacancy_id ?? null,
                        ];
                    })->toArray();
                } else {
                    $vacancy->platforms_data = [];
                }
            }
            // Убираем platforms из ответа, если используется фильтр
            unset($vacancy->platforms);
        } else {
            // Для обычных запросов оставляем стандартную обработку платформ
            if ($vacancy->relationLoaded('platforms') && !empty($vacancy->platforms)) {
                $vacancy->platforms = $vacancy->platforms->toArray();
            }
        }
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
        $vacancy = Vacancy::where('customer_id', $customerId);
        $user = CustomerRelation::where('customer_id', $customerId)->pluck('user_id')->first();
        if (!empty($user)) {
            $vacancy->orWhere('customer_id', $user);
        }
        $vacancy = $vacancy->find($id);
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

        return response()->json([
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
                'description' => 'required|string|min:3',
                'dateEnd' => 'nullable|date_format:d.m.Y',
                'code' => 'nullable|string|max:255',
                'specializations' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'employment' => 'nullable|string|max:255',
                'schedule' => 'nullable|string|max:255',
                'experience' => 'nullable|string|max:255',
                'education' => 'nullable|string|max:255',
                'salary_type' => 'nullable|string|max:100',
                'salary_from' => 'nullable|string|max:255',
                'salary_to' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'place' => 'nullable|numeric|max:255',
                'location' => 'nullable|string|max:255',
                'executor_id' => 'nullable|numeric',
                'executor_name' => 'nullable|string',
                'executor_phone' => 'nullable|regex:/^\+7\d{10}$/',
                'executor_email' => 'nullable|string',
                'show_executor' => 'nullable|boolean',
                'platform_id' => 'nullable|numeric',
                'base_id' => 'nullable|numeric',
                'status' => 'nullable|string'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        //        $isExists = null;
//        if (!empty($request->code)) {
//            $isExists = Vacancy::where('code', $request->code)->where('customer_id', $customerId)->exists();
//        }
//
//        if ($isExists) {
//            return response()->json([
//                'massage' => 'Вакансия с кодом ' . $request->code . ' уже существует'
//            ], 409);
//        }

        if (isset($request->place)) {
            $place = Place::all()->find($request->place);
            if (!empty($place)) {
                $data['places'] = $request->place;
            }
            unset($data['place']);
        }

        if (isset($request->dateEnd)) {
            $data['dateEnd'] = Carbon::createFromFormat('d.m.Y', $data['dateEnd']);
        }

        $data['customer_id'] = $request->attributes->get('customer_id');
        if  (!isset($data['platform_id'])) {
            $data['status'] = 'active';
        }

        if ($request->application) {
            $application = Application::with('status')->find($request->application);
            if (!empty($application)) {
                if ($application->status->name == 'На рассмотрении') {
                    $customer = Customer::find($customerId);
                    if ($customer->role_id == CustomerController::$roleAdmin) {
                        $application->status_id = 2;
                        $application->save();
                    }
                    if ($customer->role_id == CustomerController::$roleRecruiter) {
                        if ($application->responsible_id == $customerId) {
                            $application->status_id = 2;
                            $application->save();
                        }
                    }
                }
            }
        }

        unset($data['platform_id']);
        unset($data['base_id']);

        try {
            $vacancy = Vacancy::create($data);
            if (isset($application) && $application->status_id == 2) {
                $application->vacancy_id = $vacancy->id;
                $application->save();
            }
        } catch (\Throwable $th) {
            return response()->json([
                'massage' => 'Ошибка создания вакансии ' . $request->name
            ], 500);
        }

        if (!empty($request->platform_id) && !empty($request->base_id)) {
            // Проверяем, существует ли уже связь
            $exists = $vacancy->platforms()->where('platform_id', $request->platform_id)->exists();

            if ($exists) {
                // Обновляем существующую
                $vacancy->platforms()->updateExistingPivot($request->platform_id, [
                    'base_vacancy_id' => $request->base_id
                ]);
            } else {
                // Создаём новую связь с дополнительным полем
                $vacancy->platforms()->attach($request->platform_id, [
                    'base_vacancy_id' => $request->base_id
                ]);
            }
        }

        if (isset($request->place)) {
            $place = Place::all()->find($request->place);
            if (!empty($place)) {
                $vacancy->places = $request->place;
            }
            unset($data['place']);
        }

        if (isset($request->conditions)) {
            $vacancy->conditions()->attach($request->conditions);
            $conditions = Condition::whereIn('id', $request->conditions)->get();
            $vacancy->conditions = $conditions->toArray();
        }
        if (isset($request->additions)) {
            $vacancy->additions()->attach($request->additions);
            $drivers = Driver::whereIn('id', $request->drivers)->get();
            $vacancy->drivers = $drivers;
        }
        if (isset($request->drivers)) {
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

    public function delete(Request $request, int $id)
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

    public function update(Request $request, int $id): mixed
    {
        $customerId = $request->attributes->get('customer_id');
        $vacancy = Vacancy::where('customer_id', $customerId)->find($id);

        if (!empty($vacancy)) {
            try {
                $data = $request->validate([
                    'name' => 'nullable|string|min:3|max:255',
                    'description' => 'nullable|string|min:3',
                    'code' => 'nullable|string|max:255',
                    'specializations' => 'nullable|string|max:255',
                    'industry' => 'nullable|string|max:255',
                    'employment' => 'nullable|string|max:255',
                    'schedule' => 'nullable|string|max:255',
                    'experience' => 'nullable|string|max:255',
                    'education' => 'nullable|string|max:255',
                    'drivers' => 'nullable',
                    'salary_from' => 'nullable|string|max:255',
                    'salary_to' => 'nullable|string|max:255',
                    'salary_type' => 'nullable|string|max:100',
                    'currency' => 'nullable|string|max:255',
                    'place' => 'nullable|string|max:255',
                    'location' => 'nullable|string|max:255',
                    'status' => 'nullable|string|in:active,draft,archive',
                    'executor_id' => 'nullable|numeric',
                    'executor_name' => 'nullable|string',
                    'executor_phone' => 'nullable|regex:/^\+7\d{10}$/',
                    'executor_email' => 'nullable|string',
                    'show_executor' => 'nullable|boolean',
                    'role_id' => 'nullable|numeric',
                    'customer_role' => 'nullable|string',
                    'conditions' => 'nullable',
                    'additions' => 'nullable',
                    'phrases' => 'nullable',
                    'dateEnd' => 'nullable|date_format:d.m.Y',
                ]);
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => 'Ошибка валидации',
                ], 422);
            }

            if (isset($data['role_id']) && !in_array($data['role_id'], $this->validRole)) {
                return response()->json([
                    'message' => 'Недопустимое значение роли',
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

            if (isset($request->place)) {
                $place = Place::all()->find(intval($request->place));
                if (!empty($place)) {
                    $vacancy->places = $request->place;
                }
            }

            if (isset($data['role_id'])) {
                if ($data['role_id'] == 1 && isset($data['customer_role'])) {
                    $customerRoleId = intval($data['customer_role']);
                    if ($customerRoleId > 0) {
                        $exists = DB::table('coordinating_vacancy')
                            ->where('vacancy_id', $id)
                            ->where('customer_id', $customerRoleId)
                            ->exists();
                        if (!$exists) {
                            $vacancy->coordinators()->attach($customerRoleId);
                        }
                    }
                    unset($data['customer_role']);
                }

                if ($data['role_id'] == 3 && isset($data['customer_role'])) {
                    $customerRoleId = intval($data['customer_role']);
                    if ($customerRoleId > 0) {
                        $data['executor_id'] = $customerRoleId;
                    }
                    unset($data['customer_role']);
                }

                if ($data['role_id'] == 5 && isset($data['customer_role'])) {
                    $customerRoleId = intval($data['customer_role']);
                    if ($customerRoleId > 0) {
                        $exists = DB::table('client_vacancy')
                            ->where('vacancy_id', $id)
                            ->where('customer_id', $customerRoleId)
                            ->exists();
                        if (!$exists) {
                            $vacancy->clients()->attach($customerRoleId);

                        }
                    }
                    unset($data['customer_role']);
                }
                unset($data['role_id']);
            }

            if (empty($data)) {
                return response()->json([
                    'message' => 'Нет данных для обновления',
                ], 422);
            }

            if (isset($request->dateEnd)) {
                if (!empty($request->dateEnd)) {
                    $data['dateEnd'] = Carbon::createFromFormat('d.m.Y', $data['dateEnd'])->format('Y-m-d');
                } else {
                    $data['dateEnd'] = null;
                }
            }

            $vacancy->update($data);

            $place = Place::find($vacancy->places);
            $vacancy->place = $place;
            $vacancy->makeHidden('places');

            try {
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
                    $relatedFields = array_map(fn($el) => $el['id'], $request->drivers);
                    $vacancy->drivers()->detach();
                    $vacancy->drivers()->sync($relatedFields);
                    $drivers = Driver::whereIn('id', $relatedFields)->get();
                } else {
                    $drivers = DriverVacancy::all()->where('vacancy_id', $id)->pluck(['driver_id']);
                    $drivers = Driver::whereIn('id', $drivers)->get();
                }
                $vacancy['drivers'] = $drivers;

                if (isset($request->additions)) {
                    $relatedFields = array_map(fn($el) => intval($el), $request->additions);
                    $vacancy->additions()->detach();
                    $vacancy->additions()->sync($relatedFields);
                    $additions = Addition::whereIn('id', $relatedFields)->get();
                } else {
                    $additions = DriverVacancy::all()->where('vacancy_id', $id)->pluck(['addition_id']);
                    $additions = Driver::whereIn('id', $additions)->get();
                }
                $vacancy['additions'] = $additions;

                if (isset($request->phrases)) {
                    $phrases = array_map(fn($el) => intval($el), $request->phrases);
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
