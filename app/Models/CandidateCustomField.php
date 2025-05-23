<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateCustomField extends Model
{
    protected $fillable = [
        'id',
        'candidate_id',
        'custom_field_id',
        'value'
    ];

    protected $table = 'candidate_custom_field_values';

    public $timestamps = false; // Отключаем временные метки
}
