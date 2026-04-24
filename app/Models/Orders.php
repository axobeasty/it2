<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $table = 'orders';
    protected $fillable = ['description', 'category_id', 'employee_id', 'status', 'room', 'file_path'];
    public function category()
    {
        return $this->belongsTo(O_Categories::class, 'category_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
    public function get_fio()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }
}
