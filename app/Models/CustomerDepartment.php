<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDepartment extends Model
{
    protected $table = 'customer_department';
    protected $fillable = [
        'department_id',
        'customer_id'
    ];
}
