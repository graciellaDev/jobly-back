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
    'client_id' => '
IJL4TE2E48Q3CGCHQUGR7005VSAJ52SI8SJET21COIDCFCK88LJDIFFIJD3TJ23F',
    'client_secret' => 'LJ2S9F3JBODQNC2S35EFK48O9ITL8N6KE2M9HTAUEOCIBV0FPI927EH0DCBRQGCQ',
    'auth_url' => 'https://hh.ru/oauth/authorize',
    'redirect_url' => env('APP_URL') . '/api/hh/auth',
    'front_save_ids' => 'https://job-ly.ru/vacancies/PremiumPlatforms?popup_account=true&platform=hh',
    'get_token_url' => 'https://api.hh.ru/token',
    'get_profile_url' => 'https://api.hh.ru/me'
];
