<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;

class TestUser extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedOnce('employees', ['login' => 'test'], [
            'email' => 'test@domain.ru',
            'fio' => 'Тестовый аккаунт',
            'password' => Hash::make('123'),
            'department_id' => 1,
            'role_id' => 1,
            'active' => 1,
            'room' => 307,
        ]);
    }
}
