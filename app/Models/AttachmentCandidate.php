<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttachmentCandidate extends Model
{
    protected $fillable = [
        'id',
        'link',
        'candidate_id'
    ];

    protected $hidden = [
        'candidate_id'
    ];

    public $timestamps = false;
}
