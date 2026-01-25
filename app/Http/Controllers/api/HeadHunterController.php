<?php

namespace App\Http\Controllers\api;

use App\Helpers\PlatformHh;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\HeadHunter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class HeadHunterController extends Controller
{
    private string|null $url = null;
    private string|null $message = null;
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
                    $profile = PlatformHh::requireGetPlatform($data['access_token'], config('hh.get_profile_url'));

                    if ($profile->status() == 200) {
                        $profile = $profile->json();
                        if (!$profile['is_employer']) {
                            $this->message = 'Аккаунт не является работадателем';
                            $this->status = 400;
                        } else {
                            $data['employer_id'] = $profile['employer']['id'];
                            $this->message = 'Авторизация прошла успешно';
                            $this->status = 200;
                        }
                    }

                    HeadHunter::create($data);
                    Cookie::forget($this->COOKIE_ID_CUSTOMER);
                    $url = config('hh.front_save_ids');
                    $queryParams = [
                        'popup_account' => 'true',
                        'platform' => 'hh',
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
        string|null $clientId = null,
        string|null $secretId =
        null
    ): bool|array {
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
            $response = PlatformHh::requirePostPlatform(null, config('hh.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => time() + $data['expires_in'],
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

        $response = PlatformHh::requireGetPlatform($customerToken, $data['url']);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getProfile(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');
        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_profile_url'));

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
    }

    public function getAvailableTypes(Request $request)
    {
        $customerToken = $request->attributes->get('token');
        try {
            $data = $request->validate([
                'employer_id' => 'required|string|min:1|max:20',
                'manager_id' => 'required|string|min:1|max:20',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ошибка валидации',
            ], 422);
        }
        $url = config('hh.get_available_types')['url'] . $data['employer_id'] . config('hh.get_available_types')
        ['folder'] .
            $data['manager_id'] . config('hh.get_available_types')['catalog'];
        $response = PlatformHh::requireGetPlatform($customerToken, $url);

        return response()->json([
            'message' => 'Success',
            'data' => $response->json()
        ]);
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

        // Определяем endpoint в зависимости от параметра archived
        $archived = $request->get('archived');
        $folder = ($archived === 'true')
            ? config('hh.get_publications')['folder_archived']
            : config('hh.get_publications')['folder'];

        $pubEndpoint = config('hh.get_publications')['url'] . $customerToken['employer_id'] . $folder;
        $data = PlatformHh::requireGetPlatform($customerToken['token'], $pubEndpoint)->json();

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ]);
    }
    public function getPublication(Request $request, int $id): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_publication') . $id);

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
        $employerId = $request->attributes->get('employer_id');

        $response = PlatformHh::requireGetPlatform(
            $customerToken,
            config('hh.get_vacancies')['url'] . $employerId . config('hh.get_vacancies')['folder']
        );

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Вакансий  не найдено' : 'Ошибка получения вакансий',
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
        $response = PlatformHh::requirePostPlatform($customerToken, config('hh.get_publication'), $data);

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

        $response = PlatformHh::requirePostPlatform($customerToken, config('hh.get_publication'), $data, true);

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

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_drafts'));

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

    public function getProfessionals(Request $request): JsonResponse
    {
        $customerToken = $request->attributes->get('token');

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_professional_roles'));

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

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_vacancy_responses') . $id);

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
                'message' => 'Не передан идентификатор вакансии',
                'data' => []
            ], 422);
        }

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_vacancy_response') . $id);

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

    public function closeAuth(Request $request) {
        $customerId = $request->attributes->get('customer_id');
        $customer = Customer::find($customerId);

        if (empty($customer)) {
            return response()->json([
                'message' => 'Пользователь не найден',
            ], 404);
        }

        $hh = HeadHunter::where('customer_id', $customerId);

        if (empty($hh)) {
            return response()->json([
                'message' => 'Аккаунт не найден',
            ]);
        }

        $hh->delete();
        return response()->json([
            'message' => 'Аккаунт hh.ru успешно отвязан от платформы',
        ]);
    }

    public function getAddresses(Request $request) {
        $customerToken = $request->attributes->get('token');
        $employerId = $request->attributes->get('employer_id');

        $response = PlatformHh::requireGetPlatform(
            $customerToken,
            config('hh.get_addresses')['url'] . $employerId . config('hh.get_addresses')['folder']
        );

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Адресов  не найдено' : 'Ошибка получения адресов',
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

        $response = PlatformHh::requireGetPlatform($customerToken, config('hh.get_publication') . $id . '/visitors');

        if ($response->status() != 200) {
            return response()->json([
                'message' => $response->status() == 404 ? 'Отклики не найдены' : 'Ошибка получения откликов',
                'data' => []
            ], $response->status());
        }

        return response()->json([
            'message' => 'Количество просмотров за последнюю неделю успешно получено',
            'data' => $response['found']
        ]);
    }
}
