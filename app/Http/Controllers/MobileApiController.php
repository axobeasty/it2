<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Faculty;
use App\Models\GroupScheduleEntry;
use App\Models\Groups;
use App\Models\InvNumbers;
use App\Models\Notifs;
use App\Models\O_Categories;
use App\Models\Orders;
use App\Models\RolePagePermission;
use App\Models\Roles;
use App\Models\Settings;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\WikiPage;
use App\Support\PageAccess;
use App\Support\MobileTestTaking;
use App\Support\RequestPerformanceCache;
use App\Support\TestSubmissionNotifier;
use App\Support\TestGrading;
use App\Support\TestingStatsCollector;
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
            $ok = Cache::remember(
                RequestPerformanceCache::REMOTE_DB_HEALTH_KEY,
                RequestPerformanceCache::REMOTE_DB_HEALTH_TTL_SECONDS,
                static function (): bool {
                    try {
                        DB::purge('mysql_remote');
                        DB::connection('mysql_remote')->select('SELECT 1');

                        return true;
                    } catch (\Throwable) {
                        return false;
                    }
                }
            );

            if ($ok) {
                return response()->json([
                    'ok' => true,
                    'maintenance' => false,
                    'message' => 'Сервис доступен.',
                ]);
            }

            return response()->json([
                'ok' => false,
                'maintenance' => true,
                'message' => 'Сервис временно недоступен. Проводятся технические работы.',
            ], 503);
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

        if (! $employee->canAccessPage('schedule_my')
            && ! $employee->canAccessPage('student_tests')
            && ! $employee->canAccessPage('tests_stats')
            && ! $employee->canAccessPage('tests_admin')) {
            return response()->json(['message' => 'Нет доступа к приложению (нужны «Расписание», «Тестирование» или права администрирования/статистики тестов).'], 403);
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
     * Список статей wiki, доступных текущему пользователю.
     * GET /api/mobile/wiki
     */
    public function wikiList(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if (! $employee->canAccessPage('knowledge_wiki') && ! $employee->canAccessPage('knowledge_wiki_edit')) {
            return response()->json(['message' => 'Нет доступа к базе знаний.'], 403);
        }

        $pages = WikiPage::query()
            ->with('roles')
            ->visibleToWikiReader($employee)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'parent_id', 'sort_order', 'updated_at']);

        return response()->json([
            'items' => $pages->map(function (WikiPage $page) {
                return [
                    'id' => (int) $page->id,
                    'title' => (string) $page->title,
                    'slug' => (string) $page->slug,
                    'parent_id' => $page->parent_id !== null ? (int) $page->parent_id : null,
                    'sort_order' => (int) $page->sort_order,
                    'updated_at' => $page->updated_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    /**
     * Просмотр одной статьи wiki.
     * GET /api/mobile/wiki/{slug}
     */
    public function wikiShow(Request $request, string $slug): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        $page = WikiPage::query()
            ->with(['roles', 'creator', 'editor'])
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            return response()->json(['message' => 'Статья не найдена.'], 404);
        }

        if (! $page->isReadableByWikiReader($employee)) {
            return response()->json(['message' => 'Нет доступа к статье.'], 403);
        }

        return response()->json([
            'page' => [
                'id' => (int) $page->id,
                'title' => (string) $page->title,
                'slug' => (string) $page->slug,
                'body' => (string) $page->body,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'updated_by' => $page->editor?->fio,
            ],
        ]);
    }

    /**
     * Категории заявок.
     * GET /api/mobile/orders/categories
     */
    public function ordersCategories(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        $items = O_Categories::query()
            ->orderBy('name')
            ->get(['id', 'name', 'cat_color']);

        return response()->json([
            'items' => $items->map(function (O_Categories $cat) {
                return [
                    'id' => (int) $cat->id,
                    'name' => (string) $cat->name,
                    'color' => (string) ($cat->cat_color ?? '#0d6efd'),
                ];
            })->values(),
        ]);
    }

    /**
     * Мои заявки (или все для админа).
     * GET /api/mobile/orders/my
     */
    public function ordersMy(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        $isAdmin = $employee->canAccessPage('orders_admin');
        $query = Orders::query()->with(['category:id,name,cat_color', 'employee:id,fio']);
        if (! $isAdmin) {
            if (! $employee->canAccessPage('orders_my')) {
                return response()->json(['message' => 'Нет доступа к заявкам.'], 403);
            }
            $query->where('employee_id', $employee->id);
        }

        $orders = $query->orderByDesc('id')->limit(200)->get();
        return response()->json([
            'items' => $orders->map(function (Orders $order) {
                return [
                    'id' => (int) $order->id,
                    'description' => (string) $order->description,
                    'status' => (int) $order->status,
                    'category_id' => (int) $order->category_id,
                    'category_name' => (string) optional($order->category)->name,
                    'employee_id' => (int) $order->employee_id,
                    'employee_fio' => (string) optional($order->employee)->fio,
                    'room' => (string) ($order->room ?? ''),
                    'created_at' => $order->created_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    /**
     * Создание заявки.
     * POST /api/mobile/orders/create
     */
    public function ordersCreate(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('orders_my')) {
            return response()->json(['message' => 'Нет доступа к созданию заявки.'], 403);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:o__categories,id'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);

        $order = Orders::query()->create([
            'employee_id' => $employee->id,
            'description' => $validated['description'],
            'category_id' => (int) $validated['category_id'],
            'room' => (string) ($validated['room'] ?? ''),
            'status' => 0,
        ]);

        Notifs::create([
            'title' => 'Заявка зарегистрирована',
            'message' => 'Ваша заявка успешно зарегистрирована в системе под идентификатором '.$order->id.'.',
            'employee_id' => $employee->id,
        ]);

        return response()->json([
            'message' => 'Заявка создана.',
            'id' => (int) $order->id,
        ], 201);
    }

    /**
     * Изменение статуса заявки (админ).
     * PATCH /api/mobile/orders/{id}/status/{code}
     */
    public function ordersSetStatus(Request $request, int $id, int $code): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('orders_admin')) {
            return response()->json(['message' => 'Нет доступа к изменению статуса заявки.'], 403);
        }
        if (! in_array($code, [0, 1, 2, 3], true)) {
            return response()->json(['message' => 'Некорректный код статуса.'], 422);
        }

        $order = Orders::query()->find($id);
        if (! $order) {
            return response()->json(['message' => 'Заявка не найдена.'], 404);
        }

        $order->status = $code;
        $order->save();

        return response()->json([
            'message' => 'Статус заявки обновлён.',
            'id' => (int) $order->id,
            'status' => (int) $order->status,
        ]);
    }

    /**
     * Мой текущий инвентарь.
     * GET /api/mobile/inventory/my
     */
    public function inventoryMy(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('inventory_my') && ! $employee->canAccessPage('inventory_admin')) {
            return response()->json(['message' => 'Нет доступа к инвентарю.'], 403);
        }

        $items = InvNumbers::query()
            ->with(['store.type'])
            ->where('employees_id', $employee->id)
            ->whereNull('date_out')
            ->orderByDesc('date_in')
            ->get();

        return response()->json([
            'items' => $items->map(function (InvNumbers $item) {
                return [
                    'id' => (int) $item->id,
                    'name' => (string) optional($item->store)->name,
                    'inventory_number' => (string) ($item->number ?? ''),
                    'type' => (string) optional(optional($item->store)->type)->name,
                    'room' => (string) ($item->room ?? ''),
                    'date_in' => (string) ($item->date_in ?? ''),
                ];
            })->values(),
        ]);
    }

    /**
     * Активные закрепления инвентаря по сотрудникам (админ).
     * GET /api/mobile/inventory/manage
     */
    public function inventoryManage(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('inventory_admin')) {
            return response()->json(['message' => 'Нет доступа к управлению инвентарём.'], 403);
        }

        $items = InvNumbers::query()
            ->with(['store.type', 'employee'])
            ->whereNull('date_out')
            ->orderBy('employees_id')
            ->orderByDesc('date_in')
            ->limit(1000)
            ->get();

        return response()->json([
            'items' => $items->map(function (InvNumbers $item) {
                return [
                    'id' => (int) $item->id,
                    'employee_id' => (int) $item->employees_id,
                    'employee_fio' => (string) optional($item->employee)->fio,
                    'name' => (string) optional($item->store)->name,
                    'inventory_number' => (string) ($item->number ?? ''),
                    'type' => (string) optional(optional($item->store)->type)->name,
                    'room' => (string) ($item->room ?? ''),
                    'date_in' => (string) ($item->date_in ?? ''),
                ];
            })->values(),
        ]);
    }

    /**
     * Список сотрудников.
     * GET /api/mobile/employees
     */
    public function employeesList(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('employees_manage')) {
            return response()->json(['message' => 'Нет доступа к сотрудникам.'], 403);
        }

        $items = Employee::query()
            ->with(['role:id,name', 'group:id,name'])
            ->orderBy('fio')
            ->limit(500)
            ->get(['id', 'login', 'fio', 'email', 'active', 'role_id', 'group_id']);

        return response()->json([
            'items' => $items->map(function (Employee $row) {
                return [
                    'id' => (int) $row->id,
                    'login' => (string) $row->login,
                    'fio' => (string) $row->fio,
                    'email' => (string) $row->email,
                    'active' => (int) $row->active === 1,
                    'role_id' => $row->role_id ? (int) $row->role_id : 0,
                    'role_name' => (string) optional($row->role)->name,
                    'group_id' => $row->group_id ? (int) $row->group_id : 0,
                    'group_name' => (string) optional($row->group)->name,
                ];
            })->values(),
        ]);
    }

    /**
     * Активация/деактивация сотрудника.
     * PATCH /api/mobile/employees/{id}/active/{state}
     */
    public function employeesSetActive(Request $request, int $id, int $state): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('employees_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению сотрудниками.'], 403);
        }

        $target = Employee::query()->find($id);
        if (! $target) {
            return response()->json(['message' => 'Сотрудник не найден.'], 404);
        }

        $target->active = $state === 1 ? 1 : 0;
        $target->save();

        return response()->json([
            'message' => 'Статус сотрудника обновлён.',
            'id' => (int) $target->id,
            'active' => (int) $target->active === 1,
        ]);
    }

    /**
     * Назначение роли и/или группы сотруднику.
     * PATCH /api/mobile/employees/{id}/assign
     */
    public function employeesAssign(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('employees_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению сотрудниками.'], 403);
        }

        $target = Employee::query()->find($id);
        if (! $target) {
            return response()->json(['message' => 'Сотрудник не найден.'], 404);
        }

        $validated = $request->validate([
            'role_id' => ['nullable', 'integer', 'min:1', 'exists:roles,id'],
            'group_id' => ['nullable', 'integer', 'min:1', 'exists:groups,id'],
        ]);

        if (array_key_exists('role_id', $validated)) {
            $target->role_id = $validated['role_id'] ? (int) $validated['role_id'] : null;
        }
        if (array_key_exists('group_id', $validated)) {
            $target->group_id = $validated['group_id'] ? (int) $validated['group_id'] : null;
        }

        $target->save();
        $target->load(['role:id,name', 'group:id,name']);

        return response()->json([
            'message' => 'Назначения сотрудника обновлены.',
            'id' => (int) $target->id,
            'role_id' => $target->role_id ? (int) $target->role_id : 0,
            'role_name' => (string) optional($target->role)->name,
            'group_id' => $target->group_id ? (int) $target->group_id : 0,
            'group_name' => (string) optional($target->group)->name,
        ]);
    }

    /**
     * Список ролей.
     * GET /api/mobile/roles
     */
    public function rolesList(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage') && ! $employee->canAccessPage('employees_manage')) {
            return response()->json(['message' => 'Нет доступа к ролям.'], 403);
        }

        $roles = Roles::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_system']);

        return response()->json([
            'items' => $roles->map(function (Roles $role) {
                return [
                    'id' => (int) $role->id,
                    'name' => (string) $role->name,
                    'is_system' => (bool) $role->is_system,
                ];
            })->values(),
        ]);
    }

    /**
     * Создание роли.
     * POST /api/mobile/roles
     */
    public function rolesCreate(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению ролями.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $role = Roles::query()->create([
            'name' => (string) $validated['name'],
            'is_system' => false,
        ]);

        return response()->json([
            'message' => 'Роль создана.',
            'id' => (int) $role->id,
        ], 201);
    }

    /**
     * Обновление роли.
     * PATCH /api/mobile/roles/{id}
     */
    public function rolesUpdate(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению ролями.'], 403);
        }

        $role = Roles::query()->find($id);
        if (! $role) {
            return response()->json(['message' => 'Роль не найдена.'], 404);
        }
        if ((bool) $role->is_system) {
            return response()->json(['message' => 'Системную роль нельзя изменить.'], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $role->name = (string) $validated['name'];
        $role->save();

        return response()->json([
            'message' => 'Роль обновлена.',
            'id' => (int) $role->id,
        ]);
    }

    /**
     * Удаление роли.
     * DELETE /api/mobile/roles/{id}
     */
    public function rolesDelete(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению ролями.'], 403);
        }

        $role = Roles::query()->find($id);
        if (! $role) {
            return response()->json(['message' => 'Роль не найдена.'], 404);
        }
        if ((bool) $role->is_system) {
            return response()->json(['message' => 'Системную роль нельзя удалить.'], 422);
        }
        if ($role->employees()->exists()) {
            return response()->json(['message' => 'Нельзя удалить роль, к которой привязаны сотрудники.'], 422);
        }

        $role->delete();
        return response()->json([
            'message' => 'Роль удалена.',
            'id' => $id,
        ]);
    }

    /**
     * Права роли для редактора.
     * GET /api/mobile/roles/{id}/permissions
     */
    public function rolesPermissions(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению ролями.'], 403);
        }

        $role = Roles::query()->with('pagePermissions:id,role_id,page_key')->find($id);
        if (! $role) {
            return response()->json(['message' => 'Роль не найдена.'], 404);
        }

        $labels = PageAccess::allLabels();
        $selected = $role->pagePermissions->pluck('page_key')->map(fn ($v) => (string) $v)->all();

        $options = [];
        foreach ($labels as $key => $label) {
            $options[] = [
                'key' => (string) $key,
                'label' => (string) $label,
            ];
        }

        return response()->json([
            'role' => [
                'id' => (int) $role->id,
                'name' => (string) $role->name,
                'is_system' => (bool) $role->is_system,
            ],
            'options' => $options,
            'selected' => array_values($selected),
        ]);
    }

    /**
     * Сохранение прав роли.
     * POST /api/mobile/roles/{id}/permissions
     */
    public function rolesPermissionsSave(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('roles_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению ролями.'], 403);
        }

        $role = Roles::query()->find($id);
        if (! $role) {
            return response()->json(['message' => 'Роль не найдена.'], 404);
        }
        if ((bool) $role->is_system) {
            return response()->json(['message' => 'Системной роли нельзя менять права.'], 422);
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $requested = array_values(array_unique(array_map('strval', $validated['permissions'] ?? [])));
        $allowed = array_keys(PageAccess::allLabels());
        $allowedMap = array_fill_keys($allowed, true);
        $final = array_values(array_filter($requested, static fn ($k) => isset($allowedMap[$k])));

        RolePagePermission::query()->where('role_id', $role->id)->delete();
        if ($final !== []) {
            $rows = [];
            foreach ($final as $key) {
                $rows[] = ['role_id' => $role->id, 'page_key' => $key];
            }
            RolePagePermission::query()->insert($rows);
        }

        return response()->json([
            'message' => 'Права роли обновлены.',
            'id' => (int) $role->id,
            'count' => count($final),
        ]);
    }

    /**
     * Список групп.
     * GET /api/mobile/groups
     */
    public function groupsList(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('groups_manage') && ! $employee->canAccessPage('employees_manage')) {
            return response()->json(['message' => 'Нет доступа к группам.'], 403);
        }

        $groups = Groups::query()->withCount('students')->orderBy('name')->get(['id', 'name', 'description']);

        return response()->json([
            'items' => $groups->map(function (Groups $group) {
                return [
                    'id' => (int) $group->id,
                    'name' => (string) $group->name,
                    'description' => (string) ($group->description ?? ''),
                    'students_count' => (int) ($group->students_count ?? 0),
                ];
            })->values(),
        ]);
    }

    /**
     * Создание группы.
     * POST /api/mobile/groups
     */
    public function groupsCreate(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('groups_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению группами.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $group = Groups::query()->create([
            'name' => (string) $validated['name'],
            'description' => (string) ($validated['description'] ?? ''),
        ]);

        return response()->json([
            'message' => 'Группа создана.',
            'id' => (int) $group->id,
        ], 201);
    }

    /**
     * Обновление группы.
     * PATCH /api/mobile/groups/{id}
     */
    public function groupsUpdate(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('groups_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению группами.'], 403);
        }

        $group = Groups::query()->find($id);
        if (! $group) {
            return response()->json(['message' => 'Группа не найдена.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $group->name = (string) $validated['name'];
        $group->description = (string) ($validated['description'] ?? '');
        $group->save();

        return response()->json([
            'message' => 'Группа обновлена.',
            'id' => (int) $group->id,
        ]);
    }

    /**
     * Удаление группы.
     * DELETE /api/mobile/groups/{id}
     */
    public function groupsDelete(Request $request, int $id): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('groups_manage')) {
            return response()->json(['message' => 'Нет доступа к управлению группами.'], 403);
        }

        $group = Groups::query()->find($id);
        if (! $group) {
            return response()->json(['message' => 'Группа не найдена.'], 404);
        }

        Employee::query()->where('group_id', $group->id)->update(['group_id' => null]);
        $group->delete();

        return response()->json([
            'message' => 'Группа удалена.',
            'id' => $id,
        ]);
    }

    /**
     * Общие настройки.
     * GET /api/mobile/settings/general
     */
    public function settingsGeneral(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('settings')) {
            return response()->json(['message' => 'Нет доступа к настройкам.'], 403);
        }

        $settings = Settings::query()->find(1);
        if (! $settings) {
            return response()->json(['message' => 'Настройки не найдены.'], 404);
        }

        return response()->json([
            'title' => (string) ($settings->title ?? ''),
            'disable_reason' => (string) ($settings->disable_reason ?? ''),
            'is_enabled' => (int) ($settings->is_enabled ?? 1) === 1,
        ]);
    }

    /**
     * Сохранение общих настроек.
     * POST /api/mobile/settings/general
     */
    public function settingsGeneralSave(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }
        if (! $employee->canAccessPage('settings')) {
            return response()->json(['message' => 'Нет доступа к настройкам.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'disable_reason' => ['nullable', 'string', 'max:1000'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        $settings = Settings::query()->find(1);
        if (! $settings) {
            return response()->json(['message' => 'Настройки не найдены.'], 404);
        }

        $settings->title = (string) $validated['title'];
        $settings->disable_reason = (string) ($validated['disable_reason'] ?? '');
        $settings->is_enabled = $validated['is_enabled'] ? 1 : 0;
        $settings->save();

        return response()->json([
            'message' => 'Настройки сохранены.',
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

    /**
     * Статистика прохождения тестов (как веб /tests/stats).
     * GET /api/mobile/test-stats?group_id=&page=
     */
    public function testStats(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployeeFromToken($request);
        if (! $employee) {
            return response()->json(['message' => 'Необходима авторизация.'], 401);
        }

        if (! $employee->canAccessPage('tests_stats') && ! $employee->canAccessPage('tests_admin')) {
            return response()->json(['message' => 'Нет доступа к статистике тестов.'], 403);
        }

        $data = TestingStatsCollector::collect($request);
        $page = $data['attempts'];

        $groups = Groups::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Groups $g) => [
                'id' => (int) $g->id,
                'name' => (string) $g->name,
            ])
            ->values();

        $statsByGroup = [];
        foreach ($data['statsByGroup'] as $name => $stat) {
            $statsByGroup[$name] = $stat;
        }

        $attemptRows = collect($page->items())->map(function (TestAttempt $a) {
            $grade = $a->display_grade;

            return [
                'id' => (int) $a->id,
                'student_fio' => $a->student?->fio,
                'group_name' => $a->student?->group?->name,
                'test_title' => $a->test?->title,
                'score' => (int) $a->score,
                'max_score' => (int) $a->max_score,
                'percentage' => (float) $a->percentage,
                'grade' => $grade,
                'grade_label' => TestGrading::labelRu($grade),
                'submitted_at' => $a->submitted_at?->toIso8601String(),
            ];
        })->values();

        $jsonOpts = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

        return response()->json([
            'filter' => [
                'group_id' => (int) $data['groupId'],
                'label' => $data['filterLabel'],
            ],
            'groups' => $groups,
            'stats_by_group' => $statsByGroup,
            'attempts' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'data' => $attemptRows,
            ],
        ], 200, [], $jsonOpts);
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

        $permissions = [];
        foreach (array_keys(PageAccess::allLabels()) as $key) {
            $permissions[$key] = $employee->canAccessPage((string) $key);
        }

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
            'permissions' => $permissions,
        ];
    }

}
