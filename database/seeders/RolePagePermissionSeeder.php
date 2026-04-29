<?php

namespace Database\Seeders;

use App\Models\RolePagePermission;
use App\Models\Roles;

class RolePagePermissionSeeder extends BaseSeeder
{
    public function run(): void
    {
        $keys = [
            'dashboard',
            'orders_my',
            'orders_admin',
            'inventory_my',
            'inventory_admin',
            'employees_manage',
            'roles_manage',
            'faculties_manage',
            'chairs_manage',
            'portfolio',
            'portfolio_types',
            'portfolio_confirm',
            'settings',
            'settings_database',
            'notifications',
            'tasks',
        ];

        foreach (Roles::all() as $role) {
            foreach ($keys as $key) {
                RolePagePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'page_key' => $key,
                ]);
            }
        }
    }
}
