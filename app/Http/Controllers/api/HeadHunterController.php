<?php

namespace App\Http\Controllers\api;

use App\Models\Customer;
use App\Models\HeadHunter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HeadHunterController extends Controller
{
    private string |null $url = null;
    private string |null $message = null;
    private int $status = 200;
    private string $COOKIE_ID_CUSTOMER = 'customer_id';
    public function auth(Request $request): JsonResponse
    {
//        $clientId = Cookie::get($this->COOKIE_ID_CLIENT);
        if (!empty($clientId)) {
            $this->url = config('hh.auth_url')
                . '?response_type=code&'
//                . "client_id=$clientId&"
                . "force_login=true&"
                . config('hh.redirect_url');
            $this->message = 'Success';
        } else {
            $this->message = 'Не заполнены обязательные поля';
            $this->status = 422;
        }

        return response()->json([
            'message' => $this->message,
            'url_auth' => $this->url
        ], $this->status);
    }

    public function code(Request $request)
    {
        $code = $request->get('code');
        $clientId = config('hh.client_id');
        $clientSecret = config('hh.client_secret');
        if (!$code) {
            $customerToken = $request->get('customerToken');
            $customer = Customer::where('auth_token', $customerToken)->first();
            if (!$customer || !$customerToken) {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
            }else {
                if ($clientId && $clientSecret) {
                    Cookie::queue($this->COOKIE_ID_CUSTOMER, $customer->id, 60);
                    return redirect(
                        config('hh.auth_url')
                        . '?force_login=true&response_type=code'
                        . '&client_id=' . config('hh.client_id')
                        . 'redirect_uri=' . config('hh.redirect_url')
                    );
                } else {
                    return redirect(config('hh.front_save_ids') . '&message=Не заполнено поле');
                }
            }
        } else {
            $customerId = Cookie::get($this->COOKIE_ID_CUSTOMER);
            if ($customerId) {
                $data = $this->getToken($this->clientId, $clientSecret, $code);
                if ($data) {
                    $data['customer'] = $customerId;
                    HeadHunter::create($data);
                    return redirect(config('hh.front_save_ids') . '&status_auth=true&message=Авторизация прошла успешно');
                    $this->message = 'Success';
                }
            } else {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
            }
        }

        return response()->json([
            'message' => $this->message,
            'url' => $this->url
        ], $this->status);
    }

    private function getToken(string $code = null, string $clientId = null, string $secretId = null): bool | array
    {
        if (!$code || !$clientId || !$secretId) {
            return false;
        } else {
            $response = Http::post(config('hh.get_token_url'), [
                'client_id' => $clientId,
                'client_secret' => $secretId,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('hh.redirect_url'),
                'code' => $code
            ]);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => $data['expires_in'],
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }

    private function getRefreshToken(string $clientId = null, string $secretId = null): bool | array
    {
        if (!$clientId || !$secretId) {
            return false;
        } else {
            $response = Http::post(config('hh.get_token_url'), [
                'client_id' => $clientId,
                'client_secret' => $secretId,
                'grant_type' => 'refresh_token',
                'redirect_uri' => config('hh.redirect_url'),
            ]);
            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => $data['expires_in'],
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }

    public function getProfile(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');
        $userHh = HeadHunter::where('customer_id', $customerId)->first();

        if (!$userHh) {
            return response()->json([
                'message' => 'Пользователеь не еще не авторизован',
                'data' => []
            ], 404);
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $userHh['access_token']])
            ->get(config('hh.get_profile_url'));
        if ($response->status() == 400) {
            return response()->json([
                'message' => 'Ошибка запроса',
                'data' => []
            ], 400);
        }
        if ($response->status() == 403) {
            $response = $this->getRefreshToken($userHh['id_client'], $userHh['id_secret']);
            if (!$response) {
                return response()->json([
                    'message' => 'Ошибка получения refresh токена',
                    'data' => []
                ], 400);
            } else {
                $data = [];
                $data['access_token'] = $response['access_token'];
                $data['refresh_token'] = $response['refresh_token'];
                $data['expired_in'] = $response['expired_in'];
                $userHh->update($data);
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $response['access_token']])
                    ->get(config('hh.get_profile_url'));
            }
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }
}
