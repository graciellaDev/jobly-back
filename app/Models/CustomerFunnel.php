<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFunnel extends Model
{
    protected $fillable = [
        'id',
        'customer_id',
        'funnel_id'
    ];

    public $timestamps = false;

    protected $table = 'customer_funnel';
}
