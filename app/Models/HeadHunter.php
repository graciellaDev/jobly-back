<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeadHunter extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'id_client',
        'is_secret',
        'expired_in',
        'access_token',
        'refresh_token'
    ];
}
