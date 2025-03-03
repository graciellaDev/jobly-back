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
        'code',
        'specializations',
        'industry',
        'employment',
        'schedule',
        'experience',
        'education',
        'salary_from',
        'salary_to',
        'place',
        'currency',
        'location',
        'phrases'
    ];

    protected $casts = [
        'industry' => 'array',
        'condition' => 'array',
        'driver' => 'array'
    ];

    public $timestamps = true;

    public function industries() {
        return $this->belongsToMany(Industry::class);
    }

    public function drivers() {
        return $this->belongsToMany(Driver::class);
    }

    public function conditions() {
        return $this->belongsToMany(Condition::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function additions()
    {
        return $this->belongsToMany(Addition::class);
    }
}
