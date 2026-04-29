<?php

namespace Database\Seeders;

class Department extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUpsert('departments', ['title' => 'Основное подразделение'], []);
    }
}
