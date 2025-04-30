<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'login',
        'password',
        'phone',
        'site',
        'from_source',
        'role_id'
    ];

    protected $hidden = [
        'role_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
