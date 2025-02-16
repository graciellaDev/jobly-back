<?php

namespace App\Http\Controllers\api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function __construct()
    {
        //        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        //        $token = Cache::remember('states', $seconds = 3600, function () {
//            request()->validate([
//                'email' => 'required|string|email',
//                'password' => 'required|string',
//            ]);
//
//
//            $credentials = request()->only('email', 'password');
//
//            return JWTAuth::attempt($credentials);
//        });
//        if (!$token) {
//            return response()->json([
//                'message' => 'Unauthorized',
//            ], 401);
//        }
//
//        $user = Cache::remember('states', $seconds = 3600, function () {
//            return JWTAuth::user();
//        });

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = JWTAuth::user();
        return response()->json([
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function register(Request $request)
    {
        var_dump('dfgdfgdfgdfgdf');
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json([
            'status' => true,
            'message' => 'Logout is successfully'
        ]);
    }


    public function profile()
    {
        $userData = auth('api')->user();

        return response()->json([
            'status' => true,
            'message' => 'Profile data',
            'user' => $userData
        ]);
    }

    public function refresh()
    {
        $newToken = auth()->refresh();

        return response()->json([
            'status' => true,
            'message' => 'New access token generated',
            'token' => $newToken
        ]);
    }

}
