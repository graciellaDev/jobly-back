<?php

namespace App\Models;

use App\Helpers\PlatformAvito;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avito extends Model
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
        $clientId = config('avito.client_id');
        $clientSecret = config('avito.client_secret');
        if (!$this->refresh_token || !$this->access_token) {
            return false;
        } else {
            $formData = [
                'refresh_token' => $this->refresh_token,
                'grant_type' => 'refresh_token',
            ];

            $response = PlatformAvito::requirePostPlatform($this->access_token, config('avito.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'] + time(),
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }
}



