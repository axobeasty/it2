<?php

namespace App\Support;

/**
 * Пятибалльная оценка по проценту выполнения (доля верных ответов).
 */
class TestGrading
{
    public static function fromPercentage(float $percentage): string
    {
        if ($percentage >= 90) {
            return '5';
        }
        if ($percentage >= 75) {
            return '4';
        }
        if ($percentage >= 60) {
            return '3';
        }

        return '2';
    }

    public static function labelRu(string $grade): string
    {
        return match ($grade) {
            '5' => 'отлично',
            '4' => 'хорошо',
            '3' => 'удовлетворительно',
            '2' => 'неудовлетворительно',
            default => $grade,
        };
    }
}
