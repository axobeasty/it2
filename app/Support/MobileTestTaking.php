<?php

namespace App\Support;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Логика прохождения тестов для мобильного API (без сессии браузера).
 */
final class MobileTestTaking
{
    public static function findAvailableTest(int $groupId, int $testId): ?Test
    {
        if ($groupId <= 0) {
            return null;
        }

        return Test::query()
            ->where('tests.id', $testId)
            ->where('tests.is_active', true)
            ->whereExists(function ($query) use ($groupId) {
                $query->select(DB::raw(1))
                    ->from('test_group_assignments as tga')
                    ->whereColumn('tga.test_id', 'tests.id')
                    ->where('tga.group_id', $groupId)
                    ->where('tga.is_published', true)
                    ->where(function ($q) {
                        $q->whereNull('tga.starts_at')->orWhere('tga.starts_at', '<=', Carbon::now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('tga.ends_at')->orWhere('tga.ends_at', '>=', Carbon::now());
                    });
            })
            ->with(['questions' => fn ($q) => $q->orderBy('sort_order')])
            ->first();
    }

    public static function canStartAttempt(int $testId, int $studentId, $attemptsLimit): bool
    {
        $limit = (int) ($attemptsLimit ?? 0);
        if ($limit <= 0) {
            return true;
        }

        $attemptsCount = TestAttempt::where('test_id', $testId)
            ->where('student_id', $studentId)
            ->count();

        return $attemptsCount < $limit;
    }

    /**
     * @return array{score: int, max_score: int, percentage: float, grade: string}
     */
    public static function grade(Test $test, array $answers): array
    {
        $score = 0;
        $maxScore = 0;

        foreach ($test->questions as $question) {
            $points = max(1, (int) $question->points);
            $maxScore += $points;
            $given = self::answerFromPayload($answers, (int) $question->id);
            if (self::isCorrectAnswer($question, $given)) {
                $score += $points;
            }
        }

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0.0;
        $grade = TestGrading::fromPercentage((float) $percentage);

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'grade' => $grade,
        ];
    }

    public static function answerFromPayload(array $answers, int $questionId): mixed
    {
        return $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
    }

    public static function isCorrectAnswer(TestQuestion $question, mixed $answer): bool
    {
        $type = $question->type;
        $correct = (array) json_decode((string) $question->correct_answer_json, true);

        if ($type === 'single') {
            if ($answer === null || $answer === '') {
                return false;
            }

            return in_array((int) $answer, array_map('intval', (array) ($correct['indexes'] ?? [])), true);
        }

        if ($type === 'multiple') {
            if ($answer === null || $answer === '' || (is_array($answer) && count($answer) === 0)) {
                return false;
            }
            $given = array_map('intval', (array) $answer);
            sort($given);
            $expected = array_map('intval', (array) ($correct['indexes'] ?? []));
            sort($expected);

            return $given === $expected;
        }

        if ($type === 'match') {
            if ($answer === null || ! is_array($answer) || count($answer) === 0) {
                return false;
            }
            $given = (array) $answer;
            $expected = (array) ($correct['pairs'] ?? []);
            ksort($given);
            ksort($expected);

            return $given === $expected;
        }

        if ($type === 'word') {
            if ($answer === null || trim((string) $answer) === '') {
                return false;
            }
            $normalized = mb_strtolower(trim((string) $answer));

            return in_array($normalized, (array) ($correct['accepted'] ?? []), true);
        }

        return false;
    }

    /**
     * Вопрос для клиента без правильных ответов.
     *
     * @return array<string, mixed>
     */
    public static function serializeQuestionForClient(TestQuestion $question): array
    {
        $opts = (array) json_decode((string) $question->options_json, true);
        $base = [
            'id' => (int) $question->id,
            'type' => $question->type,
            'question_text' => $question->question_text,
            'points' => max(1, (int) $question->points),
            'sort_order' => (int) $question->sort_order,
        ];

        if ($question->type === 'single' || $question->type === 'multiple') {
            $base['options'] = array_values($opts['options'] ?? []);

            return $base;
        }

        if ($question->type === 'match') {
            $base['left'] = array_values($opts['left'] ?? []);
            $base['right'] = array_values($opts['right'] ?? []);

            return $base;
        }

        return $base;
    }
}
