<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approve extends Model
{
    protected $table = 'approval_application';
    protected $fillable = [
        'application_id',
        'customer_id',
        'executor_id',
        'description',
        'status_id',
    ];

}
