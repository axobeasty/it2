<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(Roles::class);
        $this->call(RolePagePermissionSeeder::class);
        $this->call(SchedulePagePermissionsSeeder::class);
        $this->call(Department::class);
        $this->call(TestUser::class);
        $this->call(Settings::class);
        $this->call(O_Category::class);
        $this->call(Port_roles::class);
        $this->call(Port_types::class);
    }
}
