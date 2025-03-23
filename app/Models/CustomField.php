<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = [
        'name',
        'type_id',
        'require'
    ];

    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(CustomFieldType::class);
    }
}
