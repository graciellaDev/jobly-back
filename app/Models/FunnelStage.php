<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelStage extends Model
{
    protected $fillable = [
        'funnel_id',
        'stage_id',
        'customer_id'
    ];

    public $timestamps = false;

    protected $table = 'funnel_stage';
}
