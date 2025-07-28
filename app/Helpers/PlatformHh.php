<?php

namespace App\Helpers;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PlatformHh
{
    public static function requireGetPlatform(string $token, string $url): PromiseInterface | Response
    {
        return  Http::withHeaders([
            'Content-Type'  => config('hh.content_type'),
            'Authorization' => 'Bearer ' . $token
        ])->asForm()->get($url);
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
            $headers['Content-Type']  = config('hh.content-type-json');
            return  Http::withHeaders($headers)->asJson()->post($url, $data);
        } else {
            $headers['Content-Type']  = config('hh.content_type');
            return  Http::withHeaders($headers)->asForm()->post($url, $data);
        }
    }
}
