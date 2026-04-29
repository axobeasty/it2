<?php

namespace Database\Seeders;

class Roles extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Администратор',
            'Директор',
            'Заместитель директора',
            'Преподаватель',
            'Студент',
        ] as $name) {
            $this->seedUpsert('roles', ['name' => $name], [
                'is_system' => true,
            ]);
        }
    }
}
