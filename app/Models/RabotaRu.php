<?php

namespace App\Models;

use App\Helpers\PlatformRabotaRu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RabotaRu extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'expires_in',
        'access_token',
        'refresh_token',
        'employer_id'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getRefreshToken(): bool | array
    {
        $appId = config('rabota.app_id');
        $secret = config('rabota.secret');
        if (!$this->access_token) {
            return false;
        } else {
            $time = time();
            $formData = [
                'app_id' => $appId,
                'time' => $time,
                'token' => $this->access_token
            ];

            // Генерируем подпись запроса
            $signature = PlatformRabotaRu::getSignature($formData, $secret);
            $formData['signature'] = $signature;

            $response = PlatformRabotaRu::requirePostPlatform(null, config('rabota.refresh_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => time() + ($data['expires_in'] ?? 3600),
                    'refresh_token' => $data['access_token'] // В Rabota.ru refresh_token не возвращается отдельно
                ];
            } else {
                return false;
            }
        }
    }
}
