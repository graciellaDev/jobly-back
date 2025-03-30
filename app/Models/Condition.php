<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Condition extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public $timestamps = false;

    public function vacancies(): BelongsToMany
    {
        return $this->belongsToMany(Vacancy::class, 'condition_vacancy', 'condition_id', 'vacancy_id');;
    }
}
