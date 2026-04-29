<?php

namespace Database\Seeders;

class Settings extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUpsert('settings', ['id' => 1], [
            'title' => 'Кгамт им. Л.Б. Васильева - IT Отдел',
            'is_enabled' => true,
            'auth_mode' => 2,
            'disable_reason' => 'Сайт в текущий момент находится на техническом обслуживании и в скором времени станет доступен!',
            'mode' => true,
        ]);
    }
}
