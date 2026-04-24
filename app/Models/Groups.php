<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function students()
    {
        return $this->hasMany(Employee::class, 'group_id');
    }

    public function scheduleEntries()
    {
        return $this->hasMany(GroupScheduleEntry::class, 'group_id');
    }
}
