<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funnel extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public $timestamps = false;

    protected $casts = [
        'stage' => 'array'
    ];

    public function stages()
    {
        return $this->belongsToMany(Stage::class, 'funnel_stage', 'funnel_id', 'stage_id');
    }
}
