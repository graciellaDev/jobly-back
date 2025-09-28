<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentDivision extends Model
{
    protected $table = 'department_division';
    protected $fillable = [
        'id',
        'department_id',
        'division'
    ];
    public $timestamps = false;

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
