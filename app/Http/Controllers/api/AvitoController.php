<?php

namespace App\Http\Controllers\api;

use App\Helpers\PlatformAvito;
use App\Http\Controllers\Controller;
use App\Models\Avito;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AvitoController extends Controller
{
    private string |null $url = null;
    private string |null $message = null;
    private int $status = 200;
    private string $COOKIE_ID_CUSTOMER = 'customer_id_avito';

    public function auth(Request $request): JsonResponse
    {
        $customerToken = $request->get('customerToken');
        
        if (!empty($customerToken)) {
            $customer = Customer::where('auth_token', $customerToken)->first();
            
            if (!$customer) {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
            } else {
                Cookie::queue($this->COOKIE_ID_CUSTOMER, $customer->id, 60);
                $this->url = config('avito.auth_url')
                    . '?response_type=code&'
                    . 'client_id=' . config('avito.client_id') . '&'
                    . 'redirect_uri=' . urlencode(config('avito.redirect_url'));
                $this->message = 'Success';
            }
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
        $clientId = config('avito.client_id');
        $clientSecret = config('avito.client_secret');
        
        if (!$code) {
            $customerToken = $request->get('customerToken');
            $customer = Customer::where('auth_token', $customerToken)->first();

            if (!$customer || !$customerToken) {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
            } else {
                Cookie::queue($this->COOKIE_ID_CUSTOMER, $customer->id, 60);
                $url = config('avito.auth_url');
                $queryParams = [
                    'response_type' => 'code',
                    'client_id' => config('avito.client_id'),
                    'redirect_uri' => config('avito.redirect_url')
                ];
                return redirect($url . '?' . http_build_query($queryParams));
            }
        } else {
            $customerId = Cookie::get($this->COOKIE_ID_CUSTOMER);
            if ($customerId) {
                $data = $this->getToken($code, $clientId, $clientSecret);
                if ($data) {
                    $data['customer_id'] = $customerId;
                    
                    // Получаем профиль работодателя
                    $profile = PlatformAvito::requireGetPlatform($data['access_token'], config('avito.get_profile_url'));
                    if ($profile->status() == 200) {
                        $profile = $profile->json();
                        // Извлекаем employer_id из ответа Avito (структура может отличаться от HH)
                        if (isset($profile['user_id'])) {
                            $data['employer_id'] = $profile['user_id'];
                        } elseif (isset($profile['id'])) {
                            $data['employer_id'] = $profile['id'];
                        }
                    }

                    Avito::create($data);
                    Cookie::forget($this->COOKIE_ID_CUSTOMER);
                    $url = config('avito.front_save_ids');
                    $queryParams = [
                        'popup_account' => 'true',
                        'platform' => 'avito',
                        'status_auth' => 'true',
                        'message' => 'Авторизация прошла успешно'
                    ];
                    return redirect()->to($url . '?' . http_build_query($queryParams));
                } else {
                    $this->message = 'Ошибка получения токена';
                    $this->status = 400;
                    $url = config('avito.front_save_ids');
                    $queryParams = [
                        'popup_account' => 'true',
                        'platform' => 'avito',
                        'status_auth' => 'false',
                        'message' => $this->message
                    ];
                    return redirect()->to($url . '?' . http_build_query($queryParams));
                }
            } else {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
                $url = config('avito.front_save_ids');
                $queryParams = [
                    'popup_account' => 'true',
                    'platform' => 'avito',
                    'status_auth' => 'false',
                    'message' => $this->message
                ];
                return redirect()->to($url . '?' . http_build_query($queryParams));
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
                'redirect_uri' => config('avito.redirect_url'),
                'code' => $code
            ];
            $response = PlatformAvito::requirePostPlatform(null, config('avito.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'] ?? 'Bearer',
                    'expires_in' => time() + ($data['expires_in'] ?? 3600),
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }

    public function sendUrl(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        try {
            $data = $request->validate([
                'url' => 'required|string|max:255'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации url',
            ], 422);
        }

        $response = PlatformAvito::requireGetPlatform($customerToken, $data['url']);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getProfile(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        $response = PlatformAvito::requireGetPlatform($customerToken, config('avito.get_profile_url'));

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Профиль не найден' : 'Ошибка получения профиля',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getPublicationList(Request $request): JsonResponse
    {
        $data = [];
        $customerToken = $request->attributes->get('token');
        $employerId = $request->attributes->get('employer_id');

        if (empty($employerId)) {
            return response()->json([
                'message' => 'Ваш аккаунт не может иметь публикаций',
                'data' => $data
            ], 404);
        }

        $pubEndpoint = config('avito.get_publications')['url'] . $employerId . config('avito.get_publications')['folder'];
        $response = PlatformAvito::requireGetPlatform($customerToken, $pubEndpoint);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Публикации не найдены' : 'Ошибка получения публикаций',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getPublication(Request $request, int $id): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        if (!$id) {
            return response()->json([
                'message' => 'Не передан идентификатор публикации',
                'data' => []
            ], 422);
        }

        $response = PlatformAvito::requireGetPlatform($customerToken, config('avito.get_publication') . $id);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Публикация не найдена' : 'Ошибка получения публикации',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getDraftList(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        $employerId = $request->attributes->get('employer_id');

        if (empty($employerId)) {
            return response()->json([
                'message' => 'Ваш аккаунт не может иметь черновиков',
                'data' => []
            ], 404);
        }

        $draftEndpoint = config('avito.get_drafts')['url'] . $employerId . config('avito.get_drafts')['folder'];
        $response = PlatformAvito::requireGetPlatform($customerToken, $draftEndpoint);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Черновики не найдены' : 'Ошибка получения черновиков',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }
}



