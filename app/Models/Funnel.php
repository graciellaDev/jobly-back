<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    protected $hidden = [
        'fixed'
    ];

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'funnel_stage', 'funnel_id', 'stage_id');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class);
    }
}
