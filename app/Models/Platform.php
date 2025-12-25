<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Platform extends Model
{
    protected $fillable = [
        'id',
        'name',
    ];

    public function vacancies(): BelongsToMany {
        return $this->belongsToMany(Vacancy::class, 'vacancy_platform', 'platform_id', 'vacancy_id');
    }
}
