<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    protected $fillable = [
        'position',
        'division',
        'count',
        'salaryFrom',
        'salaryTo',
        'currency',
        'require',
        'duty',
        'reason',
        'dateStart',
        'dateWork',
        'customer_id',
        'vacancy_id',
        'status_id',
        'client_id',
        'executor_id',
        'responsible_id',
        'city'
    ];

    protected $hidden = [
        'customer_id',
        'vacancy_id',
        'status_id',
        'client_id',
        'executor_id',
        'responsible_id'
    ];
    protected $casts = [
        'created_at' => 'date:d.m.Y',
        'updated_at' => 'date:d.m.Y',
        'dateStart' => 'date:d.m.Y',
        'dateWork' => 'date:d.m.Y',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class)->select(['id', 'name'])->withCount('candidates');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->select(['id', 'name']);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->with('role')->select(['id', 'name', 'role_id']);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->with('role')->select(['id', 'name', 'role_id']);
    }

    public function approvals(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'approval_application',
            'application_id',
            'customer_id'
        )->withPivot(['description', 'status_id'])
            ->withTimestamps();
    }
}
