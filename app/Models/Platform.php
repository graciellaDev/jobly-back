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
    public $timestamps = true;
    protected $table = 'platforms';
    public function vacancies(): BelongsToMany {
        return $this->belongsToMany(Vacancy::class, 'vacancy_platform', 'platform_id', 'vacancy_id')
            ->withPivot('base_vacancy_id', 'vacancy_platform_id');
    }
}
