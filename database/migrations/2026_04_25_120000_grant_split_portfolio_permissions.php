<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Для ролей, у которых уже было право «portfolio», добавляем «portfolio_types» и «portfolio_confirm»,
     * чтобы поведение после разбиения прав совпадало с прежним.
     */
    public function up(): void
    {
        $roleIds = DB::table('role_page_permissions')
            ->where('page_key', 'portfolio')
            ->pluck('role_id');

        $now = now();
        foreach ($roleIds as $roleId) {
            foreach (['portfolio_types', 'portfolio_confirm'] as $pageKey) {
                $exists = DB::table('role_page_permissions')
                    ->where('role_id', $roleId)
                    ->where('page_key', $pageKey)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('role_page_permissions')->insert([
                    'role_id' => $roleId,
                    'page_key' => $pageKey,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_page_permissions')
            ->whereIn('page_key', ['portfolio_types', 'portfolio_confirm'])
            ->delete();
    }
};
