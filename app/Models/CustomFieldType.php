<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldType extends Model
{
    protected $fillable = [
        'name',
        'multiply'
    ];
}
