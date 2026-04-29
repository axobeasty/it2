<?php

namespace Database\Seeders;

class Port_types extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUpsert('portfolio_types', ['name' => 'Конференции'], []);
    }
}
