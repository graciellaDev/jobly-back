<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\Controller;
use App\Models\Approve;
use App\Models\Customer;
use App\Models\CustomerRelation;
use App\Models\Role;
use App\Models\Status;
use App\Models\Vacancy;
use App\Models\Application;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\application\InviteVacancy;

class ApplicationController extends Controller
{
    private array $roleExecutors = [3, 4];
    private int $roleManager = 4;
    private static $defaultStatus = 1;
    private array $validFields = [
        'position' => 'required|string|min:3|max:50',
        'division' => 'string|min:3|max:50',
        'count' => 'nullable|numeric',
        'salaryFrom' => 'nullable|numeric',
        'salaryTo' => 'nullable|numeric',
        'currency' => 'nullable|string|min:2|max:200',
        'require' => 'nullable|string|min:3|max:200',
        'duty' => 'nullable|string|min:3|max:200',
        'city' => 'nullable|string|min:3|max:200',
        'reason' => 'nullable|string|min:3|max:50',
        'dateStart' => 'nullable|date_format:d.m.Y',
        'dateWork' => 'nullable|date_format:d.m.Y',
        'vacancy' => 'nullable',
        'executor' => 'nullable',
        'client' => 'nullable',
        'responsible' => 'nullable'
    ];

    private array $validSort = [
        'dateStart',
        'dateWork',
        'status',
        'client',
        'executor'
    ];

    private array $validUpdateFields = [
        'position' => 'string|min:3|max:50',
        'division' => 'string|min:3|max:50',
        'count' => 'nullable|numeric',
        'salaryFrom' => 'nullable|numeric',
        'salaryTo' => 'nullable|numeric',
        'currency' => 'nullable|string|min:2|max:200',
        'require' => 'nullable|string|min:3|max:200',
        'duty' => 'nullable|string|min:3|max:200',
        'city' => 'nullable|string|min:3|max:200',
        'reason' => 'nullable|string|min:3|max:50',
        'dateStart' => 'nullable|date_format:d.m.Y',
        'dateWork' => 'nullable|date_format:d.m.Y',
        'vacancy' => 'nullable',
        'status' => 'nullable',
        'executor' => 'nullable',
        'client' => 'nullable',
        'responsible' => 'nullable'
    ];

    protected int $roleClient = 5;
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $whereType = 'where';
        $field = 'customer_id';
        $sort = $request->get('sort');

        $customer = Customer::find($customerId);
        if ($customer->role_id == CustomerController::$roleAdmin) {
            $usersAdmin = CustomerRelation::where('user_id', $customerId)->select(['customer_id']);
            $adminId = $customerId;
            $customerId = $usersAdmin->pluck('customer_id')->toArray();
            $customerId[] = $adminId;
            $whereType = 'whereIn';
        }

        if ($customer->role_id == CustomerController::$roleRecruiter) {
            $field = 'responsible_id';
        }

