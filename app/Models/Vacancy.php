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
        'dateEnd',
        'code',
        'specializations',
        'industry',
        'employment',
        'schedule',
        'experience',
        'education',
        'salary_from',
        'salary_to',
        'salary_type',
        'places',
        'currency',
        'location',
        'customer_id',
        'executor_name',
        'executor_phone',
        'executor_email',
        'executor_id',
        'status',
        'show_executor',
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


    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function candidates()
    {
        return $this->belongsToMany(Candidate::class);
    }

    public function phrases()
    {
        return $this->belongsToMany(Phrase::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_vacancy', 'vacancy_id', 'customer_id');
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'coordinating_vacancy', 'vacancy_id', 'customer_id');
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'vacancy_platform', 'vacancy_id', 'platform_id')
            ->withPivot('base_vacancy_id');
    }
}
