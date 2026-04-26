<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchedulePagePermissionsSeeder extends Seeder
{
    /**
     * Права на страницы расписания (идемпотентно, можно вызывать после Roles).
     */
    public function run(): void
    {
        $now = now();
        $assign = [['Студент', 'schedule_my']];
        $assign[] = ['Преподаватель', 'schedule_teacher'];
        foreach (['Администратор', 'Заместитель директора', 'Директор', 'Преподаватель'] as $roleName) {
            $assign[] = [$roleName, 'schedule_constructor'];
            $assign[] = [$roleName, 'schedule_constructor_settings'];
        }

        foreach ($assign as [$roleName, $pageKey]) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('role_page_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'page_key' => $pageKey],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
