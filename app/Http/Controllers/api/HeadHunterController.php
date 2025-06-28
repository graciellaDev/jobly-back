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
                        $data['employer_id'] = $profile['employer']['id'];
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

    private function getToken(string | null $code = null, string | null $clientId = null, string | null $secretId =
    null): bool | array
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
        $customerToken = $request->attributes->get('token');
        $response = $this->requireGetPlatform($customerToken, config('hh.get_profile_url'));

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

    private function requirePostPlatform(string | null $token, string $url, array $data): PromiseInterface |
    Response
    {
        $headers = [
            'Content-Type'  => config('hh.content_type'),
        ];
        if (!empty($token)) {
            $headers['Authorization'] = "Bearer $token";
        }

        return  Http::withHeaders($headers)->asForm()->post($url, $data);
    }

    public function getPublicationList(Request $request): JsonResponse
    {
        $data = [];
        $customerToken = [
            'token' => $request->attributes->get('token'),
            'employer_id' => $request->attributes->get('employer_id')
        ];

        if (empty($customerToken['employer_id'])) {
            return response()->json([
                'message' => 'Ваш аккаунт не может иметь публикаций',
                'data' => $data
            ], 404);
        }

        $pubEndpoint = config('hh.get_publications')['url'] . $customerToken['employer_id']  . config('hh.get_publications')['folder'];
        $data = $this->requireGetPlatform($customerToken['token'], $pubEndpoint)->json();

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }

    public function getPublication(Request $request, int $id): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        $response = $this->requireGetPlatform($customerToken, config('hh.get_publication') . 'id');

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Публикация не найдена' : 'Ошибка получения вакансии',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function updatePublication(Request $request, int $id): JsonResponse
    {

        return response()->json([]);
    }

    public function getDraftList(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        $response = $this->requireGetPlatform($customerToken, config('hh.get_drafts'));

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Публикация не найдена' : 'Ошибка получения вакансии',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }
}
