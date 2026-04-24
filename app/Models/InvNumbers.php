<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvNumbers extends Model
{
    protected $table = 'inv_numbers';

    protected $fillable = [
        'number',
        'date_in',
        'date_out',
        'room',
        'employees_id',
        'store_id',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employees_id');
    }
}
