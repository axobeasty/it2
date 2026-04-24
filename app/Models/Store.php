<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'inv_number',
        'count',
        'inv_type_id',
        'is_enabled',
    ];

    public function type()
    {
        return $this->belongsTo(Inv_Type::class, 'inv_type_id');
    }
}
