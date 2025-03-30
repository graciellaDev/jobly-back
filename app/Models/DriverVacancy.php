<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverVacancy extends Model
{
    protected $fillable = [
        'id',
        'driver_id',
        'vacancy_id'
    ];

    public $timestamps = false; // Отключаем временные метки
    protected $table = 'driver_vacancy';
}
