<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Mail\register\Success;
use App\Mail\register\Restore;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $cookieAuth = $request->cookie('auth_user');
        if (isset($cookieAuth) && !empty($cookieAuth)) {
            $userAuth = Customer::all()->where('auth_token', $cookieAuth)->first();
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
                                'auth_token' => $userAuth->auth_token
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
                            'auth_token' => $userAuth->auth_token
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

        $user = Customer::all()->where('email', $request->email)->first();
        if (empty($user)) {
            return response()->json([
                'message' => 'Неверный логин или пароль'
            ], 404);
        }
        if (Hash::check($request->password, $user->password)) {
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
                'auth_token' => $user->auth_token
            ]
        ]);
        } else {

            return response()->json([
                'message' => 'Неверный логин или пароль',
            ], 404);
        }
    }

    public function register(Request $request)
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
                'from' => 'nullable|string|max:255'
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
            $users = Customer::where($user['field'], $user['value'])->first();
            if ($users) {
                return response()->json([
                    'message' => $user['error'],
                ], 422);
            }
        }

        $customHash = Str::random(16) . time();
        $authToken = Hash::make($customHash);
        $data['password'] = Hash::make($request->password);
        $data['from_source'] = $data['from'];
        $user = Customer::create($data);


        $rootUrl = $request->root();
        $url = $rootUrl . '/reg-success/' . $user->id . '/?key=' . urldecode($user->password);
        $data = [
            'name' => $user->name,
            'url' => $url
        ];
         Mail::to($user->email)->send(new Success($data));

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

            return redirect(env('URL_FRONT') . '/auth/?reg=success&key=' . $token);
        } else {
            $user = Customer::all()->find($id)->where('created_at', '<=', $today);
            if (!empty($user)) {
                $user->delete();
            }

            return redirect(env('URL_FRONT') . '/auth/?reg=error');
        }
    }

    public function restoreAccess(Request $request)
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

    public function restoreSuccess(int $id, Request $request)
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
            return  json_encode([
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
}
