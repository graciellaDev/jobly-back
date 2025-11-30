<?php

namespace App\Http\Middleware\api;

use App\Models\Avito;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class AvitoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $error = null;
        $token = null;
        $employerId = null;
        $customerId = $request->attributes->get('customer_id');
        $userAvito = Avito::where('customer_id', $customerId)->first();

        if (!$userAvito) {
            return response()->json([
                'message' => 'Пользователь еще не авторизован',
                'data' => []
            ], 404);
        }

        $token = $userAvito->access_token;
        if ($userAvito->expires_in < time()) {
            $token = $userAvito->getRefreshToken();

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
            $userAvito->update($data);
            $token = $token['access_token'];
        }

        $request->attributes->set('employer_id', $userAvito->employer_id);
        $request->attributes->set('token', $token);

        return $next($request);
    }
}

