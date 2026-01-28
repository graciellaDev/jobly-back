<?php

/*
  |--------------------------------------------------------------------------
  | Api Head Hunter
  |--------------------------------------------------------------------------
  |
  | Статические данные для интеграции в hh.ru по api
  |
*/
return [
    'client_id' => env('HH_CLIENT_ID'),
    'client_secret' => env('HH_CLIENT_SECRET'),
    'auth_url' => env('HH_AUTH_URL'),
    'content_type' => 'application/x-www-form-urlencoded',
    'content-type-json' => 'application/json',
    'redirect_url' => env('APP_URL') . '/api/code-hh',
    'front_save_ids' => env('URL_FRONT') . '/vacancies/PremiumPlatforms',
    'get_token_url' => env('HH_DOMAIN') . '/token',
    'get_profile_url' => env('HH_DOMAIN') . '/me',
    'get_publications' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/vacancies/active',
        'folder_archived' => '/vacancies/archived'
    ],
    'get_publication' => env('HH_DOMAIN') . '/vacancies/',
    'get_drafts' => env('HH_DOMAIN') . '/vacancies/drafts',
    'get_available_types' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/managers/',
        'catalog' => '/vacancies/available_types'
    ],
    'get_available_publications' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/services/available_publications'
    ],
    'get_vacancies' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/vacancies/active'
    ],
    'get_professional_roles' => env('HH_DOMAIN') . '/professional_roles',
    'get_vacancy_responses' => env('HH_DOMAIN') . '/negotiations/response?vacancy_id=',
    'get_vacancy_response' => env('HH_DOMAIN') . '/negotiations/',
    'get_addresses' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/addresses'
    ],
];
