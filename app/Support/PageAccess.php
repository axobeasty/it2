<?php

namespace App\Support;

class PageAccess
{
    public const MAP = [
        'dashboard' => ['/', '/dashboard', '/profile', '/profile/password', '/profile/notifications'],
        'orders_my' => ['/orders', '/orders/my', '/orders/create', '/orders/{id}/status/set/{code}'],
        'orders_admin' => ['/orders/administration', '/orders/categories', '/orders/categories/create', '/orders/categories/delete/{id}'],
        'inventory_my' => ['/inv'],
        'inventory_admin' => ['/inv/manage', '/inv/types', '/inv/departments/manage', '/inv/departments/delete/{id}', '/inv/departments/create', '/inv/departments/{id}/edit', '/inv/assign', '/inv/unassign/{id}', '/inv/unassign-all/{employeeId}', '/inv/reassign/{id}', '/inv/export', '/inv/print'],
        'employees_manage' => ['/employees', '/employees/new', '/employees/edit/{id}', '/employees/delete/{id}', '/employees/deactivate/{id}', '/employees/activate/{id}'],
        'roles_manage' => ['/roles', '/roles/create', '/roles/{id}/edit', '/roles/{id}/delete'],
        'groups_manage' => ['/groups', '/groups/create', '/groups/{id}/edit', '/groups/{id}/delete', '/groups/{id}/assign-students', '/groups/{id}/print-students', '/groups/students/{id}/detach'],
        'faculties_manage' => ['/teachers/faculties', '/teachers/faculties/create', '/teachers/faculties/{id}/edit', '/teachers/faculties/{id}/delete'],
        'chairs_manage' => ['/teachers/chairs', '/teachers/chairs/create', '/teachers/chairs/{id}/edit', '/teachers/chairs/{id}/delete'],
        'portfolio' => ['/profile/portfolio', '/portfolio/add'],
        'portfolio_types' => ['/portfolio/types', '/portfolio/types/add', '/portfolio/types/{id}/delete', '/portfolio/roles'],
        'portfolio_confirm' => ['/portfolio/confirm', '/portfolio/confirm/{portfolio}/approve', '/portfolio/confirm/{portfolio}/reject'],
        'settings' => [
            '/settings',
            '/settings/general',
            '/settings/general/site/disable',
            '/settings/general/site/enable',
            '/settings/authenticate',
            '/settings/email',
            '/settings/email/test',
            '/settings/save',
            '/settings/git/check-updates',
            '/settings/git/pull-updates',
            '/settings/git/deploy-ref',
        ],
        'settings_database' => [
            '/settings/database',
            '/settings/database/save',
            '/settings/database/save-remote-draft',
            '/settings/database/activate-profile',
            '/settings/database/test-connection',
            '/settings/database/dry-run-init',
            '/settings/database/initialize',
            '/settings/database/initialize-stream',
            '/settings/database/migrate',
            '/settings/database/migrate-stream',
        ],
        'notifications' => ['/notifications/mark-all-read'],
        'tasks' => ['/task/add', '/task/done', '/task/delete/{id}'],
        'password_manager' => ['/passwords', '/passwords/create', '/passwords/{id}/reveal', '/passwords/{id}/delete'],
        'student_tests' => ['/tests', '/tests/{id}', '/tests/{id}/submit', '/tests/{id}/review'],
        'tests_admin' => ['/tests/admin', '/tests/admin/create', '/tests/admin/{id}/toggle', '/tests/admin/{id}/edit', '/tests/admin/{id}/update'],
        'tests_stats' => ['/tests/stats', '/tests/stats/export', '/tests/stats/print'],
        'schedule_my' => ['/schedule'],
        'schedule_teacher' => ['/schedule/teacher'],
        'schedule_constructor' => ['/schedule/constructor', '/schedule/entries', '/schedule/entries/{id}/edit', '/schedule/entries/{id}/delete', '/schedule/copy-week', '/schedule/recalculate-week'],
        'schedule_constructor_settings' => ['/schedule/constructor/settings', '/schedule/constructor/subjects', '/schedule/constructor/subjects/{id}/delete'],
        /** База знаний (wiki): сначала узкие пути, иначе /wiki/create попадёт под /wiki/{slug}. */
        'knowledge_wiki_edit' => ['/wiki/create', '/wiki/store', '/wiki/{slug}/edit'],
        'knowledge_wiki' => ['/wiki', '/wiki/{slug}'],
        /** Программное право: вход в интерфейс при отключённом сайте (без привязки к URL). */
        'maintenance_bypass' => [],
    ];

