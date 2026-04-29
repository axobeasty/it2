<?php

namespace App\Providers;

use App\Models\Notifs;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        $this->bootAuthRateLimiters();
        $this->warnIfSessionSecurityIsWeakInProduction();

        setlocale(LC_ALL, 'ru_RU.utf8');
        Carbon::setLocale(config('app.locale'));

        View::composer('layout.nav', function ($view) {
            $user = $view->offsetGet('user') ?? null;
            $unread = 0;
            if ($user && isset($user->id)) {
                $unread = Notifs::query()
                    ->where('employee_id', (int) $user->id)
                    ->where('is_read', false)
                    ->count();
            }
            $view->with('unreadNotifsCount', $unread);
        });

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

    private function bootAuthRateLimiters(): void
    {
        RateLimiter::for('login-web', function (Request $request) {
            $login = mb_strtolower((string) $request->input('login', ''));
            $key = 'web|'.$request->ip().'|'.$login;

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('login-mobile', function (Request $request) {
            $login = mb_strtolower((string) $request->input('login', ''));
            $key = 'mobile|'.$request->ip().'|'.$login;

            return Limit::perMinute(8)->by($key);
        });
    }

    private function warnIfSessionSecurityIsWeakInProduction(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $warnings = [];
        if (! (bool) config('session.secure')) {
            $warnings[] = 'SESSION_SECURE_COOKIE is disabled';
        }
        if (! (bool) config('session.http_only')) {
            $warnings[] = 'SESSION_HTTP_ONLY is disabled';
        }
        if ((string) config('session.same_site') === 'none' && ! (bool) config('session.secure')) {
            $warnings[] = 'SESSION_SAME_SITE=none requires SESSION_SECURE_COOKIE=true';
        }
        if (! (bool) config('session.encrypt')) {
            $warnings[] = 'SESSION_ENCRYPT is disabled';
        }

        if ($warnings !== []) {
            Log::warning('security.session_hardening_warning', [
                'warnings' => $warnings,
            ]);
        }
    }
}
