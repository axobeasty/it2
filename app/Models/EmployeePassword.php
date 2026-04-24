<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePassword extends Model
{
    protected $fillable = [
        'employee_id',
        'title',
        'login',
        'url',
        'notes',
        'password_encrypted',
    ];
}
