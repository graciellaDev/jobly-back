<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'role_id'
    ];

    protected $table = 'customers';

    protected $hidden = [
        'customer_id',
    ];

    protected $casts = [
        'created_at' => 'date:Y.m.d',
        'updated_at' => 'date:Y.m.d'
    ];
}
