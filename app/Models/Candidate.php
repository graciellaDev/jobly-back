<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'firstname',
        'surname',
        'patronymic',
        'email',
        'phone',
        'location',
        'stage_id',
        'phone',
        'quickInfo',
        'education',
        'icon',
        'link',
        'vacancy_id',
        'customer_id',
        'experience',
        'telegram',
        'skype',
        'imagePath',
        'isPng',
        'resume',
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
        return $this->hasMany(AttachmentCandidate::class);
    }

    public function fields()
    {
        return $this->belongsToMany(CustomField::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stages');
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
