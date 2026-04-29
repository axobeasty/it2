<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Роли с правом редактирования wiki должны видеть и список статей (/wiki).
     */
    public function up(): void
    {
        if (! Schema::hasTable('role_page_permissions')) {
            return;
        }

        $now = now();
        $roleIds = DB::table('role_page_permissions')
            ->where('page_key', 'knowledge_wiki_edit')
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'page_key' => 'knowledge_wiki'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
