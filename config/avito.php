<?php

/*
  |--------------------------------------------------------------------------
  | Api Avito
  |--------------------------------------------------------------------------
  |
  | Статические данные для интеграции в avito.ru по api
  |
*/
return [
    'client_id' => env('AVITO_CLIENT_ID'),
    'client_secret' => env('AVITO_CLIENT_SECRET'),
    'auth_url' => env('AVITO_AUTH_URL', 'https://avito.ru/oauth'),
    'content_type' => 'application/x-www-form-urlencoded',
    'content-type-json' => 'application/json',
    'redirect_url' => env('APP_URL') . '/api/code-avito',
    'front_save_ids' => env('URL_FRONT') . '/vacancies/PremiumPlatforms',
    'get_token_url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/token',
    'get_profile_url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/accounts/self',
    'get_publications' => [
        'url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/accounts/',
        'folder' => '/items/active'
    ],
    'get_publication' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/items',
    'get_drafts' => [
        'url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/accounts/',
        'folder' => '/items/draft'
    ],
    // Endpoints для создания публикаций
    'create_publication' => [
        'url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/accounts/',
        'folder' => '/items'
    ],
    'create_draft' => [
        'url' => env('AVITO_DOMAIN', 'https://api.avito.ru') . '/core/v1/accounts/',
        'folder' => '/items/draft'
    ],
    // Scope для OAuth авторизации
    // В Avito API scope разделяются ЗАПЯТЫМИ, а не пробелами!
    // Формат: scope1,scope2,scope3 (например: messenger:read,messenger:write)
    // http_build_query автоматически кодирует двоеточия (: → %3A) и запятые
    'scope' => env('AVITO_SCOPE', 'items:info,job:applications,job:cv,job:vacancy,job:write,messenger:read,messenger:write,user_balance:read,user:read'),
];

