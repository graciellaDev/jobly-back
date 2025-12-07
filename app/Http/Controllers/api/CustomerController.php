<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Mail\register\Success;
use App\Mail\register\SuccessClient;
use App\Mail\register\SuccessRecruiter;
use App\Mail\register\Restore;
use App\Models\Customer;
use App\Models\CustomerRelation;
use App\Models\CustomerDepartment;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Vacancy;

class CustomerController extends Controller
{
    private array $roleExecutors = [3, 4];
    public static  int $roleAdmin = 1;
    public static int $roleManager = 4;
    public static int $roleClient = 5;

    public static int $roleRecruiter = 3;
    public static array $roleTeam = [3, 4, 5];

    public function login(Request $request): JsonResponse
    {
        $cookieAuth = $request->cookie('auth_user');

        if (isset($cookieAuth) && !empty($cookieAuth)) {
            $userAuth = Customer::with(['role'])->where('auth_token', $cookieAuth)->first();

            if (!empty($userAuth)) {
                if(!empty($userAuth->auth_time)) {
                    if ($userAuth->auth_time >= Carbon::today()) {
                        return response()->json([
                            'message' => 'Авторизация прошла успешно',
                            'user' => [
                                'login' => $userAuth->login,
                                'name' => $userAuth->name,
                                'email' => $userAuth->email,
                                'phone' => $userAuth->phone,
                                'site' => $userAuth->site,
                                'auth_token' => $userAuth->auth_token,
                                'role' => $userAuth->role ? $userAuth->role->name : ''
                            ]
                        ]);
                    }
                } else {
                    $userAuth->auth_time = Carbon::now()->addMonths(2);
                    $userAuth->save();

                    return response()->json([
                        'message' => 'Авторизация прошла успешно',
                        'user' => [
                            'login' => $userAuth->login,
                            'name' => $userAuth->name,
                            'email' => $userAuth->email,
                            'phone' => $userAuth->phone,
                            'site' => $userAuth->site,
                            'auth_token' => $userAuth->auth_token,
                            'role' => $userAuth->role ? $userAuth->role->name : ''
                        ]
                    ]);
                }
            }
        }

        try {
            $request->validate([
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $user = Customer::with('role')->where('email', $request->email)->first();
        if (empty($user)) {
            return response()->json([
                'message' => 'Неверный логин или пароль'
            ], 404);
        }
        if (Hash::check($request->password, $user->password)) {
            if (!$user->auth_token) {
                $relation = CustomerRelation::where('customer_id', $user->id)->first();
                if ($relation) {
                    $relation->status = 'active';
                    $relation->save();
                }
            }
        }
        $customHash = Str::random(16) . time();
        $token = Hash::make($customHash);
        $user->auth_token = $token;
        $user->auth_time = Carbon::now()->addMonths(2);
        $user->save();

        return response()->json([
            'message' => 'Авторизация прошла успешно',
            'user' => [
                'login' => $user->login,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'site' => $user->site,
                'auth_token' => $user->auth_token,
                'role' => $user->role ? $user->role->name : ''
            ]
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'login' => 'required|string|max:60',
                'phone' => 'regex:/^\+7\d{10}$/',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|string|min:6',
                'site' => 'nullable|string|max:50',
                'from' => 'nullable|string|max:255',
                'role_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
                'department' => 'nullable|integer',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $isUser = [
            [
                'field' => 'login',
                'value' => $request->login,
                'error' => 'Пользователь с таким логином уже существует'
            ],
            [
                'field' => 'email',
                'value' => $request->email,
                'error' => 'Пользователь с таким email уже существует'
            ],
            [
                'field' => 'phone',
                'value' => $request->phone,
                'error' => 'Пользователь с таким номером телефона уже существует'
            ],
        ];
        foreach ($isUser as $user) {
            if (isset($data[$user['field']])) {
                $users = Customer::where($user['field'], $user['value'])->first();
                if ($users) {
                    return response()->json([
                        'message' => $user['error'],
                    ], 422);
                }
            }
        }

        if (isset($request->role_id)) {
            $role = Role::find($request->role_id);
            if (empty($role)) {
                return response()->json([
                    'message' => 'Роль не найдена',
                ], 422);
            }
            $data['role_id'] = $request->role_id;
        } else {
            $data['role_id'] = self::$roleAdmin;
        }

        $data['password'] = Hash::make($request->password);
        $data['from_source'] = $data['from'];
        $user = Customer::create($data);

        if (isset($request->department)) {
            CustomerDepartment::create([
                'customer_id' => $user->id,
                'department_division_id' => $request->department
            ]);
        }

        $rootUrl = $request->root();
        $url = $rootUrl . '/reg-success/' . $user->id . '/?key=' . urldecode($user->password);
        $dataEmail = [
            'name' => $user->name,
            'url' => $url
        ];

        if (!empty($request->user_id)) {
            $dataEmail['url'] = $dataEmail['url'] . '&user_id=' . $request->user_id;
            $userInvite = Customer::find($user->id);
            $userInvite->relations()->attach($request->user_id);
            $dataEmail['login'] = $user->login;
            $dataEmail['email'] = $user->email;
            $dataEmail['password'] = $request->password;
            if ($data['role_id'] == self::$roleClient) {
                Mail::to($user->email)->send(new SuccessClient($dataEmail));
            }
            if ($data['role_id'] == self::$roleRecruiter) {
                Mail::to($user->email)->send(new SuccessRecruiter($dataEmail));
            }
        } else {
            Mail::to($user->email)->send(new SuccessClient($dataEmail));
        }

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => [
                'login' => $user->login,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'site' => $user->site,
                'from' => $user->from_source
            ]
        ]);
    }

    public function registerClient(request $request): JsonResponse
    {
        if (empty($request->email)) {
            return response()->json([
                'message' => 'Не указан email для регистрации клиента',
            ], 422);
        }

        $request->merge(['user_id' => $request->attributes->get('customer_id')]);
        $request->merge(['role_id' => self::$roleClient]);
        $password = Str::random(8);
        $request->merge(['login' => $request->email]);
        $request->merge(['password' => $password]);
        $request->merge(['password_confirmation' => $password]);
        $request->merge(['from' => 'По приглашению из платформы']);
        $frontUrl = env('APP_FRONT_URL', 'https://job-ly.ru');
        $request->merge(['site' => $frontUrl]);

        return  $this->register($request);
    }

    public function registerRecruiter(request $request): JsonResponse
    {
        if (empty($request->email)) {
            return response()->json([
                'message' => 'Не указан email для регистрации рекрутера',
            ], 422);
        }

        $request->merge(['user_id' => $request->attributes->get('customer_id')]);
        $request->merge(['role_id' => self::$roleRecruiter]);
        $request->merge(['login' => $request->email]);
        $password = Str::random(8);
        $request->merge(['password' => $password]);
        $request->merge(['password_confirmation' => $password]);
        $request->merge(['from' => 'По приглашению из платформы']);
        $request->merge(['site' => 'https://job-ly.ru']);

        return  $this->register($request);
    }

    public function regSuccess(int $id, Request $request): RedirectResponse
    {
        $today = Carbon::today()->subDays(3);
        $key = $request->get('key');

        $user = Customer::all()->find($id)->where('created_at', '>', $today)->first();
        if (!empty($user)) {
            if($key !== $user->password) {
                return redirect(env('URL_FRONT') . '/auth/?reg=error');
            }
            $customHash = Str::random(16) . time();
            $token = Hash::make($customHash);
            $user->auth_token = $token;
            $user->save();
            if ($user->role_id == self::$roleClient || $user->role_id == self::$roleRecruiter) {
                $invitedId = intval($request->get('user_id'));
                $userInvited = Customer::all()->find($invitedId);
                if (empty($userInvited)) {
                    return redirect(env('URL_FRONT') . '/auth/?reg=error');
                }
                $user->relations()->updateExistingPivot($invitedId, [
                    'status' => 'active',
                ]);
            }

            return redirect(env('URL_FRONT') . '/auth/?reg=success&key=' . $token);
        } else {
            $user = Customer::all()->find($id)->where('created_at', '<=', $today);
            if (!empty($user)) {
                $user->delete();
            }

            return redirect(env('URL_FRONT') . '/auth/?reg=error');
        }
    }

    public function restoreAccess(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:255|unique:users',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        $user = Customer::all()->where('email', $request->email)->first();
        if (empty($user))
            return response()->json([
                'message' => 'Пользователь с таким email не найден',
            ], 404);

        $rootUrl = $request->root();

        $key = urldecode($user->auth_token);
        if (empty($key)) {
            return response()->json([
                'message' => 'Пользователь не авторизован',
            ], 404);
        }
        $url = env('URL_FRONT') . '/auth/recovery/?user_id=' . $user->id . '&key=' . $key;
        $data = [
            'name' => $user->name,
            'url' => $url,
            'subject' => 'Восстановление доступа на job-ly.ru'
        ];
        Mail::to($user->email)->send(new Restore($data));

        return response()->json([
            'message' => 'На ваш email отправлено письмо с инструкцией для восстановления'
        ]);
    }

    public function restoreSuccess(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|min:6',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|string|min:6'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        if ($request->password !== $request->password_confirmation) {
            return  response()->json([
                'message' => 'Пароли не совпадают'
            ], 422);
        }
        $key = $request->key;
        $user = Customer::all()->where('auth_token',  $key)->find($id);
        if (empty($user))
            return response()->json([
                'message' => 'Ошибка аутентификации'
            ], 404);

        if ($user->auth_token !== $key)
            return response()->json([
                'message' => 'Ошибка аутентификации'
            ], 404);

        $user->password = Hash::make($request->password);
        $user->auth_time = null;
        $user->updated_at = Carbon::today();
        $user->save();

        return response()->json([
            'message' => 'Пароль успешно обновлен',
        ]);
    }

    public function getManagers(Request $request): JsonResponse
    {
        $managers = Customer::where('role_id', self::$roleManager)
            ->with('role')
            ->select(['id', 'name', 'role_id'])
            ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $managers
        ]);
    }

    public function getExecutors(Request $request): JsonResponse
    {
        $executors = Customer::whereIn('role_id', $this->roleExecutors)
            ->with('role')
            ->select(['id', 'name', 'role_id'])
            ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $executors
        ]);
    }

    public function getResponsibles(Request $request): JsonResponse
    {
        $responsibles = Customer::whereNot('role_id', self::$roleClient)
            ->with('role')
            ->select(['id', 'name', 'role_id'])
            ->get();

        return response()->json([
            'message' => 'Success',
            'data' => $responsibles
        ]);
    }

    public function getProfile(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $data = Customer::select(['name', 'email', 'role_id'])
            ->with('role')
            ->find($customerId);

        return response()->json([
            'message' => 'Success',
            'data' => $data,
        ]);
    }

    public function getTeam(Request $request, int $vacancy_id): JsonResponse
    {
        $role = $request->get('role');
        $customerId = $request->attributes->get('customer_id');
        $team = [];
        $vacancy = null;
        $customer = Customer::select('role_id')->with('role')->find($customerId);
        if ($customer->role_id != self::$roleAdmin) {
            $adminId = CustomerRelation::where('customer_id', $customerId)
                ->pluck('user_id');
            $teamIds = CustomerRelation::where('user_id', $adminId[0])
                ->pluck('customer_id')
                ->toArray();
        } else {
            $teamIds = CustomerRelation::where('user_id', $customerId)
                ->pluck('customer_id')
                ->toArray();
        }

        if ($vacancy_id) {
            $vacancy = Vacancy::find($vacancy_id);
            if (!empty($vacancy)) {
                if (in_array($vacancy->customer_id, $teamIds))
                    $team[] = $vacancy->customer_id;
                if (
                    in_array($vacancy->executor_id, $teamIds)
                    && !in_array($vacancy->executor_id, $team)
                )
                    $team[] = $vacancy->executor_id;
            }
        } else {
            $team = $teamIds;
        }

        $team = Customer::whereIn('id', $team)
            ->with('role')
            ->select(['id', 'name', 'email', 'role_id']);

        if ($role && in_array($role, self::$roleTeam)) {
            $team->whereHas('role', function ($query) use ($role) {
                $query->where('id', $role);
            });
        }

        $team = $team->get();

        return response()->json([
            'message' => 'Success',
            'data' => $team
        ]);
    }
}
