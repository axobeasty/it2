<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $fillable = [
        'name',
        'is_system',
    ];

    public function pagePermissions()
    {
        return $this->hasMany(RolePagePermission::class, 'role_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id');
    }
}
