<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public $timestamps = false;

    protected $hidden = ['pivot'];

    public function funnels()
    {
        return $this->belongsToMany(Funnel::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function countCandidates()
    {
        return $this->candidates()->count();
    }

    public function countVacancyCandidates(int $vacancyId)
    {
        return $this->candidates()->where('vacancy_id', $vacancyId)->count();
    }
}
