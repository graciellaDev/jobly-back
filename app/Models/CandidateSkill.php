<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateSkill extends Model
{
    protected $fillable = [
        'id',
        'candidate_id',
        'skill_id'
    ];
}
