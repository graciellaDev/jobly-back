<?php

namespace App\Models;

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
}
