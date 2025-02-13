<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        return response()->json([
            'login'
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'login' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);


        $user = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'login' => $request->login,
            'password' => Hash::make($request->password),
            // 'create_at' => now(),
            // 'update_at' => now()
        ]);

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => $user
        ]);
    }
}
