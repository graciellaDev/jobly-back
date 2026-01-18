<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VacancyPlatformController extends Controller
{
    use HasFactory;

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'vacancy_platform';

    /**
     * Отключение автоинкремента для сводной таблицы.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Поля, которые можно массово назначать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vacancy_id',
        'platform_id',
        'base_vacancy_id',
    ];

    /**
     * Получить вакансию.
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class, 'vacancy_id');
    }

    /**
     * Получить платформу.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    /**
     * Получить базовую (родительскую) вакансию.
     * Используется для связи импортированных вакансий с основной вакансией.
     */
    public function baseVacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class, 'base_vacancy_id');
    }

    /**
     * Scope: публикации с конкретной платформы.
     */
    public function scopeFromPlatform($query, int $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * Scope: публикации для конкретной базовой вакансии.
     * Используется для получения всех импортированных вакансий для основной вакансии.
     */
    public function scopeForBaseVacancy($query, int $baseVacancyId)
    {
        return $query->where('base_vacancy_id', $baseVacancyId);
    }

    /**
     * Scope: импортированные вакансии (имеют базовую вакансию).
     */
    public function scopeImported($query)
    {
        return $query->whereNotNull('base_vacancy_id');
    }
}
