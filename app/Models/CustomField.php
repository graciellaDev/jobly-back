<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = [
        'value',
        'type_id',
        'require'
    ];

    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(CustomFieldType::class);
    }

    public function canditates()
    {
        return $this->belongsToMany(Candidate::class)->withPivot('name');
    }
}
