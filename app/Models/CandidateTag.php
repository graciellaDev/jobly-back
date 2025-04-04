<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateTag extends Model
{
    protected $fillable = [
        'id',
        'candidate_id',
        'tag_id'
    ];

    protected $table = 'candidate_tag';
}
