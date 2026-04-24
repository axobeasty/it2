<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        setlocale(LC_ALL, 'ru_RU.utf8');
        Carbon::setLocale(config('app.locale'));

        $activeProfile = (string) env('DB_ACTIVE_PROFILE', 'sqlite');
        if ($activeProfile !== 'remote') {
            return;
        }

        try {
            DB::purge('mysql_remote');
            DB::connection('mysql_remote')->select('SELECT 1');
        } catch (\Throwable $e) {
            // Мягкий деградирующий режим: если remote нестабилен,
            // не даем приложению упасть на каждом запросе.
            Config::set('database.default', 'sqlite');
        }
    }
}
