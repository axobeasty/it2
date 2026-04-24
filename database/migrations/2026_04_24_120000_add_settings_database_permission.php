<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_page_permissions')) {
            return;
        }

        $roleIds = DB::table('role_page_permissions')
            ->where('page_key', 'settings')
            ->pluck('role_id')
            ->unique()
            ->values();

        $now = now();
        foreach ($roleIds as $roleId) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'settings_database'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_page_permissions')) {
            return;
        }

        DB::table('role_page_permissions')
            ->where('page_key', 'settings_database')
            ->delete();
    }
};
