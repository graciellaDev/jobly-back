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
    'auth_url' => 'https://hh.ru/oauth/authorize',
    'redirect_url' => env('APP_URL') . '/api/hh/auth',
    'front_save_ids' => 'https://job-ly.ru/vacancies/PremiumPlatforms?service=hh?popup_account=true',
    'get_token_url' => 'https://api.hh.ru/token',
    'get_profile_url' => 'https://api.hh.ru/me'
];
