<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Roles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        foreach ([
            'Администратор',
            'Директор',
            'Заместитель директора',
            'Преподаватель',
            'Студент',
        ] as $name) {
            DB::table('roles')->insert([
                'name' => $name,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
