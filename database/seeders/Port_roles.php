<?php

namespace Database\Seeders;

class Port_roles extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Главный автор', 'Соавтор'] as $name) {
            $this->seedUpsert('portfolio_roles', ['name' => $name], []);
        }
    }
}
