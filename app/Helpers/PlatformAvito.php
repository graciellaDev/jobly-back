<?php

namespace App\Helpers;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PlatformAvito
{
    public static function requireGetPlatform(string $token, string $url, array $queryParams = []): PromiseInterface | Response
    {
        $headers = [
            'Authorization' => 'Bearer ' . $token
        ];

        $request = Http::withHeaders($headers);

        // Если есть query параметры, добавляем их к URL
        if (!empty($queryParams)) {
            return $request->get($url, $queryParams);
        }

        return $request->get($url);
    }

    public static function requirePostPlatform(string | null $token, string $url, array $data, bool $jsonData = false):
    PromiseInterface |
    Response
    {
        $headers = [];

        if (!empty($token)) {
            $headers['Authorization'] = "Bearer $token";
        }

        if ($jsonData) {
            $headers['Content-Type']  = config('avito.content-type-json');
            return  Http::withHeaders($headers)->asJson()->post($url, $data);
        } else {
            $headers['Content-Type']  = config('avito.content_type');
            return  Http::withHeaders($headers)->asForm()->post($url, $data);
        }
    }
}



