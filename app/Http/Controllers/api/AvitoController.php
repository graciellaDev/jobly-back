<?php

namespace App\Http\Controllers\api;

use App\Helpers\PlatformAvito;
use App\Http\Controllers\Controller;
use App\Models\Avito;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

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
                
                // Генерируем state для защиты от CSRF
                $state = Str::random(32);
                Cookie::queue('avito_oauth_state', $state, 10); // 10 минут
                
                $queryParams = [
                    'response_type' => 'code', // КРИТИЧНО
                    'client_id' => config('avito.client_id'),
                    'redirect_uri' => config('avito.redirect_url'), // http_build_query автоматически URL-кодирует
                    'state' => $state // Рекомендуется для защиты от CSRF
                ];
                
                // Scope для Avito API разделяются ЗАПЯТЫМИ (не пробелами!)
                // Формат: scope1,scope2,scope3 (например: messenger:read,messenger:write)
                // http_build_query автоматически кодирует двоеточия (: → %3A) и запятые
                $scope = config('avito.scope');
                if (!empty($scope)) {
                    $queryParams['scope'] = $scope;
                }
                
                // http_build_query автоматически URL-кодирует все параметры, включая redirect_uri и scope
                $this->url = config('avito.auth_url') . '?' . http_build_query($queryParams);
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
                
                // Генерируем state для защиты от CSRF
                $state = Str::random(32);
                Cookie::queue('avito_oauth_state', $state, 10); // 10 минут
                
                $url = config('avito.auth_url');
                $queryParams = [
                    'response_type' => 'code', // КРИТИЧНО
                    'client_id' => config('avito.client_id'),
                    'redirect_uri' => config('avito.redirect_url'), // http_build_query автоматически URL-кодирует
                    'state' => $state // Рекомендуется для защиты от CSRF
                ];
                
                // Scope для Avito API разделяются ЗАПЯТЫМИ (не пробелами!)
                // Формат: scope1,scope2,scope3 (например: messenger:read,messenger:write)
                // http_build_query автоматически кодирует двоеточия (: → %3A) и запятые
                $scope = config('avito.scope');
                if (!empty($scope)) {
                    $queryParams['scope'] = $scope;
                }
                
                // http_build_query автоматически URL-кодирует все параметры, включая redirect_uri и scope
                return redirect($url . '?' . http_build_query($queryParams));
            }
        } else {
            // Проверяем state для защиты от CSRF
            $stateFromRequest = $request->get('state');
            $stateFromCookie = Cookie::get('avito_oauth_state');
            
            // Проверяем наличие и совпадение state
            if (empty($stateFromRequest) || empty($stateFromCookie) || $stateFromRequest !== $stateFromCookie) {
                Cookie::forget('avito_oauth_state');
                $this->message = 'Ошибка проверки state. Возможна CSRF атака.';
                $this->status = 403;
                $url = config('avito.front_save_ids');
                $queryParams = [
                    'popup_account' => 'true',
                    'platform' => 'avito',
                    'status_auth' => 'false',
                    'message' => $this->message
                ];
                return redirect()->to($url . '?' . http_build_query($queryParams));
            }
            
            // Удаляем state после проверки
            Cookie::forget('avito_oauth_state');
            
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

        $pubEndpoint = config('avito.get_publication');
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

    /**
     * Создание публикации (вакансии) в Avito
     * POST /api/avito/publications
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addPublication(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        $employerId = $request->attributes->get('employer_id');

        if (empty($employerId)) {
            return response()->json([
                'message' => 'Ваш аккаунт не может создавать публикации',
                'data' => []
            ], 404);
        }

        try {
            // Валидация данных согласно документации Avito API для вакансий
            $data = $request->validate([
                'title' => 'required|string|min:3|max:200',
                'description' => 'required|string|min:10',
                'category_id' => 'required|integer',
                'location_id' => 'required|integer',
                'price' => 'nullable|numeric|min:0',
                'contact_phone' => 'nullable|string',
                'address' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                // Дополнительные поля для вакансий (job)
                'salary' => 'nullable|integer',
                'employment' => 'nullable|string',
                'schedule' => 'nullable|string',
                'experience' => 'nullable|string',
                'education' => 'nullable|string',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации: ' . $th->getMessage(),
                'data' => []
            ], 422);
        }

        // Формируем endpoint для создания публикации
        // POST /core/v1/accounts/{employer_id}/items
        $endpoint = config('avito.create_publication')['url'] . $employerId . config('avito.create_publication')['folder'];
        
        // Отправляем POST запрос с JSON данными
        $response = PlatformAvito::requirePostPlatform($customerToken, $endpoint, $data, true);

        if ($response->status() != 200 && $response->status() != 201) {
            return response()->json([
                'message' => $response->status() == 400 ? 'Ошибка валидации данных' : 
                           ($response->status() == 403 ? 'Недостаточно прав для создания публикации' : 
                           'Ошибка создания публикации'),
                'data' => $response->json() ?? []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Публикация успешно создана',
            'data' => $response->json()
        ], $response->status());
    }

    /**
     * Создание черновика публикации (вакансии) в Avito
     * POST /api/avito/drafts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addDraft(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        $employerId = $request->attributes->get('employer_id');

        if (empty($employerId)) {
            return response()->json([
                'message' => 'Ваш аккаунт не может создавать черновики',
                'data' => []
            ], 404);
        }

        try {
            // Валидация данных для черновика (те же поля, но все опциональные)
            $data = $request->validate([
                'title' => 'nullable|string|min:3|max:200',
                'description' => 'nullable|string|min:10',
                'category_id' => 'nullable|integer',
                'location_id' => 'nullable|integer',
                'price' => 'nullable|numeric|min:0',
                'contact_phone' => 'nullable|string',
                'address' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                // Дополнительные поля для вакансий (job)
                'salary' => 'nullable|integer',
                'employment' => 'nullable|string',
                'schedule' => 'nullable|string',
                'experience' => 'nullable|string',
                'education' => 'nullable|string',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации: ' . $th->getMessage(),
                'data' => []
            ], 422);
        }

        // Формируем endpoint для создания черновика
        // POST /core/v1/accounts/{employer_id}/items/draft
        $endpoint = config('avito.create_draft')['url'] . $employerId . config('avito.create_draft')['folder'];
        
        // Отправляем POST запрос с JSON данными
        $response = PlatformAvito::requirePostPlatform($customerToken, $endpoint, $data, true);

        if ($response->status() != 200 && $response->status() != 201) {
            return response()->json([
                'message' => $response->status() == 400 ? 'Ошибка валидации данных' : 
                           ($response->status() == 403 ? 'Недостаточно прав для создания черновика' : 
                           'Ошибка создания черновика'),
                'data' => $response->json() ?? []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Черновик успешно создан',
            'data' => $response->json()
        ], $response->status());
    }
}



