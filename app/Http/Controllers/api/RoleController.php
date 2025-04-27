<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

class RoleController extends Controller
{
    private array $permissionsDb = [
        'isLook' => 1,
        'isManage' => 2,
        'isDeleteVacancy' => 3,
        'isChangePerson' => 4,
        'isInviteCustomer' => 5,
        'isDeleteCandidate' => 6,
        'isManageEmailTemplate' => 7,
        'isManageTag' => 8,
        'isReceiveVacancy' => 9,
        ];
    public function index(Request $request): JsonResponse
    {
        $roles = Role::all()->toArray();

        return response()->json([
            'message' => 'Success',
            'data' => $roles
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::find($id);
        if ($id == 1) {
            $permissions = Permission::all()->map(function ($permission) {
                $permission->value = 1;
                return $permission;
            });
            $role['permissions'] = $permissions;
        } else {

        }


        if (is_null($role)) {
            return response()->json([
                'message' => 'Роли с id = ' . $id . ' не существует'
            ], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $role
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:50',
                'isLook' => 'required|boolean',
                'isManage' => 'required|boolean',
                'isDeleteVacancy' => 'required|boolean',
                'isChangePerson' => 'required|boolean',
                'isInviteCustomer' => 'required|boolean',
                'isDeleteCandidate' => 'required|boolean',
                'isManageEmailTemplate' => 'required|boolean',
                'isManageTag' => 'required|boolean',
                'isReceiveVacancy' => 'required|boolean',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $role = Role::where('name', $data['name'])->get();
        if (!$role->isEmpty()) {
            return response()->json([
                'message' => 'Роль ' . $data['name'] . ' уже существует',
            ], 409);
        }

        $permissions = [];
        foreach ($this->permissionsDb as $key => $permission) {
            $permissions[$permission] = ['value' => $data[$key]];
        }
        $role = Role::create($data);
        $role->permissions()->attach($permissions);

        return response()->json([
            'message' => 'Роль ' . $data['name'] . ' успешно создана'
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);
        if (is_null($role)) {
            return response()->json([
                'message' => 'Роли с id = ' . $id . ' не существует'
            ], 404);
        }

        try {
            $data = $request->validate([
                'name' => 'string|min:3|max:50',
                'isLook' => 'boolean',
                'isManage' => 'boolean',
                'isDeleteVacancy' => 'boolean',
                'isChangePerson' => 'boolean',
                'isInviteCustomer' => 'boolean',
                'isDeleteCandidate' => 'boolean',
                'isManageEmailTemplate' => 'boolean',
                'isManageTag' => 'boolean',
                'isReceiveVacancy' => 'boolean',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        if (empty($data)) {
            return response()->json([
                'message' => 'Данные для обновления пусты'
            ], 204);
        }

        $rolePermissions = $role->permissions()->withPivot('value')->get('name');

//        $role->permissions = $rolePermissions;
//        var_dump($rolePermissions);




//        $permissions = PermissionRole::all()->where('role_id', $id)->pluck('permission_id');
//        $permissions = Permission::whereIn('id', $permissions)->get();
//
//        var_dump($permissions);


//        foreach ($this->permissionsDb as $key => $permission) {
//            if (isset($data[$key])) {
//                if (in_array($permission, $rolePermissions)) {
//                    $role->permissions()->updateExistingPivot($permission, ['value' => $data[$key]]);
//                } else {
//                    $role->permissions()->attach([$permission, ['value' => $data[$key]]]);
//                }
//            }
//        }

        if ($role->name != $data['name']) {
            $roleName = Role::where('name', $data['name'])->get();
            if (!$roleName->isEmpty()) {
                return response()->json([
                    'massage' => 'Роль с именованием ' . $data['name'] . ' уже существует'
                ], 409);
            }
            $role->update($data);
        }


        return response()->json([
            'message' => 'Роль обновлена',
            'data' => $role
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $role = Role::find($id);
        if (is_null($role)) {
            return response()->json([
                'message' => 'Роли с id = ' . $id . ' не существует'
            ], 404);
        }

        if ($id == 1) {
            return response()->json([
                'message' => 'Роли Администратор удалять запрещено'
            ], 409);
        }

        $customers = Customer::where('role_id', $id)->get();
        if (!$customers->isEmpty()) {
            return response()->json([
                'message' => 'Роль ' . $role->name . ' удалять нельзя, есть пользователи с этой ролью'
            ], 409);
        }

//        $role->delete();

        return response()->json([
            'message' => 'Роль ' . $role->name . ' успешно удалена'
        ]);
    }
}
