<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionVacancy extends Model
{
    protected $fillable = [
        'id',
        'addition_id',
        'condition_id'
    ];

    public $timestamps = false; // Отключаем временные метки

    protected $table = 'addition_vacancy';
}