        if (!empty($sort) && in_array($sort, $this->validSort)) {
            $asc = $request->get('asc') === '0' ? 'desc' : 'asc';
            if ($sort == 'dateStart' || $sort == 'dateWork') {
                $applications = Application::{$whereType}($field, $customerId)->select([
                    'id',
                    'position',
                    'city',
                    'dateStart',
                    'dateWork',
                    'status_id',
                    'client_id',
                    'executor_id',
                    'vacancy_id',
                    'responsible_id'
                ])
                    ->orderBy($sort, $asc)
                    ->with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                    ->paginate(10);
            } else {
                $table = match ($sort) {
                    'client', 'executor' => 'customers',
                    'status' => 'statuses',
                };
                $tableCol = match ($sort) {
                    'client' => 'client_id',
                    'executor' => 'executor_id',
                    'status' => 'status_id',
                };

                $applications = Application::{$whereType}($field, $customerId)->select([
                    'applications.id',
                    'applications.position',
                    'applications.city',
                    'applications.dateStart',
                    'applications.dateWork',
                    'applications.status_id',
                    'applications.client_id',
                    'applications.executor_id',
                    'applications.vacancy_id',
                    'applications.responsible_id'
                ])
                    ->leftJoin($table, "$table.id", '=', "applications.$tableCol")
                    ->orderBy("$table.name", $asc)
                    ->with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                    ->paginate(10);
            }
        } else {
            $applications = Application::{$whereType}($field, $customerId)->select([
                'id',
                'position',
                'city',
                'dateStart',
                'dateWork',
                'status_id',
                'client_id',
                'executor_id',
                'vacancy_id',
                'responsible_id'
            ])
                ->with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                ->paginate(10);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $applications
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $customer = Customer::find($customerId);
        $application = Application::with(['client', 'vacancy', 'status', 'executor', 'responsible'])
            ->find($id);

        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ], 404);
        }

        if ($customer->role_id == CustomerController::$roleAdmin) {
            if ($customerId != $application->customer_id) {
                $usersAdmin = CustomerRelation::where('user_id', $customerId)
                    ->select(['customer_id'])
                    ->pluck('customer_id')
                    ->toArray();
                if (!in_array($application->customer_id, $usersAdmin)) {
                    return response()->json([
                        'message' => 'У вас нет доступа к заявке с id = ' . $id
                    ], 403);
                }
            }
        }

         if ($customer->role_id == CustomerController::$roleRecruiter) {
             $application = Application::with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                 ->where('responsible_id', $customerId)
                 ->orWhere('customer_id', $customerId)
                 ->find($id);
         }
        if ($customer->role_id == CustomerController::$roleClient) {
            $application = Application::with(['client', 'vacancy', 'status', 'executor', 'responsible'])
                ->where('customer_id', $customerId)
                ->find($id);

        }

        if (empty($application)) {
            return response()->json([
                'message' => 'У вас нет доступа к заявке с id = ' . $id
            ], 403);
        }
        $application['approvals'] = Approve::where('application_id', $id)->get();
        return response()->json([
            'message' => 'Success',
            'data' => $application
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');

        try {
            $data = $request->validate($this->validFields);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $data['customer_id'] = $customerId;

        if (isset($data['dateStart'])) {
            $data['dateStart'] = Carbon::createFromFormat('d.m.Y', $data['dateStart']);
        }

        if (isset($data['dateWork'])) {
            $data['dateWork'] = Carbon::createFromFormat('d.m.Y', $data['dateWork']);
        }

        if (isset($data['vacancy'])) {
            $vacancy = Vacancy::find(intval($data['vacancy']));
            if (!empty($vacancy)) {
                $data['vacancy_id'] = $vacancy->id;
            }
        }

        $data['status_id'] = self::$defaultStatus;

        if (isset($data['client'])) {
            $client = Client::where('role_id', $this->roleClient)->find(intval($data['client']));
            if (!empty($client)) {
                $data['client_id'] = $client->id;
            }
        }

        if (isset($data['executor'])) {
            $executor = Customer::whereIn('role_id', $this->roleExecutors)->find(intval($data['executor']));
            if (!empty($executor)) {
                $data['executor_id'] = $executor->id;
            }
        }

        if (isset($data['responsible'])) {
            $responsible = Customer::whereIn('role_id', $this->roleExecutors)->find(intval($data['responsible']));
            if (!empty($responsible)) {
                $data['responsible_id'] = $responsible->id;
            }
        }

        try {
            $application = Application::create($data);
            if (isset($data['responsible']) && !empty($data['responsible'])) {
                $responsible = Customer::find($data['responsible']);
                if (!empty($responsible)) {
                    $dataEmail = [
                        'email' => $responsible->email,
                        'subject' => 'Согласование вакансии job-ly.ru',
                        'position' => $data['position'],
                        'name' => $responsible->name,
                        'url' => ''
                    ];
                    Mail::to($responsible->email)->send(new InviteVacancy($dataEmail));
                }
            }

        } catch (\Throwable $th) {
            echo $th->getMessage();
            return response()->json([
                'massage' => 'Ошибка создания заявки ' . $data['position']
            ], 500);
        }

        $application['client'] = $application->client;
        $application['vacancy'] = $application->vacancy;
        $application['status'] = $application->status;
        $application['executor'] = $application->executor;
        $application['responsible'] = $application->responsible;

        return response()->json([
            'message' => 'Заявка успешно ' . $data['position'] . ' создана',
            'data' => $application
        ]);

    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');

        try {
            $data = $request->validate($this->validUpdateFields);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        if (empty($data)) {
            return response()->json([
                'message' => 'Данные для обновления пусты'
            ]);
        }

        $application = Application::with(['client', 'vacancy', 'status', 'executor', 'responsible'])->find($id);
        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ], 404);
        }

        if (isset($data['dateStart'])) {
            $data['dateStart'] = Carbon::createFromFormat('d.m.Y', $data['dateStart']);
        }

        if (isset($data['dateWork'])) {
            $data['dateWork'] = Carbon::createFromFormat('d.m.Y', $data['dateWork']);
        }

        if (isset($data['vacancy'])) {
            $vacancy = Vacancy::find(intval($data['vacancy']));
            if (!empty($vacancy)) {
                $data['vacancy_id'] = $vacancy->id;
            }
        }

        if (isset($data['status'])) {
            $status = Status::find(intval($data['status']));
            if (!empty($vacancy)) {
                $data['status_id'] = $status->id;
            }
        }

        if (isset($data['client'])) {
            $client = Client::where('role_id', $this->roleClient)->find(intval($data['client']));
            if (!empty($client)) {
                $data['client_id'] = $client->id;
            }
        }

        if (isset($data['executor'])) {
            $executor = Customer::whereIn('role_id', $this->roleExecutors)->find(intval($data['executor']));
            if (!empty($executor)) {
                $data['executor_id'] = $executor->id;
            } else {
                $executor = null;
            }
        } else {
            $executor = null;
        }

        if (isset($data['responsible'])) {
            $responsible = Customer::whereIn('role_id', $this->roleExecutors)->find(intval($data['responsible']));
            if (!empty($responsible)) {
                $data['responsible_id'] = $responsible->id;
            }
        }

        $application->update($data);


        return response()->json([
            'message' => 'Заявка обновлена',
            'data' => $application->responsible
        ]);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $application = Application::where('customer_id', $customerId)->find($id);

        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ]);
        }

        $application->delete();

        return response()->json([
            'message' => 'Заявка успешно удалена'
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $application = Application::find($id);

        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ]);
        }

        $customerId = $request->attributes->get('customer_id');
        $user = Customer::with('role')->find($customerId);

        if ($application->customer_id != $customerId) {
            $role = $user->role;
            if ($role->id != CustomerController::$roleAdmin) {
                if (
                    $role->id != CustomerController::$roleRecruiter
                    || $application->responsible_id != $customerId
                ) {
                    return response()->json([
                        'message' => 'У вас нет прав для согласования заявки'
                    ], 403);
                }
            }
        }

        if ($application->status_id == 2) {
            return response()->json([
                'message' => 'Заявка уже согласована'
            ]);
        }

        Approve::create([
            'application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'executor_id' => $customerId,
            'status_id' => 2
        ]);

        $application->status_id = 2;
        $application->save();


        return response()->json([
            'message' => 'Заявка успешно согласована',
            'data' => ['id' => '']
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $application = Application::find($id);

        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ]);
        }

        $customerId = $request->attributes->get('customer_id');
        $user = Customer::with('role')->find($customerId);

        if ($application->customer_id != $customerId) {
            $role = $user->role;
            if ($role->id != CustomerController::$roleAdmin) {
                if (
                    $role->id != CustomerController::$roleRecruiter
                    || $application->responsible_id != $customerId
                ) {
                    return response()->json([
                        'message' => 'У вас нет прав для отклонения заявки'
                    ], 403);
                }
            }
        }

        if ($application->status_id == 3) {
            return response()->json([
                'message' => 'Заявка уже отклонена'
            ]);
        }

        try {
            $data = $request->validate([
                'description' => 'required|string|min:3|max:200'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Не указано описание причины отклонения',
            ], 422);
        }

        Approve::create([
            'application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'executor_id' => $customerId,
            'description' => $data['description'],
            'status_id' => 2
        ]);

        $application->status_id = 3;
        $application->save();

        if ($application->status_id == 3) {
            return response()->json([
                'message' => 'Заявка успешно отклонена'
            ]);
        }
    }
}
