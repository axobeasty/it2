<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = [
        'name',
    ];

    public function chairs()
    {
        return $this->hasMany(Chair::class, 'faculty_id');
    }
}
