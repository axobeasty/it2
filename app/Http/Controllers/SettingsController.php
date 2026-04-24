<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Support\DatabaseProfileManager;
use Brian2694\Toastr\Facades\Toastr;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function __construct(private readonly DatabaseProfileManager $databaseProfileManager)
    {
    }

    public function index(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.general',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function authenticate(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.authenticate',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function general(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.general',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function database(Request $request)
    {
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.database',[
                    'user' => $user,
                    'settings' => $settings,
                    'activeProfile' => $this->resolveCurrentDatabaseProfile(),
                    'remoteHost' => env('DB_REMOTE_HOST', ''),
                    'remotePort' => env('DB_REMOTE_PORT', '3306'),
                    'remoteDatabase' => env('DB_REMOTE_DATABASE', ''),
                    'remoteUsername' => env('DB_REMOTE_USERNAME', ''),
                    'remotePassword' => env('DB_REMOTE_PASSWORD', ''),
                    'remoteCharset' => env('DB_REMOTE_CHARSET', 'utf8mb4'),
                    'remoteCollation' => env('DB_REMOTE_COLLATION', 'utf8mb4_unicode_ci'),
                ]);
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function disable(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                $settings->is_enabled = 0;
                $settings->save();
                Toastr::success('Техническое обслуживание', 'Сайт успешно выключен!', ["progressBar"=> true]);
                return redirect('/settings/general');
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function save(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();

            if($user->canAccessPage('settings')){
                if($request->input('page') == 'authenticate'){
                    $settings->auth_mode = $request->input('auth_method');


                    $settings->save();
                }
                else if($request->input('page') == 'general'){
                    $settings->title = $request->input('title');
                    $settings->disable_reason = $request->input('disable_reason');

                    $settings->save();
                }else if($request->input('page') == 'email'){
                    $settings->email_enabled = $request->input('email_enabled');
                    $settings->save();
                }else if($request->input('page') == 'database'){
                    $validated = $request->validate([
                        'db_profile' => ['required', 'in:sqlite,remote'],
                        'remote_host' => ['nullable', 'string', 'max:255'],
                        'remote_port' => ['nullable', 'string', 'max:10'],
                        'remote_database' => ['nullable', 'string', 'max:255'],
                        'remote_username' => ['nullable', 'string', 'max:255'],
                        'remote_password' => ['nullable', 'string', 'max:255'],
                        'remote_charset' => ['nullable', 'string', 'max:64'],
                        'remote_collation' => ['nullable', 'string', 'max:64'],
                    ]);

                    if ($validated['db_profile'] === 'remote' && empty($validated['remote_host'])) {
                        Toastr::error('Для remote профиля укажите host удаленной БД.', 'Ошибка валидации', ["progressBar"=> true]);
                        return redirect('/settings/database');
                    }

                    $this->databaseProfileManager->applyDatabaseSettings([
                        'db_profile' => $validated['db_profile'],
                        'remote_host' => (string) ($validated['remote_host'] ?? ''),
                        'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
                        'remote_database' => (string) ($validated['remote_database'] ?? ''),
                        'remote_username' => (string) ($validated['remote_username'] ?? ''),
                        'remote_password' => (string) ($validated['remote_password'] ?? ''),
                        'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
                        'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
                    ]);
                }


                Toastr::success('Успешное сохранение', 'Настройки сайта успешно сохранены', ["progressBar"=> true]);
                return redirect('/settings/'.$request->input('page'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }
    public function enable(Request $request){
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                $settings->is_enabled = 1;
                $settings->save();
                Toastr::success('Техническое обслуживание', 'Сайт успешно включен!', ["progressBar"=> true]);
                return redirect('/settings/general');
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
            return redirect('/');
        }
    }

    public function migrateDatabase(Request $request)
    {
        if (! $request->session()->has('user')) {
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $validated = $request->validate([
            'source_profile' => ['required', 'in:sqlite,remote'],
            'target_profile' => ['required', 'in:sqlite,remote'],
        ]);

        if ($validated['source_profile'] === $validated['target_profile']) {
            Toastr::error('Источник и назначение должны отличаться.', 'Ошибка', ["progressBar"=> true]);
            return redirect('/settings/database');
        }

        try {
            $this->databaseProfileManager->migrateBetweenProfiles(
                $validated['source_profile'],
                $validated['target_profile']
            );
            Toastr::success('Перенос данных завершен успешно.', 'Готово', ["progressBar"=> true]);
        } catch (\Throwable $e) {
            Toastr::error('Не удалось перенести данные: '.$e->getMessage(), 'Ошибка', ["progressBar"=> true]);
        }

        return redirect('/settings/database');
    }

    public function migrateDatabaseStream(Request $request): StreamedResponse
    {
        if (! $request->session()->has('user')) {
            return response()->stream(function () {
                $this->streamEvent('error', 0, 'Необходима авторизация.');
                $this->streamEvent('done', 0, 'Операция завершена с ошибкой.');
            }, 401, $this->streamHeaders());
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->stream(function () {
                $this->streamEvent('error', 0, 'У Вас недостаточно прав для выполнения этого действия.');
                $this->streamEvent('done', 0, 'Операция завершена с ошибкой.');
            }, 403, $this->streamHeaders());
        }

        $validated = $request->validate([
            'source_profile' => ['required', 'in:sqlite,remote'],
            'target_profile' => ['required', 'in:sqlite,remote'],
        ]);

        return response()->stream(function () use ($validated) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
            ignore_user_abort(true);

            if ($validated['source_profile'] === $validated['target_profile']) {
                $this->streamEvent('error', 0, 'Источник и назначение должны отличаться.');
                $this->streamEvent('done', 0, 'Операция завершена с ошибкой.');
                return;
            }

            $this->streamEvent('progress', 2, 'Запуск миграции данных...');
            try {
                $this->databaseProfileManager->migrateBetweenProfiles(
                    $validated['source_profile'],
                    $validated['target_profile'],
                    function (int $percent, string $message): void {
                        $this->streamEvent('progress', $percent, $message);
                    }
                );

                $this->streamEvent('success', 100, 'Перенос данных завершен успешно.');
                $this->streamEvent('done', 100, 'Операция завершена.');
            } catch (\Throwable $e) {
                $this->streamEvent('error', 100, 'Ошибка миграции: '.$e->getMessage());
                $this->streamEvent('done', 100, 'Операция завершена с ошибкой.');
            }
        }, 200, $this->streamHeaders());
    }

    public function saveDatabase(Request $request)
    {
        $wantsJson = $request->expectsJson() || $request->wantsJson();
        if (! $request->session()->has('user')) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Необходима авторизация.'], 401);
            }
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'У Вас недостаточно прав для выполнения этого действия.'], 403);
            }
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        $validated = $request->validate([
            'db_profile' => ['required', 'in:sqlite,remote'],
            'remote_host' => ['nullable', 'string', 'max:255'],
            'remote_port' => ['nullable', 'string', 'max:10'],
            'remote_database' => ['nullable', 'string', 'max:255'],
            'remote_username' => ['nullable', 'string', 'max:255'],
            'remote_password' => ['nullable', 'string', 'max:255'],
            'remote_charset' => ['nullable', 'string', 'max:64'],
            'remote_collation' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validated['db_profile'] === 'remote' && empty($validated['remote_host'])) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Для remote профиля укажите host удаленной БД.'], 422);
            }
            Toastr::error('Для remote профиля укажите host удаленной БД.', 'Ошибка валидации', ["progressBar"=> true]);
            return redirect('/settings/database');
        }

        if ($validated['db_profile'] === 'remote') {
            if (! extension_loaded('pdo_mysql')) {
                if ($wantsJson) {
                    return response()->json(['ok' => false, 'message' => 'В PHP не включен драйвер pdo_mysql.'], 422);
                }
                Toastr::error('В PHP не включен драйвер pdo_mysql. Установите/включите расширение и повторите попытку.', 'Ошибка окружения', ["progressBar"=> true]);
                return redirect('/settings/database');
            }

            // Сначала сохраняем только параметры remote, но не переключаем профиль.
            $this->databaseProfileManager->applyDatabaseSettings([
                'db_profile' => 'sqlite',
                'remote_host' => (string) ($validated['remote_host'] ?? ''),
                'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
                'remote_database' => (string) ($validated['remote_database'] ?? ''),
                'remote_username' => (string) ($validated['remote_username'] ?? ''),
                'remote_password' => (string) ($validated['remote_password'] ?? ''),
                'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
                'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
            ]);

            try {
                $this->databaseProfileManager->checkRemoteConnection();
            } catch (\Throwable $e) {
                if ($wantsJson) {
                    return response()->json(['ok' => false, 'message' => 'Подключение к удаленной БД не прошло проверку: '.$e->getMessage()], 422);
                }
                Toastr::error('Подключение к удаленной БД не прошло проверку: '.$e->getMessage(), 'Ошибка подключения', ["progressBar"=> true]);
                return redirect('/settings/database');
            }

            // Проверяем, что удаленная БД пригодна как основная БД приложения.
            // Иначе переключение профиля ломает авторизацию/настройки на следующем запросе.
            try {
                DB::connection('mysql_remote')->select('SELECT 1');
                $requiredTables = ['settings', 'employees'];
                foreach ($requiredTables as $tableName) {
                    if (! Schema::connection('mysql_remote')->hasTable($tableName)) {
                        throw new \RuntimeException("В удаленной БД отсутствует обязательная таблица: {$tableName}.");
                    }
                }
            } catch (\Throwable $e) {
                if ($wantsJson) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Удаленная БД еще не готова для переключения профиля: '.$e->getMessage().' Сначала выполните инициализацию/миграцию, профиль остается SQLite.',
                    ], 422);
                }
                Toastr::error(
                    'Удаленная БД еще не готова для переключения профиля: '.$e->getMessage().' Сначала выполните инициализацию/миграцию, профиль остается SQLite.',
                    'Профиль не переключен',
                    ["progressBar"=> true]
                );
                return redirect('/settings/database');
            }
        }

        $this->databaseProfileManager->applyDatabaseSettings([
            'db_profile' => $validated['db_profile'],
            'remote_host' => (string) ($validated['remote_host'] ?? ''),
            'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
            'remote_database' => (string) ($validated['remote_database'] ?? ''),
            'remote_username' => (string) ($validated['remote_username'] ?? ''),
            'remote_password' => (string) ($validated['remote_password'] ?? ''),
            'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
            'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
        ]);

        // Финальная страховка: после фактического переключения на remote проверяем,
        // что приложение может выполнять базовые запросы. Если нет — откатываемся на sqlite.
        if ($validated['db_profile'] === 'remote') {
            try {
                DB::connection('mysql_remote')->table('settings')->limit(1)->get();
                if (isset($user->id)) {
                    DB::connection('mysql_remote')->table('employees')->where('id', (int) $user->id)->limit(1)->get();
                }
            } catch (\Throwable $e) {
                $this->databaseProfileManager->applyDatabaseSettings([
                    'db_profile' => 'sqlite',
                    'remote_host' => (string) ($validated['remote_host'] ?? ''),
                    'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
                    'remote_database' => (string) ($validated['remote_database'] ?? ''),
                    'remote_username' => (string) ($validated['remote_username'] ?? ''),
                    'remote_password' => (string) ($validated['remote_password'] ?? ''),
                    'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
                    'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
                ]);

                if ($wantsJson) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Профиль remote не применен: после переключения БД вернула ошибку ('.$e->getMessage().'). Выполнен автоматический откат на SQLite.',
                    ], 422);
                }
                Toastr::error(
                    'Профиль remote не применен: после переключения БД вернула ошибку ('.$e->getMessage().'). Выполнен автоматический откат на SQLite.',
                    'Профиль не переключен',
                    ["progressBar"=> true]
                );
                return redirect('/settings/database');
            }
        }

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'message' => 'Настройки БД успешно сохранены.',
                'active_profile' => $validated['db_profile'],
            ]);
        }
        Toastr::success('Успешное сохранение', 'Настройки БД успешно сохранены', ["progressBar"=> true]);
        return redirect('/settings/database');
    }

    public function testRemoteDatabaseConnection(Request $request)
    {
        if (! $request->session()->has('user')) {
            return response()->json([
                'ok' => false,
                'message' => 'Необходима авторизация.',
            ], 401);
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->json([
                'ok' => false,
                'message' => 'У Вас недостаточно прав для выполнения этого действия.',
            ], 403);
        }

        try {
            $this->databaseProfileManager->checkRemoteConnection();
            return response()->json([
                'ok' => true,
                'message' => 'Подключение к удаленной БД успешно.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function dryRunRemoteInitialization(Request $request)
    {
        if (! $request->session()->has('user')) {
            return response()->json([
                'ok' => false,
                'message' => 'Необходима авторизация.',
            ], 401);
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->json([
                'ok' => false,
                'message' => 'У Вас недостаточно прав для выполнения этого действия.',
            ], 403);
        }

        try {
            $inspection = $this->databaseProfileManager->inspectRemoteDatabase();
            $mode = $inspection['is_empty'] ? 'empty' : 'not_empty';

            return response()->json([
                'ok' => true,
                'mode' => $mode,
                'tables_before' => $inspection['tables_before'],
                'message' => $mode === 'empty'
                    ? 'Dry-run: база пустая, будет выполнено migrate + db:seed.'
                    : 'Dry-run: база не пустая, будет выполнено migrate:fresh + db:seed.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function saveRemoteDraft(Request $request)
    {
        if (! $request->session()->has('user')) {
            return response()->json([
                'ok' => false,
                'message' => 'Необходима авторизация.',
            ], 401);
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->json([
                'ok' => false,
                'message' => 'У Вас недостаточно прав для выполнения этого действия.',
            ], 403);
        }

        $validated = $request->validate([
            'remote_host' => ['nullable', 'string', 'max:255'],
            'remote_port' => ['nullable', 'string', 'max:10'],
            'remote_database' => ['nullable', 'string', 'max:255'],
            'remote_username' => ['nullable', 'string', 'max:255'],
            'remote_password' => ['nullable', 'string', 'max:255'],
            'remote_charset' => ['nullable', 'string', 'max:64'],
            'remote_collation' => ['nullable', 'string', 'max:64'],
        ]);

        if (empty($validated['remote_host'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Для remote профиля укажите host удаленной БД.',
            ], 422);
        }

        $this->databaseProfileManager->applyDatabaseSettings([
            'db_profile' => 'sqlite',
            'remote_host' => (string) ($validated['remote_host'] ?? ''),
            'remote_port' => (string) ($validated['remote_port'] ?? '3306'),
            'remote_database' => (string) ($validated['remote_database'] ?? ''),
            'remote_username' => (string) ($validated['remote_username'] ?? ''),
            'remote_password' => (string) ($validated['remote_password'] ?? ''),
            'remote_charset' => (string) ($validated['remote_charset'] ?? 'utf8mb4'),
            'remote_collation' => (string) ($validated['remote_collation'] ?? 'utf8mb4_unicode_ci'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Параметры удаленной БД сохранены (профиль пока не переключен).',
            'active_profile' => 'sqlite',
        ]);
    }

    public function activateDatabaseProfile(Request $request)
    {
        if (! $request->session()->has('user')) {
            return response()->json([
                'ok' => false,
                'message' => 'Необходима авторизация.',
            ], 401);
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->json([
                'ok' => false,
                'message' => 'У Вас недостаточно прав для выполнения этого действия.',
            ], 403);
        }

        $validated = $request->validate([
            'db_profile' => ['required', 'in:sqlite,remote'],
        ]);

        if ($validated['db_profile'] === 'remote') {
            try {
                $this->databaseProfileManager->checkRemoteConnection();
                foreach (['settings', 'employees'] as $tableName) {
                    if (! Schema::connection('mysql_remote')->hasTable($tableName)) {
                        throw new \RuntimeException("В удаленной БД отсутствует обязательная таблица: {$tableName}.");
                    }
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Профиль remote не может быть активирован: '.$e->getMessage(),
                ], 422);
            }
        }

        $this->databaseProfileManager->applyDatabaseSettings([
            'db_profile' => $validated['db_profile'],
            'remote_host' => (string) env('DB_REMOTE_HOST', ''),
            'remote_port' => (string) env('DB_REMOTE_PORT', '3306'),
            'remote_database' => (string) env('DB_REMOTE_DATABASE', ''),
            'remote_username' => (string) env('DB_REMOTE_USERNAME', ''),
            'remote_password' => (string) env('DB_REMOTE_PASSWORD', ''),
            'remote_charset' => (string) env('DB_REMOTE_CHARSET', 'utf8mb4'),
            'remote_collation' => (string) env('DB_REMOTE_COLLATION', 'utf8mb4_unicode_ci'),
        ]);

        // Финальная страховка: после фактического переключения на remote
        // проверяем реальные запросы приложения и при ошибке откатываем на sqlite.
        if ($validated['db_profile'] === 'remote') {
            try {
                DB::purge('mysql_remote');
                DB::connection('mysql_remote')->table('settings')->limit(1)->get();
            } catch (\Throwable $e) {
                $isGoneAway = str_contains(mb_strtolower($e->getMessage()), 'server has gone away');
                if ($isGoneAway) {
                    try {
                        DB::purge('mysql_remote');
                        DB::connection('mysql_remote')->table('settings')->limit(1)->get();

                        return response()->json([
                            'ok' => true,
                            'message' => 'Активный профиль БД переключен на remote (соединение восстановлено после переподключения).',
                            'active_profile' => 'remote',
                        ]);
                    } catch (\Throwable $retryError) {
                        $e = $retryError;
                    }
                }

                $this->databaseProfileManager->applyDatabaseSettings([
                    'db_profile' => 'sqlite',
                    'remote_host' => (string) env('DB_REMOTE_HOST', ''),
                    'remote_port' => (string) env('DB_REMOTE_PORT', '3306'),
                    'remote_database' => (string) env('DB_REMOTE_DATABASE', ''),
                    'remote_username' => (string) env('DB_REMOTE_USERNAME', ''),
                    'remote_password' => (string) env('DB_REMOTE_PASSWORD', ''),
                    'remote_charset' => (string) env('DB_REMOTE_CHARSET', 'utf8mb4'),
                    'remote_collation' => (string) env('DB_REMOTE_COLLATION', 'utf8mb4_unicode_ci'),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Профиль remote не применен: после переключения БД вернула ошибку ('.$e->getMessage().'). Выполнен автоматический откат на SQLite.',
                ], 422);
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Активный профиль БД переключен на '.$validated['db_profile'].'.',
            'active_profile' => $validated['db_profile'],
        ]);
    }

    public function initializeRemoteDatabase(Request $request)
    {
        if (! $request->session()->has('user')) {
            return redirect('/');
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
            return redirect('/');
        }

        try {
            $result = $this->databaseProfileManager->initializeRemoteDatabase();
            if ($result['mode'] === 'empty') {
                Toastr::success('Удаленная БД была пустой: выполнены миграции и сиды.', 'Инициализация БД', ["progressBar"=> true]);
            } else {
                Toastr::success('Удаленная БД не пустая: выполнены очистка, миграции и сиды.', 'Инициализация БД', ["progressBar"=> true]);
            }
        } catch (\Throwable $e) {
            Toastr::error('Не удалось инициализировать удаленную БД: '.$e->getMessage(), 'Инициализация БД', ["progressBar"=> true]);
        }

        return redirect('/settings/database');
    }

    public function initializeRemoteDatabaseStream(Request $request): StreamedResponse
    {
        if (! $request->session()->has('user')) {
            return response()->stream(function () {
                $this->streamEvent('error', 0, 'Необходима авторизация.');
                $this->streamEvent('done', 0, 'Операция завершена с ошибкой.');
            }, 401, $this->streamHeaders());
        }

        $user = $request->session()->get('user');
        if (! $user->canAccessPage('settings')) {
            return response()->stream(function () {
                $this->streamEvent('error', 0, 'У Вас недостаточно прав для выполнения этого действия.');
                $this->streamEvent('done', 0, 'Операция завершена с ошибкой.');
            }, 403, $this->streamHeaders());
        }

        return response()->stream(function () {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
            ignore_user_abort(true);

            $this->streamEvent('progress', 2, 'Запуск инициализации удаленной БД...');
            try {
                $result = $this->databaseProfileManager->initializeRemoteDatabase(
                    function (int $percent, string $message): void {
                        $this->streamEvent('progress', $percent, $message);
                    }
                );

                if (($result['mode'] ?? '') === 'empty') {
                    $this->streamEvent('success', 100, 'Удаленная БД была пустой: выполнены миграции и сиды.');
                } else {
                    $this->streamEvent('success', 100, 'Удаленная БД не пустая: выполнены очистка, миграции и сиды.');
                }
                $this->streamEvent('done', 100, 'Операция завершена.');
            } catch (\Throwable $e) {
                $this->streamEvent('error', 100, 'Ошибка инициализации: '.$e->getMessage());
                $this->streamEvent('done', 100, 'Операция завершена с ошибкой.');
            }
        }, 200, $this->streamHeaders());
    }
    public function notallowed(){
        return redirect('/');
    }

    public function email(Request $request)
    {
        if($request->session()->has('user')){
            $user = $request->session()->get('user');
            $settings = Settings::where('id',1)->first();
            if($user->canAccessPage('settings')){
                return view('settings.email',compact('user','settings'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        }else{
        return redirect('/');
    }
    }

    private function streamHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ];
    }

    private function streamEvent(string $type, int $percent, string $message): void
    {
        echo json_encode([
            'type' => $type,
            'percent' => $percent,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE)."\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }

    private function resolveCurrentDatabaseProfile(): string
    {
        $defaultConnection = (string) config('database.default', 'sqlite');
        return $defaultConnection === 'mysql_remote' ? 'remote' : 'sqlite';
    }
}
