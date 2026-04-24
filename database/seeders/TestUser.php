<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('employees')->insert([
            'login' => 'test',
            'fio' => 'Тестовый аккаунт',
            'password' => Hash::make('123'),
            'department_id' => 1,
            'role_id'=>1,
            'active' => 1,
            'room'=>307,
        ]);
    }
}
