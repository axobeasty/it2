<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_page_permissions')) {
            return;
        }

        $roleIds = collect();

        if (Schema::hasColumn('employees', 'is_admin')) {
            $roleIds = $roleIds->merge(
                DB::table('employees')->where('is_admin', 1)->distinct()->pluck('role_id')
            );
        }

        $adminRoleId = DB::table('roles')->where('name', 'Администратор')->value('id');
        if ($adminRoleId) {
            $roleIds->push($adminRoleId);
        }

        $roleIds = $roleIds->filter()->unique()->values();
        $now = now();

        foreach ($roleIds as $rid) {
            DB::table('role_page_permissions')->updateOrInsert(
                ['role_id' => $rid, 'page_key' => 'maintenance_bypass'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        if (DB::table('role_page_permissions')->where('page_key', 'maintenance_bypass')->doesntExist()) {
            if (DB::table('roles')->where('id', 1)->exists()) {
                DB::table('role_page_permissions')->updateOrInsert(
                    ['role_id' => 1, 'page_key' => 'maintenance_bypass'],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if (Schema::hasColumn('employees', 'is_admin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }

        if (Schema::hasTable('portfolio_roles') && Schema::hasColumn('portfolio_roles', 'is_admin')) {
            Schema::table('portfolio_roles', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'is_admin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }

        if (Schema::hasTable('portfolio_roles') && ! Schema::hasColumn('portfolio_roles', 'is_admin')) {
            Schema::table('portfolio_roles', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }

        if (Schema::hasTable('role_page_permissions')) {
            DB::table('role_page_permissions')->where('page_key', 'maintenance_bypass')->delete();
        }
    }
};
