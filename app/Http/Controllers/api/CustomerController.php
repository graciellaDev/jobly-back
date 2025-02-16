<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Mail\register\Success;
use App\Mail\RegsuccessEmail;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
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

        $credentials = $request->only('email', 'password');
        $user = Customer::where('email', $request->email)->where('status', true)->first();

        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Авторизация прошла успешно',
                'user' => [
                    'login' => $user->login,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'site' => $user->site
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
            'site' => $request->site ?? $request->site,
            'status' => false
        ]);

        $rootUrl = $request->root();
        $url = $rootUrl . '/reg-success/' . $user->id . '/?key=' . urldecode($user->password);
        Mail::to('gravielladesign@gmail.com')->send(new Success(['name' => $user->name, 'url' => $url]));

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

    public function regSuccess(int $id, Request $request) {
        $query = $request->query('key');
        $today = Carbon::today()->subDays(3);
        $user = Customer::all()->find($id)->where('created_at', '>', $today);
        if (!empty($user)) {
            $user->update([
                'status' => true
            ]);
            return redirect(env('URL_FRONT') . '/reg-success');
        } else {
            $user->delete();
            return redirect(env('URL_FRONT') . '/reg-error');
        }
    }
}
