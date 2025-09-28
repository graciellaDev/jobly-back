<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Department;
use App\Models\DepartmentDivision;
use App\Models\CustomerDepartment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $departments = CustomerDepartment::where('customer_id', $customerId)->pluck('department_id');
        $departments = Department::whereIn('id', $departments)->with('divisions')->select(['id', 'name'])->get();

        return response()->json([
            'data' => $departments->toArray(),
        ]);
    }

    public function show($departmentId): JsonResponse
    {
        try {
            $department = Department::with('divisions')->findOrFail($departmentId);

            return response()->json([
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'divisions' => $department->divisions->map(function ($division) {
                        return [
                            'id' => $division->id,
                            'name' => $division->name,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка получения департамента: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Поле название обязательно для заполнения ',
            ], 422);
        }


        try {
            $customerId = $request->attributes->get('customer_id');
            $customer = Customer::findOrFail($customerId);

            $department = Department::create([
                'name' => $request->name,
            ]);

            // Привязываем департамент к клиенту
            $customer->departments()->attach($department->id);

            return response()->json([
                'success' => true,
                'message' => 'Департамент ' . $request->name . ' успешно создан',
                'department' => [
                    'id' => $department->id,
                    'name' => $department->name,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания департамента: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createDivision(Request $request, $departmentId): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации: ' . $th->getMessage(),
            ], 422);
        }

        try {
            $department = Department::findOrFail($departmentId);

            if (!$department) {
                return response()->json([
                    'message' => 'Департамент не найден',
                ], 404);
            }

            $division = DepartmentDivision::where('division', $request->name)->first();
            if ($division) {
                return response()->json([
                    'message' => 'Отдел с таким названием уже существует',
                ], 409);
            }

            $division = DepartmentDivision::create([
                'department_id' => $department->id,
                'division' => $request->name,
            ]);

            return response()->json([
                'message' => 'Отдел ' . $request->name . ' успешно создан',
                'division' => $division,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка создания отдела: ' . $e->getMessage(),
            ], 500);
        }
    }
}
