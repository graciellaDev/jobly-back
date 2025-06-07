<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhraseVacancy extends Model
{
    protected $fillable = [
        'id',
        'phrase_id',
        'vacancy_id',
    ];

    protected $table = 'phrase_vacancy';

    public $timestamps = false;
}
