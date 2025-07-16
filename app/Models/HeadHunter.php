<?php

namespace App\Models;

use App\Helpers\PlatformHh;
use Illuminate\Database\Eloquent\Model;

class HeadHunter extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'expired_in',
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
        $clientId = config('hh.client_id');
        $clientSecret = config('hh.client_secret');
        if (!$this->refresh_token || !$this->access_token) {
            return false;
        } else {
            $formData = [
                'refresh_token' => $this->refresh_token,
//                'client_id'     => $clientId,
//                'client_secret' => $clientSecret,
//                'access_token' => $this->access_token,
                'grant_type' => 'refresh_token',
//                'redirect_uri' => config('hh.redirect_url'),
            ];

            $response = PlatformHh::requirePostPlatform($this->access_token, config('hh.get_token_url'), $formData);

            if ($response->status() == 200) {
                $data = $response->json();
                return [
                    'access_token' => $data['access_token'],
                    'expired_in' => $data['expires_in'] + time(),
                    'refresh_token' => $data['refresh_token']
                ];
            } else {
                return false;
            }
        }
    }
}
