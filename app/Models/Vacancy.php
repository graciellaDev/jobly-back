<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vacancy extends Model
{
    protected $fillable = [
        'id',
        'name',
        'description',
        'specializations',
        'employment',
        'schedule',
        'experience',
        'education',
        'salary_from',
        'salary_to',
        'salary',
        'currency',
        'place',
        'location',
        'phrases'
    ];

    protected $casts = [
        'industry' => 'array',
        'condition' => 'array',
        'driver' => 'array'
    ];
}
