<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public $timestamps = false;

    protected $hidden = ['pivot'];

    public function funnels()
    {
        return $this->belongsToMany(Funnel::class);
    }
}
