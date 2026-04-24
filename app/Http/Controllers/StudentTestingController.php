<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\Settings;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestGroupAssignment;
use App\Models\TestQuestion;
use App\Support\TestGrading;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentTestingController extends Controller
{
    public function studentIndex(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);

        $groupId = (int) ($user->group_id ?? 0);
        $tests = collect();

        if ($groupId > 0) {
            $tests = Test::query()
                ->select('tests.*')
                ->join('test_group_assignments as tga', 'tga.test_id', '=', 'tests.id')
                ->where('tga.group_id', $groupId)
                ->where('tga.is_published', true)
                ->where('tests.is_active', true)
                ->with('questions')
                ->orderByDesc('tests.created_at')
                ->distinct()
                ->get();
        }

        $attempts = TestAttempt::where('student_id', $user->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->unique('test_id')
            ->keyBy('test_id');

        $attemptsCountByTest = TestAttempt::where('student_id', $user->id)
            ->select('test_id', DB::raw('COUNT(*) as attempts_count'))
            ->groupBy('test_id')
            ->pluck('attempts_count', 'test_id');

        return view('tests.index', compact('user', 'settings', 'tests', 'attempts', 'attemptsCountByTest'));
    }

    public function take(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);
        $test = $this->getAvailableTestForStudent($user->id, (int) $user->group_id, $id);

        if (!$test) {
            Toastr::error('Ошибка', 'Тест недоступен для вашей группы', ["progressBar" => true]);
            return redirect('/tests');
        }

        if (!$this->canStartAttempt((int) $test->id, (int) $user->id, $test->attempts_limit)) {
            Toastr::error('Ошибка', 'Лимит попыток исчерпан', ["progressBar" => true]);
            return redirect('/tests');
        }

        $attemptStartedAt = now();
        $request->session()->put("test_session.{$test->id}.{$user->id}.started_at", $attemptStartedAt->toDateTimeString());

        return view('tests.take', compact('user', 'settings', 'test'));
    }

    public function submit(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        $test = $this->getAvailableTestForStudent($user->id, (int) $user->group_id, $id);

        if (!$test) {
            Toastr::error('Ошибка', 'Тест недоступен', ["progressBar" => true]);
            return redirect('/tests');
        }

        if (!$this->canStartAttempt((int) $test->id, (int) $user->id, $test->attempts_limit)) {
            Toastr::error('Ошибка', 'Лимит попыток исчерпан', ["progressBar" => true]);
            return redirect('/tests');
        }

        $startedAtRaw = $request->session()->get("test_session.{$test->id}.{$user->id}.started_at");
        $startedAt = $startedAtRaw ? Carbon::parse($startedAtRaw) : now();
        $request->session()->forget("test_session.{$test->id}.{$user->id}.started_at");

        $answers = (array) $request->input('answers', []);
        $score = 0;
        $maxScore = 0;

        foreach ($test->questions as $question) {
            $points = max(1, (int) $question->points);
            $maxScore += $points;
            if ($this->isCorrectAnswer($question, $answers[$question->id] ?? null)) {
                $score += $points;
            }
        }

        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
        $grade = TestGrading::fromPercentage((float) $percentage);

        TestAttempt::create([
            'test_id' => $test->id,
            'student_id' => $user->id,
            'group_id' => $user->group_id,
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'grade' => $grade,
            'status' => 'submitted',
            'started_at' => $startedAt,
            'submitted_at' => now(),
            'answers_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
        ]);

        $isAutoSubmitted = $request->boolean('auto_submitted');
        $messagePrefix = $isAutoSubmitted ? 'Время вышло. Тест отправлен автоматически.' : 'Тест отправлен.';
        $gradeLabel = TestGrading::labelRu($grade);
        Toastr::success('Готово', "{$messagePrefix} Результат: {$score}/{$maxScore} ({$percentage}%). Оценка: {$grade} ({$gradeLabel}).", ["progressBar" => true]);
        return redirect('/tests');
    }

    public function review(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);

        $attemptId = (int) $request->query('attempt', 0);
        if ($attemptId > 0) {
            $attempt = TestAttempt::where('student_id', $user->id)->where('test_id', $id)->where('id', $attemptId)->first();
        } else {
            $attempt = TestAttempt::where('student_id', $user->id)->where('test_id', $id)->orderByDesc('submitted_at')->first();
        }

        if (!$attempt) {
            Toastr::warning('Нет данных', 'Сначала пройдите тест или выберите другую попытку.', ["progressBar" => true]);
            return redirect('/tests');
        }

        $test = Test::with(['questions' => fn ($q) => $q->orderBy('sort_order')])->findOrFail($id);
        $answers = (array) json_decode((string) $attempt->answers_json, true);

        $allAttempts = TestAttempt::where('student_id', $user->id)
            ->where('test_id', $id)
            ->orderByDesc('submitted_at')
            ->get();

        $breakdown = [];
        foreach ($test->questions as $idx => $question) {
            $given = $this->answerFromPayload($answers, (int) $question->id);
            $isCorrect = $this->isCorrectAnswer($question, $given);
            $breakdown[] = [
                'num' => $idx + 1,
                'question' => $question,
                'is_correct' => $isCorrect,
                'your_answer' => $this->formatStudentAnswerForDisplay($question, $given),
                'points' => max(1, (int) $question->points),
                'earned' => $isCorrect ? max(1, (int) $question->points) : 0,
            ];
        }

        return view('tests.review', compact('user', 'settings', 'test', 'attempt', 'breakdown', 'allAttempts'));
    }

    public function adminIndex(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);
        $groups = Groups::orderBy('name')->get();
        $tests = Test::with(['questions', 'assignments.group'])->orderByDesc('created_at')->get();

        return view('tests.admin', compact('user', 'settings', 'groups', 'tests'));
    }

    public function store(Request $request)
    {
        $user = $request->session()->get('user');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'questions' => ['required', 'array', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
            $test = Test::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'attempts_limit' => $validated['attempts_limit'] ?? null,
                'is_active' => (bool) $request->boolean('is_active', true),
                'created_by' => $user->id,
            ]);

            $sort = 1;
            foreach ((array) $request->input('questions', []) as $questionData) {
                $prepared = $this->prepareQuestionPayload((array) $questionData);
                $test->questions()->create([
                    'type' => $prepared['type'],
                    'question_text' => $prepared['question_text'],
                    'options_json' => json_encode($prepared['options'], JSON_UNESCAPED_UNICODE),
                    'correct_answer_json' => json_encode($prepared['correct'], JSON_UNESCAPED_UNICODE),
                    'points' => $prepared['points'],
                    'sort_order' => $sort++,
                ]);
            }

            foreach (array_unique(array_map('intval', (array) $request->input('group_ids', []))) as $groupId) {
                TestGroupAssignment::create([
                    'test_id' => $test->id,
                    'group_id' => $groupId,
                    'is_published' => true,
                ]);
            }
        });

        Toastr::success('Успешно', 'Тест создан и выдан выбранным группам', ["progressBar" => true]);
        return redirect('/tests/admin');
    }

    public function edit(Request $request, int $id)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);
        $test = Test::with([
            'questions' => fn ($q) => $q->orderBy('sort_order'),
            'assignments',
        ])->findOrFail($id);

        $groups = Groups::orderBy('name')->get();
        $builderQuestions = $test->questions->map(fn (TestQuestion $q) => $this->questionToEditableArray($q))->values()->all();

        return view('tests.edit', compact('user', 'settings', 'groups', 'test', 'builderQuestions'));
    }

    public function update(Request $request, int $id)
    {
        $test = Test::findOrFail($id);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'questions' => ['required', 'array', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $request, $test) {
            $test->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'attempts_limit' => $validated['attempts_limit'] ?? null,
                'is_active' => (bool) $request->boolean('is_active'),
            ]);

            $test->questions()->delete();

            $sort = 1;
            foreach ((array) $request->input('questions', []) as $questionData) {
                $prepared = $this->prepareQuestionPayload((array) $questionData);
                $test->questions()->create([
                    'type' => $prepared['type'],
                    'question_text' => $prepared['question_text'],
                    'options_json' => json_encode($prepared['options'], JSON_UNESCAPED_UNICODE),
                    'correct_answer_json' => json_encode($prepared['correct'], JSON_UNESCAPED_UNICODE),
                    'points' => $prepared['points'],
                    'sort_order' => $sort++,
                ]);
            }

            $test->assignments()->delete();
            foreach (array_unique(array_map('intval', (array) $request->input('group_ids', []))) as $groupId) {
                TestGroupAssignment::create([
                    'test_id' => $test->id,
                    'group_id' => $groupId,
                    'is_published' => true,
                ]);
            }
        });

        Toastr::success('Успешно', 'Тест обновлён', ["progressBar" => true]);
        return redirect('/tests/admin');
    }

    public function toggle(int $id)
    {
        $test = Test::findOrFail($id);
        $test->is_active = !$test->is_active;
        $test->save();

        Toastr::success('Успешно', $test->is_active ? 'Тест активирован' : 'Тест деактивирован', ["progressBar" => true]);
        return redirect('/tests/admin');
    }

    public function stats(Request $request)
    {
        $user = $request->session()->get('user');
        $settings = Settings::find(1);
        $groups = Groups::orderBy('name')->get();
        $data = $this->collectTestingStats($request);

        return view('tests.stats', [
            'user' => $user,
            'settings' => $settings,
            'groups' => $groups,
            'attempts' => $data['attempts'],
            'statsByGroup' => $data['statsByGroup'],
            'groupId' => $data['groupId'],
            'filterLabel' => $data['filterLabel'],
        ]);
    }

    public function statsExport(Request $request)
    {
        $data = $this->collectTestingStats($request);
        $attempts = $data['attempts'];
        $statsByGroup = $data['statsByGroup'];
        $filterLabel = $data['filterLabel'];

        $fileName = 'statistika_testov_'.now()->format('Y-m-d_H-i').'.csv';

        return response()->streamDownload(function () use ($attempts, $statsByGroup, $filterLabel) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Статистика тестирования', 'Фильтр: '.$filterLabel], ';');
            fputcsv($output, [], ';');
            fputcsv($output, ['Сводка по группам'], ';');
            fputcsv($output, ['Группа', 'Попыток', 'Средний %', 'Мин %', 'Макс %'], ';');
            foreach ($statsByGroup as $groupName => $stat) {
                fputcsv($output, [$groupName, $stat['count'], $stat['avg'], $stat['min'], $stat['max']], ';');
            }
            fputcsv($output, [], ';');
            fputcsv($output, ['Детализация попыток'], ';');
            fputcsv($output, ['Студент', 'Группа', 'Тест', 'Баллы', 'Макс. баллов', 'Процент', 'Оценка', 'Дата сдачи'], ';');
            foreach ($attempts as $attempt) {
                $at = $attempt->submitted_at ?? $attempt->created_at;
                $atStr = $at ? Carbon::parse($at)->format('d.m.Y H:i') : '—';
                fputcsv($output, [
                    optional($attempt->student)->fio ?: '—',
                    optional(optional($attempt->student)->group)->name ?: '—',
                    optional($attempt->test)->title ?: '—',
                    $attempt->score,
                    $attempt->max_score,
                    $attempt->percentage,
                    $attempt->display_grade,
                    $atStr,
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function statsPrint(Request $request)
    {
        $settings = Settings::find(1);
        $data = $this->collectTestingStats($request);
        $printedAt = now()->format('d.m.Y H:i');

        return view('tests.stats-print', [
            'settings' => $settings,
            'attempts' => $data['attempts'],
            'statsByGroup' => $data['statsByGroup'],
            'filterLabel' => $data['filterLabel'],
            'printedAt' => $printedAt,
        ]);
    }

    /**
     * @return array{groupId: int, attempts: \Illuminate\Support\Collection, statsByGroup: \Illuminate\Support\Collection, filterLabel: string}
     */
    private function collectTestingStats(Request $request): array
    {
        $groupId = (int) $request->query('group_id', 0);

        $attempts = TestAttempt::query()
            ->with(['test', 'student.group'])
            ->when($groupId > 0, fn ($q) => $q->where('group_id', $groupId))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $statsByGroup = $attempts
            ->groupBy(fn ($attempt) => optional($attempt->student->group)->name ?? 'Без группы')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'avg' => round($items->avg('percentage'), 2),
                'max' => round($items->max('percentage'), 2),
                'min' => round($items->min('percentage'), 2),
            ]);

        $filterLabel = 'Все группы';
        if ($groupId > 0) {
            $group = Groups::find($groupId);
            $filterLabel = $group ? $group->name : 'Группа #'.$groupId;
        }

        return [
            'groupId' => $groupId,
            'attempts' => $attempts,
            'statsByGroup' => $statsByGroup,
            'filterLabel' => $filterLabel,
        ];
    }

    private function getAvailableTestForStudent(int $studentId, int $groupId, int $testId): ?Test
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
            ->with('questions')
            ->first();
    }

    private function questionToEditableArray(TestQuestion $question): array
    {
        $opts = (array) json_decode((string) $question->options_json, true);
        $corr = (array) json_decode((string) $question->correct_answer_json, true);

        $base = [
            'type' => $question->type,
            'question_text' => $question->question_text,
            'points' => max(1, (int) $question->points),
        ];

        if ($question->type === 'single' || $question->type === 'multiple') {
            $base['options'] = array_values($opts['options'] ?? []);
            $base['correct'] = implode(',', array_map('strval', $corr['indexes'] ?? []));

            return $base;
        }

        if ($question->type === 'match') {
            $base['left'] = array_values($opts['left'] ?? []);
            $base['right'] = array_values($opts['right'] ?? []);
            $pairs = (array) ($corr['pairs'] ?? []);
            $lines = [];
            foreach ($pairs as $left => $right) {
                $lines[] = $left.'='.$right;
            }
            $base['pairs_lines'] = implode("\n", $lines);

            return $base;
        }

        $base['accepted'] = implode(', ', array_values($corr['accepted'] ?? []));

        return $base;
    }

    private function prepareQuestionPayload(array $questionData): array
    {
        $type = (string) ($questionData['type'] ?? '');
        $questionText = trim((string) ($questionData['question_text'] ?? ''));
        $points = max(1, (int) ($questionData['points'] ?? 1));

        if ($questionText === '' || !in_array($type, ['single', 'multiple', 'match', 'word'], true)) {
            abort(422, 'Некорректные данные вопроса');
        }

        if ($type === 'single' || $type === 'multiple') {
            $options = array_values(array_filter(array_map('trim', (array) ($questionData['options'] ?? [])), fn ($v) => $v !== ''));
            $correct = array_map('intval', (array) ($questionData['correct'] ?? []));
            if (count($options) < 2 || count($correct) < 1) {
                abort(422, 'Вопрос с вариантами ответа заполнен неверно');
            }
            return [
                'type' => $type,
                'question_text' => $questionText,
                'options' => ['options' => $options],
                'correct' => ['indexes' => $correct],
                'points' => $points,
            ];
        }

        if ($type === 'match') {
            $left = array_values(array_filter(array_map('trim', (array) ($questionData['left'] ?? [])), fn ($v) => $v !== ''));
            $right = array_values(array_filter(array_map('trim', (array) ($questionData['right'] ?? [])), fn ($v) => $v !== ''));
            $pairs = (array) ($questionData['pairs'] ?? []);
            if (count($left) < 1 || count($right) < 1 || count($pairs) < 1) {
                abort(422, 'Вопрос на сопоставление заполнен неверно');
            }
            return [
                'type' => $type,
                'question_text' => $questionText,
                'options' => ['left' => $left, 'right' => $right],
                'correct' => ['pairs' => $pairs],
                'points' => $points,
            ];
        }

        $accepted = array_values(array_filter(array_map(function ($value) {
            return mb_strtolower(trim((string) $value));
        }, (array) ($questionData['accepted'] ?? [])), fn ($v) => $v !== ''));

        if (count($accepted) < 1) {
            abort(422, 'Для текстового ответа укажите минимум один правильный вариант');
        }

        return [
            'type' => 'word',
            'question_text' => $questionText,
            'options' => null,
            'correct' => ['accepted' => $accepted],
            'points' => $points,
        ];
    }

    private function isCorrectAnswer($question, $answer): bool
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
            if ($answer === null || !is_array($answer) || count($answer) === 0) {
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

    private function answerFromPayload(array $answers, int $questionId): mixed
    {
        return $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
    }

    private function formatStudentAnswerForDisplay(TestQuestion $question, mixed $given): string
    {
        $opts = (array) json_decode((string) $question->options_json, true);

        if ($given === null || $given === '') {
            return '— (нет ответа)';
        }

        if ($question->type === 'single') {
            $idx = (int) $given;
            $list = $opts['options'] ?? [];

            return $list[$idx] ?? ('Вариант №'.($idx + 1));
        }

        if ($question->type === 'multiple') {
            $indices = array_map('intval', (array) $given);
            sort($indices);
            $list = $opts['options'] ?? [];
            $parts = [];
            foreach ($indices as $idx) {
                $parts[] = $list[$idx] ?? ('№'.($idx + 1));
            }

            return $parts === [] ? '—' : implode('; ', $parts);
        }

        if ($question->type === 'match') {
            if (!is_array($given)) {
                return '—';
            }
            $pairs = (array) $given;
            ksort($pairs);
            $lines = [];
            foreach ($pairs as $left => $right) {
                $lines[] = $left.' → '.$right;
            }

            return $lines === [] ? '—' : implode("\n", $lines);
        }

        if ($question->type === 'word') {
            return (string) $given;
        }

        return '—';
    }

    private function canStartAttempt(int $testId, int $studentId, $attemptsLimit): bool
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
}
