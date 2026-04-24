<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleConstructorSetting extends Model
{
    public const DURATION_OPTIONS = [
        40 => '40 минут',
        45 => '45 минут',
        90 => 'Полтора часа (90 мин.)',
    ];

    protected $fillable = [
        'first_lesson_start',
        'lesson_duration_minutes',
        'break_minutes',
        'max_slots_per_day',
    ];

    public static function current(): self
    {
        $row = static::query()->first();
        if (! $row) {
            $row = static::create([
                'first_lesson_start' => '09:00:00',
                'lesson_duration_minutes' => 45,
                'break_minutes' => 10,
                'max_slots_per_day' => 12,
            ]);
        }

        return $row;
    }
}
