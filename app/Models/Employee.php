<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public function canAccessPage(string $pageKey): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role.pagePermissions');
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->pagePermissions->contains('page_key', $pageKey);
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function group()
    {
        return $this->belongsTo(Groups::class, 'group_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function chair()
    {
        return $this->belongsTo(Chair::class, 'chair_id');
    }

    public function groupScheduleEntriesAsTeacher()
    {
        return $this->hasMany(GroupScheduleEntry::class, 'teacher_id');
    }
}
