<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'tests';

    protected $fillable = [
        'title',
        'description',
        'time_limit_minutes',
        'attempts_limit',
        'is_active',
        'created_by',
    ];

    public function questions()
    {
        return $this->hasMany(TestQuestion::class, 'test_id')->orderBy('sort_order');
    }

    public function assignments()
    {
        return $this->hasMany(TestGroupAssignment::class, 'test_id');
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class, 'test_id');
    }
}
