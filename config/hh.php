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
    'redirect_url' => env('APP_URL') . '/api/code-hh',
    'front_save_ids' => env('URL_FRONT') . '/vacancies/PremiumPlatforms',
    'get_token_url' => env('HH_DOMAIN') . '/token',
    'get_profile_url' => env('HH_DOMAIN') . '/me',
    'get_publications' => [
        'url' => env('HH_DOMAIN') . '/employers/',
        'folder' => '/vacancies/active'
    ],
    'get_publication' => env('HH_DOMAIN') . '/vacancies/',
    'get_drafts' => env('HH_DOMAIN') . '/vacancies/drafts'
];
