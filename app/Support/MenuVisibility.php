<?php

namespace App\Support;

/**
 * Флаги видимости пунктов меню (navbar + sidebar). Единая логика прав.
 *
 * @return array<string, bool>
 */
class MenuVisibility
{
    public static function flags(object $user): array
    {
        return [
            'canDashboard' => $user->canAccessPage('dashboard'),
            'canOrdersMy' => $user->canAccessPage('orders_my'),
            'canOrdersAdmin' => $user->canAccessPage('orders_admin'),
            'canInventoryMy' => $user->canAccessPage('inventory_my'),
            'canInventoryAdmin' => $user->canAccessPage('inventory_admin'),
            'canEmployees' => $user->canAccessPage('employees_manage'),
            'canRoles' => $user->canAccessPage('roles_manage'),
            'canGroups' => $user->canAccessPage('groups_manage'),
            'canFaculties' => $user->canAccessPage('faculties_manage'),
            'canChairs' => $user->canAccessPage('chairs_manage'),
            'canSettings' => $user->canAccessPage('settings'),
            'canSettingsDatabase' => $user->canAccessPage('settings_database'),
            'canPortfolioOwn' => $user->canAccessPage('portfolio'),
            'canPortfolioTypes' => $user->canAccessPage('portfolio_types'),
            'canPortfolioConfirm' => $user->canAccessPage('portfolio_confirm'),
            'canPasswords' => $user->canAccessPage('password_manager'),
            'canStudentTests' => $user->canAccessPage('student_tests'),
            'canTestsAdmin' => $user->canAccessPage('tests_admin'),
            'canTestsStats' => $user->canAccessPage('tests_stats'),
            'canScheduleMy' => $user->canAccessPage('schedule_my'),
            'canScheduleTeacher' => $user->canAccessPage('schedule_teacher'),
            'canScheduleConstructor' => $user->canAccessPage('schedule_constructor'),
            'canScheduleConstructorSettings' => $user->canAccessPage('schedule_constructor_settings'),
        ];
    }
}
