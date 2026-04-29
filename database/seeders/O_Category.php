<?php

namespace Database\Seeders;

class O_Category extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUpsert('o__categories', ['name' => 'Основная категория'], [
            'cat_color' => 'white',
        ]);
    }
}
