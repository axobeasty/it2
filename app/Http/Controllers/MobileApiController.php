<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\GroupScheduleEntry;
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

        $employee = Employee::with('role.pagePermissions')
            ->where('login', $validated['login'])
            ->first();

        if (! $employee || ! Hash::check($validated['password'], $employee->password)) {
            return response()->json(['message' => 'Неверный логин или пароль.'], 401);
        }

        if ((int) $employee->active !== 1) {
            return response()->json(['message' => 'Аккаунт деактивирован.'], 403);
        }

        if (! $employee->canAccessPage('schedule_my')) {
            return response()->json(['message' => 'Нет доступа к расписанию.'], 403);
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

        return Employee::with(['group', 'role.pagePermissions'])->find($employeeId);
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

    private function serializeEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'login' => $employee->login,
            'fio' => $employee->fio,
            'email' => $employee->email,
            'group_id' => $employee->group_id,
            'group_name' => $employee->group?->name,
        ];
    }
}