    public const LABELS = [
        'dashboard' => 'Главная и профиль',
        'orders_my' => 'Мои заявки',
        'orders_admin' => 'Управление заявками',
        'inventory_my' => 'Мой инвентарь',
        'inventory_admin' => 'Управление инвентарем',
        'employees_manage' => 'Пользователи',
        'roles_manage' => 'Управление ролями',
        'groups_manage' => 'Управление группами',
        'faculties_manage' => 'Образование: факультеты',
        'chairs_manage' => 'Образование: кафедры',
        'portfolio' => 'Портфолио: мои материалы',
        'portfolio_types' => 'Портфолио: типы и роли',
        'portfolio_confirm' => 'Портфолио: подтверждение',
        'settings' => 'Настройки системы',
        'settings_database' => 'Настройки БД и перенос данных',
        'notifications' => 'Уведомления',
        'tasks' => 'Задачи',
        'password_manager' => 'Менеджер паролей',
        'student_tests' => 'Тесты группы (студент)',
        'tests_admin' => 'Администрирование тестов',
        'tests_stats' => 'Статистика тестирования',
        'schedule_my' => 'Расписание: просмотр (студент)',
        'schedule_teacher' => 'Расписание: просмотр (преподаватель)',
        'schedule_constructor' => 'Расписание: конструктор (редактирование)',
        'schedule_constructor_settings' => 'Расписание: настройки конструктора',
        'knowledge_wiki' => 'Wiki: просмотр статей',
        'knowledge_wiki_edit' => 'Wiki: создание и правка статей',
        'maintenance_bypass' => 'Доступ при отключённом сайте (техобслуживание)',
    ];

    /**
     * Группы прав для экрана «Управление ролями» (порядок секций и пунктов).
     *
     * @return array<string, array<string, string>> [заголовок секции => [page_key => подпись]]
     */
    public static function groupedLabelsForRoles(): array
    {
        $labels = self::LABELS;
        $groups = [
            'Главная и ежедневная работа' => ['dashboard', 'notifications', 'tasks'],
            'База знаний (wiki)' => ['knowledge_wiki', 'knowledge_wiki_edit'],
            'Заявки' => ['orders_my', 'orders_admin'],
            'Инвентарь' => ['inventory_my', 'inventory_admin'],
            'Пользователи, роли и группы' => ['employees_manage', 'roles_manage', 'groups_manage'],
            'Образование: факультеты и кафедры' => ['faculties_manage', 'chairs_manage'],
            'Тестирование' => ['student_tests', 'tests_admin', 'tests_stats'],
            'Расписание: просмотр' => ['schedule_my', 'schedule_teacher'],
            'Расписание: конструктор и настройки' => ['schedule_constructor', 'schedule_constructor_settings'],
            'Портфолио' => ['portfolio', 'portfolio_types', 'portfolio_confirm'],
            'Настройки системы' => ['settings', 'settings_database'],
            'Безопасность и обслуживание' => ['password_manager', 'maintenance_bypass'],
        ];

        $result = [];
        $used = [];

        foreach ($groups as $title => $keys) {
            $items = [];
            foreach ($keys as $key) {
                if (isset($labels[$key])) {
                    $items[$key] = $labels[$key];
                    $used[$key] = true;
                }
            }
            if ($items !== []) {
                $result[$title] = $items;
            }
        }

        $rest = [];
        foreach ($labels as $key => $caption) {
            if (! isset($used[$key])) {
                $rest[$key] = $caption;
            }
        }
        if ($rest !== []) {
            $result['Прочее'] = $rest;
        }

        return $result;
    }

    public static function pathToPageKey(string $path, ?string $httpMethod = null): ?string
    {
        $normalized = '/'.ltrim($path, '/');
        if ($normalized === '//') {
            $normalized = '/';
        }

        $method = strtoupper($httpMethod ?? 'GET');
        // PATCH/DELETE по /wiki/{slug} — не чтение, а изменение (иначе хватало бы только knowledge_wiki).
        if (preg_match('#^/wiki/[^/]+$#', $normalized) && in_array($method, ['PATCH', 'DELETE'], true)) {
            return 'knowledge_wiki_edit';
        }

        foreach (self::MAP as $pageKey => $patterns) {
            foreach ($patterns as $pattern) {
                if (self::matches($normalized, $pattern)) {
                    return $pageKey;
                }
            }
        }

        return null;
    }

    public static function allLabels(): array
    {
        return self::LABELS;
    }

    private static function matches(string $path, string $pattern): bool
    {
        // Нельзя сначала preg_quote всего паттерна: скобки {id} превратятся в \{id\},
        // и плейсхолдеры перестанут подставляться.
        $token = 'ZZZPATHSEGZZZ';
        $tmp = preg_replace('/\{[^}]+\}/', $token, $pattern);
        $quoted = preg_quote($tmp, '/');
        $regex = str_replace(preg_quote($token, '/'), '[^/]+', $quoted);

        return (bool) preg_match('#^'.$regex.'$#', $path);
    }
}
