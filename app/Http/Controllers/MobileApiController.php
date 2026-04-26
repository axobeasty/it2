<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Faculty;
use App\Models\GroupScheduleEntry;
use App\Models\Groups;
use App\Models\Notifs;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Support\MobileTestTaking;
use App\Support\TestSubmissionNotifier;
use App\Support\TestGrading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileApiController extends Controller
{
    private const TOKEN_TTL_SECONDS = 2592000; // 30 days

    private const TEST_SESSION_CACHE_SECONDS = 43200; // 12 hours

    public function health(): JsonResponse
    {
        try {
            DB::purge('mysql_remote');
            DB::connection('mysql_remote')->select('SELECT 1');

            return response()->json([
                'ok' => true,
                'maintenance' => false,
                'message' => 'Сервис доступен.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'maintenance' => true,
                'message' => 'Сервис временно недоступен. Проводятся технические работы.',
            ], 503);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::with(['role.pagePermissions', 'group', 'department', 'chair'])
            ->where('login', $validated['login'])
            ->first();

        if (! $employee || ! Hash::check($validated['password'], $employee->password)) {
            return response()->json(['message' => 'Неверный логин или пароль.'], 401);
        }

        if ((int) $employee->active !== 1) {
            return response()->json(['message' => 'Аккаунт деактивирован.'], 403);
        }

        if (! $employee->canAccessPage('schedule_my') && ! $employee->canAccessPage('student_tests')) {
            return response()->json(['message' => 'Нет доступа к приложению (нужны «Расписание» или «Тестирование»).'], 403);
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        Cache::put($this->tokenCacheKey($tokenHash), $employee->id, self::TOKEN_TTL_SECONDS);

        return response()->json([
            'token' => $plainToken,
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'user' => $this->serializeEmployee($employee),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        return response()->json([
            'user' => $this->serializeEmployee($employee),
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if (! $employee->canAccessPage('schedule_my')) {
            return response()->json(['message' => 'Нет доступа к расписанию.'], 403);
        }

        $weekMonday = $request->filled('week')
            ? Carbon::parse((string) $request->input('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $entries = collect();
        if ($employee->group_id) {
            $entries = GroupScheduleEntry::query()
                ->where('group_id', $employee->group_id)
                ->whereDate('week_start_date', $weekMonday->toDateString())
                ->with(['teacher', 'scheduleSubject'])
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get();
        }

        return response()->json([
            'week_start' => $weekMonday->toDateString(),
            'group' => $employee->group?->name,
            'entries' => $entries->map(function (GroupScheduleEntry $entry) {
                return [
                    'id' => $entry->id,
                    'weekday' => $entry->weekday,
                    'weekday_label' => $entry->weekdayLabel(),
                    'lesson_slot' => $entry->lesson_slot,
                    'start_time' => (string) $entry->start_time,
                    'end_time' => (string) $entry->end_time,
                    'subject' => $entry->scheduleSubject?->name ?? $entry->subject_title,
                    'teacher' => $entry->teacher?->fio,
                    'room' => $entry->room,
                    'building' => $entry->building,
                    'building_label' => $entry->buildingLabel(),
                ];
            })->values(),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if ($request->boolean('bootstrap')) {
            $maxId = (int) Notifs::query()
                ->where('employee_id', $employee->id)
                ->max('id');

            return response()->json([
                'max_id' => $maxId,
                'items' => [],
            ]);
        }

        $sinceId = max(0, (int) $request->query('since_id', 0));

        $items = Notifs::query()
            ->where('employee_id', $employee->id)
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'title', 'message', 'is_read', 'created_at']);

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Список тестов, доступных группе студента (как веб /tests).
     * GET /api/mobile/tests или /api/mobile/student-tests
     */
    public function testsList(Request $request): JsonResponse
    {
        try {
            $employee = $this->resolveEmployeeFromToken($request);
            if (! $employee) {
                return response()->json(['message' => 'Необходима авторизация.'], 401);
            }

            if (! $employee->canAccessPage('student_tests')) {
                return response()->json(['message' => 'Нет доступа к тестированию.'], 403);
            }

            $groupId = (int) ($employee->group_id ?? 0);
            $tests = collect();

            if ($groupId > 0) {
                $testIds = Test::query()
                    ->select('tests.id')
                    ->join('test_group_assignments as tga', 'tga.test_id', '=', 'tests.id')
                    ->where('tga.group_id', $groupId)
                    ->where('tga.is_published', true)
                    ->where('tests.is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('tga.starts_at')->orWhere('tga.starts_at', '<=', Carbon::now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('tga.ends_at')->orWhere('tga.ends_at', '>=', Carbon::now());
                    })
                    ->distinct()
                    ->pluck('tests.id');

                if ($testIds->isNotEmpty()) {
                    $tests = Test::query()
                        ->whereIn('id', $testIds)
                        ->withCount(['questions'])
                        ->orderByDesc('created_at')
                        ->get();
                }
            }

            $attemptsCountByTest = TestAttempt::where('student_id', $employee->id)
                ->select('test_id', DB::raw('COUNT(*) as attempts_count'))
                ->groupBy('test_id')
                ->pluck('attempts_count', 'test_id')
                ->mapWithKeys(fn ($count, $testId) => [(int) $testId => (int) $count]);

            $lastAttempts = TestAttempt::where('student_id', $employee->id)
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get()
                ->unique(fn (TestAttempt $a) => (int) $a->test_id)
                ->keyBy(fn (TestAttempt $a) => (int) $a->test_id);

            $studentId = (int) $employee->id;

            $payload = $tests->map(function (Test $test) use ($attemptsCountByTest, $lastAttempts, $studentId) {
                $tid = (int) $test->id;
                $attemptsUsed = (int) ($attemptsCountByTest[$tid] ?? 0);
                $limit = (int) ($test->attempts_limit ?? 0);
                $canStart = MobileTestTaking::canStartAttempt($tid, $studentId, $test->attempts_limit);

                $last = $lastAttempts->get($tid);
                $gradeDisplay = $last ? (string) $last->display_grade : '';

                return [
                    'id' => $tid,
                    'title' => $test->title,
                    'description' => $test->description,
                    'time_limit_minutes' => $test->time_limit_minutes,
                    'attempts_limit' => $limit,
                    'attempts_used' => $attemptsUsed,
                    'can_start' => $canStart,
                    'questions_count' => (int) ($test->questions_count ?? 0),
                    'last_attempt' => $last ? [
                        'id' => (int) $last->id,
                        'score' => (int) $last->score,
                        'max_score' => (int) $last->max_score,
                        'percentage' => (float) $last->percentage,
                        'grade' => $gradeDisplay,
                        'grade_label' => TestGrading::labelRu($gradeDisplay),
                        'submitted_at' => $last->submitted_at?->toIso8601String(),
                    ] : null,
                ];
            })->values();

            $jsonOpts = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

            return response()->json(['tests' => $payload], 200, [], $jsonOpts);
        } catch (\Throwable $e) {
            report($e);

            $detail = config('app.debug') ? $e->getMessage() : null;
            $message = $detail
                ?? 'Не удалось загрузить список тестов. Проверьте миграции (таблица test_group_assignments и др.) и лог: storage/logs/laravel.log';

            return response()->json(['message' => $message], 500);
        }
    }

    /**
     * Начало попытки: фиксирует время старта в кэше, возвращает вопросы без правильных ответов.
     * POST /api/mobile/tests/{id}/session или /api/mobile/student-tests/{id}/session
     */
    public function testBegin(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if (! $employee->canAccessPage('student_tests')) {
            return response()->json(['message' => 'Нет доступа к тестированию.'], 403);
        }

        $groupId = (int) ($employee->group_id ?? 0);
        $test = MobileTestTaking::findAvailableTest($groupId, $id);

        if (! $test) {
            return response()->json(['message' => 'Тест недоступен.'], 404);
        }

        if (! MobileTestTaking::canStartAttempt($id, (int) $employee->id, $test->attempts_limit)) {
            return response()->json(['message' => 'Лимит попыток исчерпан.'], 403);
        }

        $startedAt = now();
        Cache::put(
            $this->mobileTestSessionKey((int) $employee->id, $id),
            ['started_at' => $startedAt->toIso8601String()],
            self::TEST_SESSION_CACHE_SECONDS
        );

        $questions = $test->questions->map(fn ($q) => MobileTestTaking::serializeQuestionForClient($q))->values();

        return response()->json([
            'test' => [
                'id' => (int) $test->id,
                'title' => $test->title,
                'description' => $test->description,
                'time_limit_minutes' => $test->time_limit_minutes,
                'attempts_limit' => (int) ($test->attempts_limit ?? 0),
                'questions' => $questions,
            ],
            'started_at' => $startedAt->toIso8601String(),
        ]);
    }

    /**
     * Отправка ответов: тело JSON { "answers": { "<id_вопроса>": … }, "auto_submitted": false }.
     * Типы значений как в веб-форме: single — int; multiple — массив int; match — объект «левый столбец» => «правый»; word — строка.
     * POST /api/mobile/tests/{id}/submit или /api/mobile/student-tests/{id}/submit
     */
    public function testSubmit(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if (! $employee->canAccessPage('student_tests')) {
            return response()->json(['message' => 'Нет доступа к тестированию.'], 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'auto_submitted' => ['sometimes', 'boolean'],
        ]);

        $groupId = (int) ($employee->group_id ?? 0);
        $test = MobileTestTaking::findAvailableTest($groupId, $id);

        if (! $test) {
            return response()->json(['message' => 'Тест недоступен.'], 404);
        }

        if (! MobileTestTaking::canStartAttempt($id, (int) $employee->id, $test->attempts_limit)) {
            return response()->json(['message' => 'Лимит попыток исчерпан.'], 403);
        }

        $cacheKey = $this->mobileTestSessionKey((int) $employee->id, $id);
        $sessionData = Cache::pull($cacheKey);
        $startedAt = isset($sessionData['started_at'])
            ? Carbon::parse((string) $sessionData['started_at'])
            : now();

        $answers = $this->normalizeMobileAnswers((array) $validated['answers']);
        $graded = MobileTestTaking::grade($test, $answers);

        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'student_id' => $employee->id,
            'group_id' => $employee->group_id,
            'score' => $graded['score'],
            'max_score' => $graded['max_score'],
            'percentage' => $graded['percentage'],
            'grade' => $graded['grade'],
            'status' => 'submitted',
            'started_at' => $startedAt,
            'submitted_at' => now(),
            'answers_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
        ]);

        TestSubmissionNotifier::notifyStaffAboutGroupTestSubmission($employee, $test, $attempt);

        $gradeLabel = TestGrading::labelRu($graded['grade']);

        return response()->json([
            'message' => $request->boolean('auto_submitted')
                ? 'Время вышло. Тест отправлен автоматически.'
                : 'Тест отправлен.',
            'attempt' => [
                'id' => (int) $attempt->id,
                'score' => $graded['score'],
                'max_score' => $graded['max_score'],
                'percentage' => $graded['percentage'],
                'grade' => $graded['grade'],
                'grade_label' => $gradeLabel,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);
        if ($token !== null) {
            Cache::forget($this->tokenCacheKey(hash('sha256', $token)));
        }

        return response()->json(['message' => 'Сессия завершена.']);
    }

    private function resolveEmployeeFromToken(Request $request): ?Employee
    {
        $token = $this->extractBearerToken($request);
        if (! $token) {
            return null;
        }

        $employeeId = Cache::get($this->tokenCacheKey(hash('sha256', $token)));
        if (! $employeeId) {
            return null;
        }

        return Employee::with(['group', 'role.pagePermissions', 'department', 'chair'])->find($employeeId);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $token = $request->bearerToken();
        if (! $token || strlen($token) < 20) {
            return null;
        }

        return $token;
    }

    private function tokenCacheKey(string $hash): string
    {
        return 'mobile_token_'.$hash;
    }

    private function mobileTestSessionKey(int $employeeId, int $testId): string
    {
        return "mobile_test_session_{$employeeId}_{$testId}";
    }

    /**
     * Приводит ключи вопросов из JSON к int там, где это числа (Gson/Retrofit часто шлёт строковые ключи).
     *
     * @return array<int|string, mixed>
     */
    private function normalizeMobileAnswers(array $raw): array
    {
        $out = [];
        foreach ($raw as $questionId => $value) {
            $key = is_numeric($questionId) ? (int) $questionId : $questionId;
            $out[$key] = $value;
        }

        return $out;
    }

    private function serializeEmployee(Employee $employee): array
    {
        $employee->loadMissing(['group', 'role.pagePermissions', 'department', 'chair']);

        $facultyCatalogName = null;
        if ($employee->faculty_id) {
            $facultyCatalogName = Faculty::query()->whereKey($employee->faculty_id)->value('name');
        }

        $attrs = $employee->getAttributes();

        return [
            'id' => $employee->id,
            'login' => $employee->login,
            'fio' => $employee->fio,
            'email' => $employee->email,
            'active' => (bool) $employee->active,
            'room' => $employee->room,
            'phone' => $employee->phone ?? null,
            'birth_date' => $employee->birth_date ? (string) $employee->birth_date : null,
            'citizenship' => $employee->citizenship ?? null,
            'course' => $employee->course ?? null,
            'record_book_number' => $employee->record_book_number ?? null,
            'enrollment_year' => $employee->enrollment_year ?? null,
            'faculty_note' => $attrs['faculty'] ?? null,
            'department_note' => $employee->department_name ?? null,
            'group_id' => $employee->group_id,
            'group_name' => $employee->group?->name,
            'role_id' => $employee->role_id,
            'role_name' => $employee->role?->name,
            'department_id' => $employee->department_id,
            'department_title' => $employee->department?->title,
            'faculty_id' => $employee->faculty_id,
            'faculty_name' => $facultyCatalogName,
            'chair_id' => $employee->chair_id,
            'chair_name' => $employee->chair?->name,
            'email_notifications' => (bool) $employee->email_notifications,
            'permissions' => [
                'schedule_my' => $employee->canAccessPage('schedule_my'),
                'student_tests' => $employee->canAccessPage('student_tests'),
            ],
        ];
    }

}
