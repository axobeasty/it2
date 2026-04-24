<?php

namespace App\Models;

use App\Support\TestGrading;
use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    protected $table = 'test_attempts';

    protected $fillable = [
        'test_id',
        'student_id',
        'group_id',
        'score',
        'max_score',
        'percentage',
        'grade',
        'status',
        'started_at',
        'submitted_at',
        'answers_json',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function student()
    {
        return $this->belongsTo(Employee::class, 'student_id');
    }

    /**
     * Оценка для отображения: из БД или пересчёт по проценту (старые записи).
     */
    public function getDisplayGradeAttribute(): string
    {
        $stored = $this->attributes['grade'] ?? null;
        if ($stored !== null && $stored !== '') {
            return (string) $stored;
        }

        return TestGrading::fromPercentage((float) $this->percentage);
    }
}
