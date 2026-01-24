<?php

namespace App\Helpers;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PlatformRabotaRu
{
    /**
     * Генерация подписи запроса согласно документации Rabota.ru
     * @param array $params Параметры запроса (без signature)
     * @param string $secret Секрет приложения
     * @return string SHA256 хеш подписи
     */
    public static function getSignature(array $params, string $secret): string
    {
        // Приводим все значения к типу строка (string)
        foreach ($params as $k => $v) {
            $params[$k] = (string)$v;
        }
        
        // Функция сортировки по ключам
        $sort = function ($array) use (&$sort) {
            if (!is_array($array)) return $array;
            ksort($array);
            return array_map($sort, $array);
        };
        
        // Сортируем массив
        $sortedParams = $sort($params);
        
        // Преобразуем в JSON-строку без пробелов
        $jsonString = json_encode($sortedParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Добавляем секрет и берем хеш
        return hash('sha256', $jsonString . $secret, false);
    }

    public static function requireGetPlatform(string $token, string $url): PromiseInterface | Response
    {
        return Http::withHeaders([
            'X-Token' => $token
        ])->get($url);
    }

    public static function requirePostPlatform(string | null $token, string $url, array $data, bool $jsonData = false):
    PromiseInterface |
    Response
    {
        $headers = [];

        if (!empty($token)) {
            $headers['X-Token'] = $token;
        }

        if ($jsonData) {
            $headers['Content-Type'] = config('rabota.content-type-json');
            return Http::withHeaders($headers)->asJson()->post($url, $data);
        } else {
            $headers['Content-Type'] = config('rabota.content_type');
            return Http::withHeaders($headers)->asForm()->post($url, $data);
        }
    }
}
