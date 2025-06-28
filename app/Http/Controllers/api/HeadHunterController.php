<?php

namespace App\Http\Controllers\api;

use App\Models\Customer;
use App\Models\HeadHunter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\Attributes\Ticket;

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
            } else {
                Cookie::queue($this->COOKIE_ID_CUSTOMER, $customer->id, 60);
                $url = config('hh.auth_url');
                $queryParams = [
                    'force_login' => 'true',
                    'response_type' => 'code',
                    'client_id' => config('hh.client_id'),
                    'redirect_uri' => config('hh.redirect_url')
                ];
                return redirect($url . '?' . http_build_query($queryParams));
            }
        } else {
            $customerId = Cookie::get($this->COOKIE_ID_CUSTOMER);
            if ($customerId) {
                $data = $this->getToken($code, $clientId, $clientSecret);
                if ($data) {
                    $data['customer_id'] = $customerId;
                    $profile = $this->requireGetPlatform($data['access_token'], config('hh.get_profile_url'));
                    if ($profile->status() == 200) {
                        $profile = $profile->json();
                        $data['employer_id'] = json_encode($profile);
                    } else {
                        $data['employer_id'] = 'Не назначен';
                    }

                    HeadHunter::create($data);
                    Cookie::forget($this->COOKIE_ID_CUSTOMER);
                    $url = config('hh.front_save_ids');
                    $queryParams = [
                        'popup_account' => 'true',
                        'platform' => 'hh',
                        'status_auth' => 'true',
                        'message' => 'Авторизация прошла успешно'
                    ];
                    return redirect()->to($url . '?' . http_build_query($queryParams));
                } else {
                    $this->message = 'Ошибка получения токена';
                    $this->status = 400;
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
            $formData = [
                'client_id' => $clientId,
                'client_secret' => $secretId,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('hh.redirect_url'),
                'code' => $code
            ];
            $response = $this->requirePostPlatform(null, config('hh.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expired_in' => time() + $data['expires_in'],
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }
    private function getRefreshToken(string $accessToken = null, string $refreshToken = null): bool | array
    {
        if (!$accessToken || !$refreshToken) {
            return false;
        } else {
//            $clientId = config('hh.client_id');
//            $clientSecret = config('hh.client_secret');
            $formData = [
                'refresh_token' => $refreshToken,
//                'client_id'     => $clientId,
//                'client_secret' => $clientSecret,
//                'access_token' => $accessToken,
                'grant_type' => 'refresh_token',
//                'redirect_uri' => config('hh.redirect_url'),
            ];
            $response = $this->requirePostPlatform($accessToken, config('hh.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'] + time(),
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

        $response = $this->requireGetPlatform($userHh['access_token'], config('hh.get_profile_url'));
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
                $response = $this->requireGetPlatform($response['access_token'], config('hh.get_profile_url'));
            }
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    private function requireGetPlatform(string $token, string $url): PromiseInterface | Response
    {
        return  Http::withHeaders([
            'Content-Type'  => config('hh.content_type'),
            'Authorization' => 'Bearer ' . $token
        ])->asForm()->get($url);
    }

    private function requirePostPlatform(string $token, string $url, array $data): PromiseInterface | Response
    {
        $headers = [
            'Content-Type'  => config('hh.content_type'),
        ];
        if (!empty($token)) {
            $headers['Authorization'] = "Bearer $token";
        }

        return  Http::withHeaders($headers)->asForm()->post($url, $data);
    }

    public function getPublications(Request $request): JsonResponse
    {
        $customerId = $request->attributes->get('customer_id');

        $userHh = HeadHunter::where('customer_id', $customerId)->first();
        $data = [];
        if (!$userHh) {
            return response()->json([
                'message' => 'Пользователь не авторизован на hh.ru',
                'data' => $data
            ], 404);
        }
        $accessToken = $userHh->access_token;
        if ($userHh->expired_in - 60 < time()) {
            $response = $this->getRefreshToken($userHh->access_token, $userHh->refresh_token);
            if (!$response) {
                return response()->json([
                    'message' => 'Ошибка получения refresh токена',
                    'data' => []
                ], 404);
            }
            $userHh->update($response);
            $accessToken = $response['access_token'];
        }

        $pubEndpoint = config('hh.get_publications')['url'] . $userHh->employer_id . config('hh.get_publications')['folder'];
        $data = $this->requireGetPlatform($accessToken, $pubEndpoint);

        return response()->json([
            'message' => $this->message,
            'data' => $data->json()
        ], $this->status);
    }
}
