<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phrase extends Model
{
    protected $fillable = [
        'name'
    ];

    public $timestamps = false;

    public function vacancies()
    {
        return $this->belongsToMany(Vacancy::class);
    }
}
