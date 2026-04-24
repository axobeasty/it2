<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Заменяет единое право academic_structure_manage на faculties_manage и chairs_manage.
     */
    public function up(): void
    {
        $now = now();
        $roleIds = DB::table('role_page_permissions')
            ->where('page_key', 'academic_structure_manage')
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            foreach (['faculties_manage', 'chairs_manage'] as $pageKey) {
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

        DB::table('role_page_permissions')->where('page_key', 'academic_structure_manage')->delete();
    }

    public function down(): void
    {
        $facultyRoles = DB::table('role_page_permissions')->where('page_key', 'faculties_manage')->pluck('role_id');
        $chairRoles = DB::table('role_page_permissions')->where('page_key', 'chairs_manage')->pluck('role_id');
        $both = $facultyRoles->intersect($chairRoles);
        $now = now();
        foreach ($both as $roleId) {
            DB::table('role_page_permissions')->insert([
                'role_id' => $roleId,
                'page_key' => 'academic_structure_manage',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('role_page_permissions')->whereIn('page_key', ['faculties_manage', 'chairs_manage'])->delete();
    }
};
