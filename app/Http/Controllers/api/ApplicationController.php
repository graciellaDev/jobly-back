<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Status;
use App\Models\Vacancy;
use App\Models\Application;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
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
        'status' => 'nullable',
        'executor' => 'nullable',
        'client' => 'nullable'
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

    protected int $roleId = 5;
    public function index(Request $request)
    {
        $customerId = $request->attributes->get('customer_id');
        $sort = $request->get('sort');

        if (!empty($sort) && in_array($sort, $this->validSort)) {
            $asc = $request->get('asc') === '0' ? 'desc' : 'asc';
            if ($sort == 'dateStart' || $sort == 'dateWork') {
                $applications = Application::where('customer_id', $customerId)->select([
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
                $applications = Application::where('customer_id', $customerId)->select([
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
                    ->join($table, "$table.id", '=', "applications.$tableCol")
                    ->orderBy("$table.name", $asc)
                    ->with(['client', 'vacancy', 'status', 'executor', 'resposible'])
                    ->paginate(10);
            }
        } else {
            $applications = Application::where('customer_id', $customerId)->select([
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

    public function show(Request $request, int $id)
    {
        $customerId = $request->attributes->get('customer_id');
        $application = Application::with(['client', 'vacancy', 'status', 'executor', 'reponsible'])
            ->where('customer_id', $customerId)
            ->find($id);

        if (empty($application)) {
            return response()->json([
                'message' => 'Заявка с id = ' . $id . ' не найдена'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $application
        ]);
    }

    public function create(Request $request)
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

        if (isset($data['status'])) {
            $status = Status::find(intval($data['status']));
            if (!empty($vacancy)) {
                $data['status_id'] = $status->id;
            }
        }

        if (isset($data['client'])) {
            $client = Client::where('role_id', $this->roleId)->find(intval($data['client']));
            if (!empty($client)) {
                $data['client_id'] = $client->id;
            }
        }

        if (isset($data['executor'])) {
            $executor = Customer::where('role_id', 4)->find(intval($data['executor']));
            if (!empty($executor)) {
                $data['executor_id'] = $executor->id;
            }
        }

        try {
            $application = Application::create($data);
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

        return response()->json([
            'message' => 'Заявка успешно ' . $data['position'] . ' создана',
            'data' => $application
        ]);

    }

    public function update(Request $request, int $id)
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

        $application = Application::with(['client', 'vacancy', 'status', 'executor'])->find($id);
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
            $client = Client::where('role_id', $this->roleId)->find(intval($data['client']));
            if (!empty($client)) {
                $data['client_id'] = $client->id;
            }
        }

        if (isset($data['executor'])) {
            $executor = Customer::where('role_id', 4)->find(intval($data['executor']));
            if (!empty($executor)) {
                $data['executor_id'] = $executor->id;
            }
        }

        $application->update($data);

        return response()->json([
            'message' => 'Заявка обновлена',
            'data' => $application
        ]);
    }

    public function delete(Request $request, int $id)
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
}
