<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'places',
        'currency',
        'location',
        'phrases',
        'footerData',
        'customer_id',
        'executor_name',
        'executor_phone',
        'executor_email',
        'executor_id',
        'status'
    ];

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'driver_vacancy', 'vacancy_id', 'driver_id');
    }

    /**
     * @return BelongsToMany
     */
    public function conditions(): BelongsToMany
    {

        return $this->belongsToMany(Condition::class, 'condition_vacancy', 'vacancy_id', 'condition_id');
    }

    public function places(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function additions(): BelongsToMany
    {
        return $this->belongsToMany(Addition::class, 'addition_vacancy', 'vacancy_id');
    }

    public function footerData()
    {
        return $this->footerData = [
            'itemId' => $this->id . ' ID'
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function candidates()
    {
        return $this->belongsTo(Candidate::class);
    }
}
