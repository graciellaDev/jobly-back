<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function funnels(): BelongsToMany
    {
        return $this->belongsToMany(Funnel::class);
    }

    public function relations(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_relations', 'user_id', 'customer_id');
    }
}
