<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addition extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    protected $hidden = ['pivot'];

    public function vacancies() {
        return $this->belongsToMany(Vacancy::class);
    }
}
