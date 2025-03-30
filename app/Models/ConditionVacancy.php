<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConditionVacancy  extends Pivot
{
    protected $fillable = [
        'id',
        'vacancy_id',
        'condition_id'
    ];

    public $timestamps = false; // Отключаем временные метки
}
