<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_page_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('page_key');
            $table->timestamps();

            $table->unique(['role_id', 'page_key']);
        });

        $defaultPageKeys = [
            'dashboard',
            'orders_my',
            'orders_admin',
            'inventory_my',
            'inventory_admin',
            'employees_manage',
            'roles_manage',
            'portfolio',
            'settings',
            'notifications',
            'tasks',
        ];

        $roleIds = DB::table('roles')->pluck('id');
        $rows = [];
        $now = now();
        foreach ($roleIds as $roleId) {
            foreach ($defaultPageKeys as $pageKey) {
                $rows[] = [
                    'role_id' => $roleId,
                    'page_key' => $pageKey,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('role_page_permissions')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_page_permissions');
    }
};
