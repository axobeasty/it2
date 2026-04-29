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

        $now = now();

        $readerRoleIds = DB::table('role_page_permissions')
            ->where('page_key', 'dashboard')
            ->pluck('role_id');

        foreach ($readerRoleIds as $roleId) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'knowledge_wiki'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $editorRoleIds = DB::table('role_page_permissions')
            ->where('page_key', 'settings')
            ->pluck('role_id');

        foreach ($editorRoleIds as $roleId) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'knowledge_wiki_edit'],
                ['created_at' => $now, 'updated_at' => $now]
            );
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'knowledge_wiki'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_page_permissions')) {
            return;
        }

        DB::table('role_page_permissions')->whereIn('page_key', ['knowledge_wiki', 'knowledge_wiki_edit'])->delete();
    }
};
