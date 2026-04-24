<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupScheduleEntry extends Model
{
    public const BUILDING_LABELS = [
        'main' => 'Основное здание',
        'second' => 'Второе здание',
        'third' => 'Третье здание',
    ];

    public const WEEKDAY_LABELS = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

    protected $fillable = [
        'group_id',
        'teacher_id',
        'week_start_date',
        'weekday',
        'lesson_slot',
        'start_time',
        'end_time',
        'subject_title',
        'schedule_subject_id',
        'room',
        'building',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Groups::class, 'group_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    public function scheduleSubject(): BelongsTo
    {
        return $this->belongsTo(ScheduleSubject::class, 'schedule_subject_id');
    }

    public function buildingLabel(): string
    {
        return self::BUILDING_LABELS[$this->building] ?? $this->building;
    }

    public function weekdayLabel(): string
    {
        return self::WEEKDAY_LABELS[(int) $this->weekday] ?? (string) $this->weekday;
    }
}
