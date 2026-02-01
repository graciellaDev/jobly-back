<?php

namespace App\Http\Controllers\api;

use App\Helpers\PlatformRabotaRu;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RabotaRu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class RabotaRuController extends Controller
{
    private string|null $url = null;
    private string|null $message = null;
    private int $status = 200;
    private string $COOKIE_ID_CUSTOMER = 'customer_id';

    public function auth(Request $request): JsonResponse
    {
        $appId = config('rabota.app_id');
        if (!empty($appId)) {
            $queryParams = [
                'app_id' => $appId,
                'scope' => config('rabota.scope', 'profile,vacancies'),
                'display' => 'page',
                'redirect_uri' => config('rabota.redirect_url')
            ];
            $this->url = config('rabota.auth_url') . '?' . http_build_query($queryParams);
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
        $appId = config('rabota.app_id');
        $secret = config('rabota.secret');
        if (!$code) {
            $customerToken = $request->get('customerToken');
            $customer = Customer::where('auth_token', $customerToken)->first();

            if (!$customer || !$customerToken) {
                $this->message = 'Пользователь не найден';
                $this->status = 404;
            } else {
                Cookie::queue($this->COOKIE_ID_CUSTOMER, $customer->id, 60);
                $url = config('rabota.auth_url');
                $queryParams = [
                    'app_id' => config('rabota.app_id'),
                    'scope' => config('rabota.scope', 'profile,vacancies'),
                    'display' => 'page',
                    'redirect_uri' => config('rabota.redirect_url')
                ];
                return redirect($url . '?' . http_build_query($queryParams));
            }
        } else {
            $customerId = Cookie::get($this->COOKIE_ID_CUSTOMER);
            if ($customerId) {
                $data = $this->getToken($code, $appId, $secret);
                if ($data) {
                    $data['customer_id'] = $customerId;
                    $profile = PlatformRabotaRu::requireGetPlatform($data['access_token'], config('rabota.get_profile_url'));

                    if ($profile->status() == 200) {
                        $profile = $profile->json();
                        // Проверка на работодателя для Rabota.ru
                        // Структура ответа может отличаться, нужно адаптировать под реальный ответ API
                        if (isset($profile['employer_id']) || isset($profile['employer']['id'])) {
                            $data['employer_id'] = $profile['employer_id'] ?? $profile['employer']['id'] ?? null;
                            $this->message = 'Авторизация прошла успешно';
                            $this->status = 200;
                        } else {
                            // Если employer_id не найден, все равно сохраняем токен, но без employer_id
                            $this->message = 'Авторизация прошла успешно, но аккаунт может не иметь прав работодателя';
                            $this->status = 200;
                        }
                    } else {
                        // Если не удалось получить профиль, все равно сохраняем токен
                        $this->message = 'Токен получен, но не удалось получить профиль';
                        $this->status = 200;
                    }

                    RabotaRu::create($data);
                    Cookie::forget($this->COOKIE_ID_CUSTOMER);
                    $url = config('rabota.front_save_ids');
                    $queryParams = [
                        'popup_account' => 'true',
                        'platform' => 'rabota',
                        'status_auth' => $this->status == 200 ? 'true' : 'false',
                        'message' => $this->message
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

    private function getToken(
        string|null $code = null,
        string|null $appId = null,
        string|null $secret = null
    ): bool|array {
        if (!$code || !$appId || !$secret) {
            return false;
        } else {
            $time = time();
            $formData = [
                'app_id' => $appId,
                'time' => $time,
                'code' => $code
            ];
            
            // Генерируем подпись запроса
            $signature = PlatformRabotaRu::getSignature($formData, $secret);
            $formData['signature'] = $signature;
            
            $response = PlatformRabotaRu::requirePostPlatform(null, config('rabota.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => time() + ($data['expires_in'] ?? 3600),
                    'refresh_token' => $data['access_token'] // В Rabota.ru refresh_token не возвращается, используется тот же токен
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

        $response = PlatformRabotaRu::requireGetPlatform($customerToken, $data['url']);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getProfile(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        // Согласно OpenAPI v4: /me.json использует POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_profile_url'), []);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getAvailableTypes(Request $request)
    {
        $customerToken = $request->attributes->get('token');
        // Согласно OpenAPI v4: /me/vacancies/filters.json использует POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_available_types'), [], true);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getPublicationList(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        
        // Согласно OpenAPI v4: /me/vacancies.json использует POST метод
        // Параметр archived определяет, использовать ли архивный эндпоинт
        $archived = $request->get('archived');
        $endpoint = ($archived === 'true')
            ? config('rabota.get_publications_archived')
            : config('rabota.get_publications');
        
        // Параметры запроса передаются в теле POST
        $params = $request->except(['archived']);
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, $endpoint, $params, true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Вакансий не найдено' : 'Ошибка получения вакансий',
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

        // Согласно OpenAPI v4: /me/vacancies.json использует POST метод с параметром vacancy_id
        $params = ['vacancy_id' => $id];
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_publication'), $params, true);

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

    public function getVacancies(Request $request)
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: /me/vacancies.json использует POST метод
        $params = $request->all();
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_vacancies'), $params, true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Вакансий не найдено' : 'Ошибка получения вакансий',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function addPublication(Request $request)
    {
        $customerToken = $request->attributes->get('token');
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:100',
                'description' => 'required|string|min:1|max:1024',
                'area' => 'required',
                'code' => 'nullable|string|max:255',
                'employment_form' => 'nullable',
                'working_hours' => 'nullable',
                'work_schedule_by_days' => 'nullable',
                'education_level' => 'nullable',
                'experience' => 'nullable',
                'driver_license_types' => 'nullable',
                'manager' => 'numeric',
                'previous_id' => 'numeric', // id архивной вакансии
                'type' => 'nullable',
                'address' => 'numeric|default:1',
                'fly_in_fly_out_duration' => 'nullable',
                'work_format' => 'nullable',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        // Согласно OpenAPI v4: создание вакансии через /me/vacancies.json (POST)
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_publications'), $data, true);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function addDraft(Request $request)
    {
        $customerToken = $request->attributes->get('token');
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:100',
                'description' => 'required|string|min:1|max:1024',
                'billing_types' => 'nullable',
                'professional_roles' => 'nullable',
                'areas' => 'nullable',
                'code' => 'nullable',
                'employment_form' => 'nullable',
                'driver_license_types' => 'nullable',
                'work_schedule_by_days' => 'nullable',
                'education_level' => 'nullable',
                'manager' => 'nullable',
                'previous_id' => 'nullable',
                'type' => 'nullable',
                'address' => 'nullable',
                'experience' => 'nullable',
                'fly_in_fly_out_duration' => 'nullable',
                'work_format' => 'nullable',
                'schedule' => 'nullable'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }

        // Согласно OpenAPI v4: создание черновика через /me/vacancies.json с параметром folder=drafts
        $data['folder'] = 'drafts';
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_drafts'), $data, true);

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

        // Согласно OpenAPI v4: черновики получаются через /me/vacancies.json с фильтром folder=drafts
        $params = array_merge($request->all(), ['folder' => 'drafts']);
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_drafts'), $params, true);

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

    public function getProfessionals(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_professional_roles'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Роли не найдены' : 'Ошибка получения ролей',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getVacancyResponses(Request $request, int $id): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        if (!$id) {
            return response()->json([
                'message' => 'Не передан идентификатор вакансии',
                'data' => []
            ], 422);
        }

        // Согласно OpenAPI v4: /me/responses.json использует POST метод с параметром vacancy_id
        $params = ['vacancy_id' => $id];
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_vacancy_responses'), $params, true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Отклики не найдены' : 'Ошибка получения откликов',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getVacancyResponse(Request $request, int $id): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        if (!$id) {
            return response()->json([
                'message' => 'Не передан идентификатор отклика',
                'data' => []
            ], 422);
        }

        // Согласно OpenAPI v4: /me/response/contact.json использует POST метод с параметром response_id
        $params = ['response_id' => $id];
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_vacancy_response'), $params, true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Отклик не найден' : 'Ошибка получения отклика',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function closeAuth(Request $request) {
        $customerId = $request->attributes->get('customer_id');
        $customer = Customer::find($customerId);

        if (empty($customer)) {
            return response()->json([
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $rabota = RabotaRu::where('customer_id', $customerId)->first();

        if (empty($rabota)) {
            return response()->json([
                'message' => 'Аккаунт не найден',
            ], 404);
        }

        $rabota->delete();
        return response()->json([
            'message' => 'Аккаунт rabota.ru успешно отвязан от платформы',
        ]);
    }

    public function getAddresses(Request $request) {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: /me/company/addresses.json использует POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_addresses'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Адресов не найдено' : 'Ошибка получения адресов',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getCountVisitors(Request $request, int $id): JsonResponse {
        $customerToken = $request->attributes->get('token');

        if (!$id) {
            return response()->json([
                'message' => 'Не передан идентификатор вакансии',
                'data' => []
            ], 422);
        }

        // Согласно OpenAPI v4: /me/vacancies/responses/counters.json использует POST метод с параметром vacancy_id
        $params = ['vacancy_id' => $id];
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_count_visitors'), $params, true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Статистика не найдена' : 'Ошибка получения статистики',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Количество просмотров за последнюю неделю успешно получено',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка регионов (areas)
     * Используется для указания региона размещения вакансии
     */
    public function getAreas(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_areas'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Регионы не найдены' : 'Ошибка получения регионов',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка форм занятости (employment_form)
     * Используется для указания типа занятости (полная, частичная и т.д.)
     */
    public function getEmploymentForms(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_employment_forms'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Формы занятости не найдены' : 'Ошибка получения форм занятости',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка уровней образования (education_level)
     * Используется для указания требований к образованию соискателя
     */
    public function getEducationLevels(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_education_levels'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Уровни образования не найдены' : 'Ошибка получения уровней образования',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка опыта работы (experience)
     * Используется для указания требований к опыту работы
     */
    public function getExperience(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_experience'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Опыт работы не найден' : 'Ошибка получения опыта работы',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка типов водительских прав (driver_license_types)
     * Используется для указания требований к водительским правам
     */
    public function getDriverLicenseTypes(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_driver_license_types'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Типы водительских прав не найдены' : 'Ошибка получения типов водительских прав',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка типов оплаты (billing_types)
     * Используется для указания способа оплаты труда
     */
    public function getBillingTypes(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_billing_types'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Типы оплаты не найдены' : 'Ошибка получения типов оплаты',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка форматов работы (work_format)
     * Используется для указания формата работы (удаленно, офис и т.д.)
     */
    public function getWorkFormats(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_work_formats'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Форматы работы не найдены' : 'Ошибка получения форматов работы',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка рабочих часов (working_hours)
     * Используется для указания количества рабочих часов
     */
    public function getWorkingHours(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_working_hours'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Рабочие часы не найдены' : 'Ошибка получения рабочих часов',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    /**
     * Получение списка графиков работы (schedules)
     * Используется для указания графика работы (полный день, сменный график и т.д.)
     */
    public function getSchedules(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        // Согласно OpenAPI v4: справочники используют POST метод
        $response = PlatformRabotaRu::requirePostPlatform($customerToken, config('rabota.get_schedules'), [], true);

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Графики работы не найдены' : 'Ошибка получения графиков работы',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }
}
