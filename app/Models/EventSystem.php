<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSystem extends Model
{
    protected $table = 'events_system';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'content',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
