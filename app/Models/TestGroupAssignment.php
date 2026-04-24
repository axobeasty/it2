<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestGroupAssignment extends Model
{
    protected $table = 'test_group_assignments';

    protected $fillable = [
        'test_id',
        'group_id',
        'starts_at',
        'ends_at',
        'is_published',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function group()
    {
        return $this->belongsTo(Groups::class, 'group_id');
    }
}
