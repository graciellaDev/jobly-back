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

    public function countVacancyCandidates(int $vacancyId, ?int $customerId = null)
    {
        $query = $this->candidates()->where('vacancy_id', $vacancyId);

        if ($customerId !== null && $customerId > 0) {
            $query->where('customer_id', $customerId);
        }

        return $query->count();
    }
}
