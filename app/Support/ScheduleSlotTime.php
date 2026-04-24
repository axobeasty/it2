<?php

namespace App\Support;

use App\Models\ScheduleConstructorSetting;
use Carbon\Carbon;

class ScheduleSlotTime
{
    /**
     * @return array{start_time: string, end_time: string} В формате H:i:s
     */
    public static function timesForSlot(ScheduleConstructorSetting $cfg, int $slot): array
    {
        $duration = (int) $cfg->lesson_duration_minutes;
        $breakM = (int) $cfg->break_minutes;
        $base = Carbon::parse($cfg->first_lesson_start);
        $start = $base->copy()->addMinutes(($slot - 1) * ($duration + $breakM));
        $end = $start->copy()->addMinutes($duration);

        return [
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ];
    }

    /**
     * @return array<int, string> [номер_пары => подпись]
     */
    public static function slotOptions(ScheduleConstructorSetting $cfg): array
    {
        $max = max(1, min(20, (int) $cfg->max_slots_per_day));
        $out = [];
        for ($s = 1; $s <= $max; $s++) {
            $t = self::timesForSlot($cfg, $s);
            $out[$s] = sprintf(
                '%d-я пара: %s — %s',
                $s,
                substr($t['start_time'], 0, 5),
                substr($t['end_time'], 0, 5)
            );
        }

        return $out;
    }

    /**
     * Подобрать номер пары по времени начала (для старых записей без lesson_slot).
     */
    public static function inferSlotFromStart(ScheduleConstructorSetting $cfg, string $startTime): ?int
    {
        $normalized = strlen($startTime) >= 8 ? substr($startTime, 0, 8) : Carbon::parse($startTime)->format('H:i:s');
        foreach (array_keys(self::slotOptions($cfg)) as $slot) {
            $t = self::timesForSlot($cfg, $slot);
            if ($t['start_time'] === $normalized) {
                return (int) $slot;
            }
        }

        return null;
    }

    /**
     * Совпадают ли сохранённые в записи времена с расчётом по текущей сетке для данного номера пары.
     */
    public static function storedTimesMatchCurrentGridForSlot(
        ScheduleConstructorSetting $cfg,
        int $slot,
        string $storedStart,
        string $storedEnd
    ): bool {
        $t = self::timesForSlot($cfg, $slot);
        $ns = self::normalizeTimeToHis($storedStart);
        $ne = self::normalizeTimeToHis($storedEnd);

        return $t['start_time'] === $ns && $t['end_time'] === $ne;
    }

    private static function normalizeTimeToHis(string $time): string
    {
        return strlen($time) >= 8 ? substr($time, 0, 8) : Carbon::parse($time)->format('H:i:s');
    }
}
