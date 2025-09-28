<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    protected $fillable = [
        'id',
        'name'
    ];

    public function divisions()
    {
        return $this->hasMany(DepartmentDivision::class, 'department_id', 'id');
    }
}
