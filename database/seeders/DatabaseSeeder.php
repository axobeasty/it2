<?php

namespace Database\Seeders;

class DatabaseSeeder extends BaseSeeder
{
    /**
     * Seed the application's database.
     *
     * Дочерние сидеры наследуют {@see BaseSeeder}: вставки по уникальным ключам без дубликатов при повторном запуске.
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
        $this->call(WikiKnowledgeBaseSeeder::class);
    }
}
