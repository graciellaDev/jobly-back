<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'job',
        'location',
        'phone',
        'description',
        'education',
        'link',
        'vacancy',
        'experience',
        'telegram',
        'skype',
        'imagePath',
        'resumePath',
        'coverPath',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public  function customFields()
    {
        return $this->belongsToMany(CustomField::class)->withPivot('name');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function attachments()
    {
        return $this->belongsToMany(AttachmentCandidate::class);
    }

    public function fields()
    {
        return $this->belongsToMany(CustomField::class);
    }

    public function funnelStages()
    {
        return $this->belongsTo(FunnelStage::class, 'candidate_funnel_stage', 'candidate_id', 'funnel_stage_id');
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
