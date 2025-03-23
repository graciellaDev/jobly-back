<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttachmentCandidate extends Model
{
    protected $fillable = [
        'link',
        'candidate_id'
    ];
}
