<?php

/*
  |--------------------------------------------------------------------------
  | Api Rabota.ru
  |--------------------------------------------------------------------------
  |
  | Статические данные для интеграции в rabota.ru по api
  |
*/
return [
    'app_id' => env('RABOTA_APP_ID'),
    'secret' => env('RABOTA_SECRET'),
    'domain' => env('RABOTA_DOMAIN', 'https://api.rabota.ru'),
    'auth_url' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/oauth/authorize.html',
    'content_type' => 'application/x-www-form-urlencoded',
    'content-type-json' => 'application/json',
    'redirect_url' => env('APP_URL') . '/api/code-rabota',
    'front_save_ids' => env('URL_FRONT') . '/vacancies/PremiumPlatforms',
    'get_token_url' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/oauth/token.json',
    'refresh_token_url' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/oauth/refresh-token.json',
    'get_profile_url' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me.json',
    'scope' => env('RABOTA_SCOPE', 'profile,vacancies'),
    // Согласно OpenAPI v4: /me/vacancies.json (POST) - список вакансий
    'get_publications' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies.json',
    'get_publications_archived' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies/archive.json',
    // Согласно OpenAPI v4: /me/vacancies.json (POST) с параметром vacancy_id для получения конкретной вакансии
    'get_publication' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies.json',
    // Согласно OpenAPI v4: черновики получаются через /me/vacancies.json с фильтром
    'get_drafts' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies.json',
    // Согласно OpenAPI v4: доступные типы через /me/vacancies/filters.json
    'get_available_types' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies/filters.json',
    // Согласно OpenAPI v4: список вакансий через /me/vacancies.json
    'get_vacancies' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies.json',
    // Согласно OpenAPI v4: профессиональные роли через справочники
    'get_professional_roles' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/dictionaries/professional_roles.json',
    // Согласно OpenAPI v4: отклики через /me/responses.json или /me/company/responses.json
    'get_vacancy_responses' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/responses.json',
    'get_company_responses' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/company/responses.json',
    // Согласно OpenAPI v4: конкретный отклик через /me/response/contact.json
    'get_vacancy_response' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/response/contact.json',
    // Согласно OpenAPI v4: адреса через /me/company/addresses.json
    'get_addresses' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/company/addresses.json',
    // Согласно OpenAPI v4: статистика просмотров через /me/vacancies/responses/counters.json
    'get_count_visitors' => env('RABOTA_DOMAIN', 'https://api.rabota.ru') . '/v4/me/vacancies/responses/counters.json',
];
