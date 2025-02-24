<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vacancy extends Model
{
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class);
    }
}
