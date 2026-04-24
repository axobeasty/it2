<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Settings extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->insert([
            'title' => 'Кгамт им. Л.Б. Васильева - IT Отдел',
            'is_enabled' => true,
            'auth_mode'=>2,
            'disable_reason' => 'Сайт в текущий момент находится на техническом обслуживании и в скором времени станет доступен!',
            'mode'=> true
        ]);
    }
}
