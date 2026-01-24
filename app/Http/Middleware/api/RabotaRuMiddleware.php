<?php

namespace App\Http\Middleware\api;

use App\Models\RabotaRu;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class RabotaRuMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $error = null;
        $token = null;
        $employerId = null;
        $customerId = $request->attributes->get('customer_id');
        $userRabota = RabotaRu::where('customer_id', $customerId)->first();

        if (!$userRabota) {
            return response()->json([
                'message' => 'Пользователь еще не авторизован',
                'data' => []
            ], 404);
        }

        $token = $userRabota->access_token;
        if ($userRabota->expires_in < time()) {
            $token = $userRabota->getRefreshToken();

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
            $userRabota->update($data);
            $token = $token['access_token'];
        }

        $request->attributes->set('employer_id', $userRabota->employer_id);
        $request->attributes->set('token', $token);

        return $next($request);
    }
}
