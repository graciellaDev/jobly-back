<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Mail\RegsuccessEmail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $cookieAuth = $request->cookie('auth_user');
        if (isset($cookieAuth) && !empty($cookieAuth)) {
            $userAuth = Customer::all()
                ->where('auth_token', $cookieAuth)
                ->where('auth_time', '>=', Carbon::today())
                ->first();
            if (!empty($userAuth)) {
                return response()->json([
                    'message' => 'Авторизация прошла успешно',
                    'user' => [
                        'login' => $userAuth->login,
                        'name' => $userAuth->name,
                        'email' => $userAuth->email,
                        'phone' => $userAuth->phone,
                        'site' => $userAuth->site
                    ]
                ]);
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
            $user->auth_time = Carbon::today();
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
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'login' => 'required|string|max:60',
                'phone' => 'regex:/^\+7\d{10}$/',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|string|min:6'
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

        $user = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'login' => $request->login,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'customer',
            'site' => $request->site ?? $request->site
        ]);


        $rootUrl = $request->root();
        $url = $rootUrl . '/reg-success/' . $user->id . '/?key=' . urldecode($user->password);
        $data = [
        'name' => $user->name,
        'url' => $url
    ];
         Mail::to($user->email)->send(new RegsuccessEmail($data));

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => [
                'login' => $user->login,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'site' => $user->site
            ]
        ]);
    }
    public function regSuccess(int $id, Request $request): RedirectResponse {
        $today = Carbon::today()->subDays(3);
        $key = $request->get('key');
        $user = Customer::all()->find($id)->where('created_at', '>', $today)->first();
        if (!empty($user)) {
            if($key !== $user->password) {
                return redirect(env('URL_FRONT') . '/auth/?reg=error');
            }

            $token = Hash::make($user->password);
            $user->auth_token = $token;
            $user->save();

            return redirect(env('URL_FRONT') . '/auth/?reg=success');
        } else {
            $user = Customer::all()->find($id)->where('created_at', '<=', $today);
            if (!empty($user)) {
                $user->delete();
            }

            return redirect(env('URL_FRONT') . '/auth/?reg=error');
        }
    }
}
