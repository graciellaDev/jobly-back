<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public function vacancies() {
        return $this->belongsToMany(Vacancy::class);
    }
}
