<?php

namespace App\Http\Middleware\api;

use App\Models\HeadHunter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;
class HeadHunterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $error = null;
        $token = null;
        $employerId = null;
        $customerId = $request->attributes->get('customer_id');
        $userHh = HeadHunter::where('customer_id', $customerId)->first();

        if (!$userHh) {
            return response()->json([
                'message' => 'Пользователь еще не авторизован',
                'data' => []
            ], 404);
        }

        $token = $userHh->access_token;
        if ($userHh->expires_in < time()) {
//            $token = $userHh->getRefreshToken($token, $userHh->refresh_token);
            $token = $userHh->getRefreshToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Ошибка получения refresh токена',
                    'data' => []
                ], 404);
            }

            $data = [];
            $data['access_token'] = $token['access_token'];
            $data['refresh_token'] = $token['refresh_token'];
            $data['expires_in'] = $token['expires_in'];
            $userHh->update($data);
            $token = $token['access_token'];
        }

        $request->attributes->set('employer_id', $userHh->employer_id);
        $request->attributes->set('token', $token);

        return $next($request);
    }
}
