<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePagePermission extends Model
{
    protected $fillable = [
        'role_id',
        'page_key',
    ];
}
