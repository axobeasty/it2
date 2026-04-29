<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Ключи и TTL для снижения нагрузки на БД на горячих путях запроса.
 */
final class RequestPerformanceCache
{
    public const REMOTE_DB_HEALTH_KEY = 'app:perf:mysql_remote_reachable';

    public const REMOTE_DB_HEALTH_TTL_SECONDS = 45;

    /** Кэш модели сотрудника + права страниц (инвалидация при смене роли / прав). */
    public const EMPLOYEE_PAGE_ACCESS_PREFIX = 'app:perf:employee_page_access:';

    public const EMPLOYEE_PAGE_ACCESS_TTL_SECONDS = 120;

    public const NOTIF_UNREAD_PREFIX = 'app:perf:notif_unread_count:';

    public const NOTIF_UNREAD_TTL_SECONDS = 12;

    public static function employeePageAccessKey(int $employeeId): string
    {
        return self::EMPLOYEE_PAGE_ACCESS_PREFIX.$employeeId;
    }

    public static function forgetEmployeePageAccess(int $employeeId): void
    {
        Cache::forget(self::employeePageAccessKey($employeeId));
    }

    /** @param  iterable<int|string>  $employeeIds */
    public static function forgetEmployeePageAccessMany(iterable $employeeIds): void
    {
        foreach ($employeeIds as $id) {
            self::forgetEmployeePageAccess((int) $id);
        }
    }

    public static function notifUnreadKey(int $employeeId): string
    {
        return self::NOTIF_UNREAD_PREFIX.$employeeId;
    }

    public static function forgetNotifUnreadCount(int $employeeId): void
    {
        Cache::forget(self::notifUnreadKey($employeeId));
    }
}
