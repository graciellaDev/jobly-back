<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRelation extends Model
{
    protected $table = 'customer_relations';
    protected $fillable = ['user_id', 'customer_id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function relatedCustomer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
