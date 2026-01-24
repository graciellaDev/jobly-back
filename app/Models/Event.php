<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

    public const TYPE_SYSTEM = 'system';
    public const TYPE_CHAT = 'chat';
    public const TYPE_EMAIL = 'email';
    public const TYPE_NOTE = 'note';
    public const TYPE_TASK = 'task';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_CALL = 'call';

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'type',
        'occurred_at',
        'author_name',
        'channel',
        'direction',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function system()
    {
        return $this->hasOne(EventSystem::class, 'event_id');
    }

    public function chat()
    {
        return $this->hasOne(EventChat::class, 'event_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function scopeForCandidateVacancy(Builder $query, int $candidateId, ?int $vacancyId)
    {
        $query->where('candidate_id', $candidateId);

        if (!is_null($vacancyId)) {
            $query->where('vacancy_id', $vacancyId);
        }

        return $query;
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }
}
