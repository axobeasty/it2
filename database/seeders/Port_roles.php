<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Port_roles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        DB::table('portfolio_roles')->insert([
            'name' => 'Главный автор',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('portfolio_roles')->insert([
            'name' => 'Соавтор',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
