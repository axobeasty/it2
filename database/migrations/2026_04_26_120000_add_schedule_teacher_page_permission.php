<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $roleId = DB::table('roles')->where('name', 'Преподаватель')->value('id');
        if ($roleId) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'schedule_teacher'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_page_permissions')->where('page_key', 'schedule_teacher')->delete();
    }
};
