<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name'
    ];

    public $timestamps = false;

    public function candidates()
    {
        return $this->belongsToMany(Candidate::class);
    }
}
