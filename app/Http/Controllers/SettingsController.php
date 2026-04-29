<?php

namespace App\Http\Controllers;

use App\Http\Email\Email;
use App\Models\MailDeliveryFailure;
use App\Models\Settings;
use App\Support\DatabaseProfileManager;
use App\Support\DeployVersion;
use App\Support\GitHubDeployApi;
use Brian2694\Toastr\Facades\Toastr;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
                return view('settings.index', compact('user', 'settings'));
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
                    $validated = $request->validate([
                        'email_enabled' => ['required', 'in:0,1'],
                        'smtp_host' => ['nullable', 'string', 'max:255'],
                        'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                        'smtp_encryption' => ['nullable', 'in:tls,ssl,none'],
                        'smtp_username' => ['nullable', 'string', 'max:255'],
                        'smtp_password' => ['nullable', 'string', 'max:500'],
                        'mail_from_address' => ['nullable', 'email', 'max:255'],
                        'mail_from_name' => ['nullable', 'string', 'max:255'],
                    ]);

                    if ($validated['email_enabled'] === '1' && trim((string) ($validated['smtp_host'] ?? '')) === '') {
                        Toastr::error('Настройка почты', 'Укажите SMTP-сервер, если включена отправка писем.', ['progressBar' => true]);

                        return redirect('/settings/email')->withInput();
                    }

                    $settings->email_enabled = $validated['email_enabled'];
                    $settings->smtp_host = trim((string) ($validated['smtp_host'] ?? '')) ?: null;
                    $settings->smtp_port = $validated['smtp_port'] ?? null;
                    $enc = isset($validated['smtp_encryption']) ? trim((string) $validated['smtp_encryption']) : '';
                    $settings->smtp_encryption = $enc !== '' ? $enc : 'tls';
                    $settings->smtp_username = trim((string) ($validated['smtp_username'] ?? '')) ?: null;
                    if (! empty($validated['smtp_password'])) {
                        $settings->smtp_password = Crypt::encryptString($validated['smtp_password']);
                    }
                    $settings->mail_from_address = trim((string) ($validated['mail_from_address'] ?? '')) ?: null;
                    $settings->mail_from_name = trim((string) ($validated['mail_from_name'] ?? '')) ?: null;
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
                $mailFailureTree = $this->buildMailFailureTree();
                $mailFailureTotal = Schema::hasTable('mail_delivery_failures')
                    ? MailDeliveryFailure::query()->count()
                    : 0;

                return view('settings.email', compact('user', 'settings', 'mailFailureTree', 'mailFailureTotal'));
            }else{
                Toastr::error('Ошибка доступа', 'У Вас недостаточно прав для выполнения этого действия!', ["progressBar"=> true]);
                return redirect('/');
            }
        } else {
            return redirect('/');
        }
    }

    public function sendTestEmail(Request $request)
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
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $settings = Settings::where('id', 1)->first();
        if (! $settings) {
            Toastr::error('Настройки сайта не найдены.', 'Ошибка', ["progressBar"=> true]);
            return redirect('/settings/email')->withInput();
        }

        if ((int) $settings->email_enabled !== 1) {
            Toastr::error('Отправка почты отключена. Включите её в настройках и повторите попытку.', 'Тестовое сообщение', ["progressBar"=> true]);
            return redirect('/settings/email')->withInput();
        }

        if (trim((string) ($settings->smtp_host ?? '')) === '') {
            Toastr::error('Не указан SMTP host. Заполните SMTP-настройки и повторите попытку.', 'Тестовое сообщение', ["progressBar"=> true]);
            return redirect('/settings/email')->withInput();
        }

        $title = 'Тестовое сообщение SMTP';
        $body = 'Это тестовое письмо из настроек системы <b>'.e($settings->title).'</b>.<br>Если вы получили это письмо, SMTP настроен корректно.';

        $email = new Email();
        $email->send(
            $title,
            $body,
            (string) $validated['test_email'],
            (string) ($settings->mail_from_name ?: $settings->title),
            [
                'category' => MailDeliveryFailure::CATEGORY_SYSTEM,
                'mail_type' => 'settings_test',
                'recipient_name' => (string) ($user->fio ?? ''),
                'triggered_by_employee_id' => isset($user->id) ? (int) $user->id : null,
                'meta' => [
                    'source' => 'settings_email_test_button',
                ],
            ]
        );

        Toastr::success('Запрос на отправку тестового письма выполнен. Проверьте почту и, при необходимости, журнал ошибок доставки ниже.', 'Тестовое сообщение', ["progressBar"=> true]);

        return redirect('/settings/email');
    }

    private function streamHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ];
    }

    /**
     * Дерево: категория → получатель → список записей (новые сверху внутри категории).
     *
     * @return array<string, array{label: string, recipients: array<string, mixed>}>
     */
    private function buildMailFailureTree(): array
    {
        if (! Schema::hasTable('mail_delivery_failures')) {
            return [];
        }

        $rows = MailDeliveryFailure::query()
            ->orderByDesc('created_at')
            ->limit(400)
            ->get();

        $order = [
            MailDeliveryFailure::CATEGORY_AUTH,
            MailDeliveryFailure::CATEGORY_EMPLOYEES,
            MailDeliveryFailure::CATEGORY_INVENTORY,
            MailDeliveryFailure::CATEGORY_SYSTEM,
        ];

        $tree = [];
        foreach ($order as $category) {
            $tree[$category] = [
                'label' => MailDeliveryFailure::categoryLabel($category),
                'recipients' => [],
            ];
        }

        foreach ($rows as $row) {
            $category = $row->category;
            if (! isset($tree[$category])) {
                $tree[$category] = [
                    'label' => MailDeliveryFailure::categoryLabel($category),
                    'recipients' => [],
                ];
            }
            $recKey = (string) ($row->recipient_employee_id ?? '0').'|'.mb_strtolower((string) $row->recipient_email);
            if (! isset($tree[$category]['recipients'][$recKey])) {
                $display = $row->recipient_display;
                if ($display === null || $display === '') {
                    $display = $row->recipient_email !== ''
                        ? $row->recipient_email
                        : ($row->recipient_employee_id ? 'Сотрудник #'.$row->recipient_employee_id : 'Неизвестный получатель');
                }
                $tree[$category]['recipients'][$recKey] = [
                    'display' => $display,
                    'email' => $row->recipient_email,
                    'items' => collect(),
                ];
            }
            $tree[$category]['recipients'][$recKey]['items']->push($row);
        }

        $ordered = [];
        foreach ($order as $category) {
            if (isset($tree[$category]) && $tree[$category]['recipients'] !== []) {
                $ordered[$category] = $tree[$category];
            }
        }
        foreach ($tree as $category => $data) {
            if (! isset($ordered[$category]) && $data['recipients'] !== []) {
                $ordered[$category] = $data;
            }
        }

        return $ordered;
    }

    public function checkGitUpdates(Request $request)
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

        $result = $this->computeUpdateAvailability();

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'code' => $result['code'] ?? null,
                'message' => $result['message'] ?? 'Не удалось проверить обновления.',
            ], 422);
        }

        unset($result['ok']);

        return response()->json(array_merge(['ok' => true], $result, $this->gitReleaseJsonFragment()));
    }

    /**
     * @return array{app_release: ?string, app_release_source: 'env'|'version_file'|'deploy_json'|null}
     */
    private function gitReleaseJsonFragment(): array
    {
        $r = DeployVersion::resolveReleaseVersion(base_path());

        return [
            'app_release' => $r['version'],
            'app_release_source' => $r['source'],
        ];
    }

    public function pullGitUpdates(Request $request)
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

        $basePath = base_path();
        if (! DeployVersion::isGitWorkingTree($basePath)) {
            return response()->json([
                'ok' => false,
                'code' => 'pull_requires_git_clone',
                'message' => 'Автоматическое скачивание (git pull) из панели возможно только если проект на сервере развёрнут как git clone (есть каталог .git). '
                    .'При загрузке по FTP обновите файлы вручную и укажите commit в storage/app/deploy.json (поле ref), чтобы проверка через GitHub снова работала.',
            ], 422);
        }

        try {
            $stashFirst = filter_var($request->input('stash_first', false), FILTER_VALIDATE_BOOLEAN);
            $blockingLines = $this->getGitBlockingStatusLines($basePath);
            $didStash = false;

            if ($blockingLines !== [] && ! $stashFirst) {
                return response()->json([
                    'ok' => false,
                    'code' => 'working_tree_dirty',
                    'can_retry_with_stash' => true,
                    'message' => 'Есть незакоммиченные изменения в отслеживаемых файлах — без stash или коммита безопасный pull невозможен. Неотслеживаемые файлы (??) обычно не мешают.',
                    'dirty_lines' => array_values($blockingLines),
                ], 422);
            }

            if ($blockingLines !== [] && $stashFirst) {
                $this->runGitProcess(['git', 'stash', 'push', '-m', 'it-master panel pull '.gmdate('c')], $basePath);
                $didStash = true;
                $blockingLines = $this->getGitBlockingStatusLines($basePath);
                if ($blockingLines !== []) {
                    return response()->json([
                        'ok' => false,
                        'code' => 'stash_did_not_clear_tree',
                        'message' => 'После git stash остались изменения, которые мешают обновлению. Выполните git status по SSH и разберитесь вручную.',
                        'dirty_lines' => array_values($blockingLines),
                    ], 422);
                }
            }

            $check = $this->computeUpdateAvailability();
            if (! ($check['ok'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'message' => $check['message'] ?? 'Не удалось проверить обновления.',
                ], 422);
            }

            if (($check['has_updates'] ?? false) !== true) {
                return response()->json(array_merge([
                    'ok' => true,
                    'updated' => false,
                    'message' => $check['message'] ?? 'Обновлений нет. Репозиторий уже актуален.',
                ], $this->gitReleaseJsonFragment()));
            }

            $pullProcess = $this->runGitProcess(['git', 'pull', '--ff-only'], $basePath);

            $headSha = trim($this->runGitProcess(['git', 'rev-parse', 'HEAD'], $basePath)->getOutput());
            $persist = DeployVersion::tryPersistDeployRef($basePath, $headSha);

            $payload = [
                'ok' => true,
                'updated' => true,
                'message' => 'Обновления успешно скачаны и применены.',
                'output' => trim($pullProcess->getOutput()),
                'deploy_ref_saved' => $persist['saved'],
                'current_ref' => $persist['saved'] ? $persist['ref'] : ($headSha !== '' ? $headSha : null),
            ];
            if ($persist['saved']) {
                $payload['message'] .= ' Метка в storage/app/deploy.json обновлена на '.substr($persist['ref'], 0, 7).'.';
            } elseif ($persist['skipped_env']) {
                $payload['deploy_ref_note'] = 'deploy.json не меняли: задан DEPLOY_GIT_REF в .env (он важнее файла).';
            } else            if ($persist['error'] !== null) {
                $payload['deploy_ref_note'] = 'Не удалось обновить deploy.json: '.$persist['error'];
            }

            if ($didStash) {
                $pop = new Process(['git', 'stash', 'pop'], $basePath, null, null, 120);
                $pop->run();
                if (! $pop->isSuccessful()) {
                    $payload['stash_pop_warning'] = trim($pop->getErrorOutput()."\n".$pop->getOutput());
                    if ($payload['stash_pop_warning'] !== '') {
                        $payload['message'] .= ' После обновления git stash pop выдал предупреждение — проверьте слияние локальных правок.';
                    }
                }
            }

            return response()->json(array_merge($payload, $this->gitReleaseJsonFragment()));
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $this->formatGitOperationError('Ошибка при получении обновлений', $e),
            ], 422);
        }
    }

    public function saveDeployRef(Request $request)
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

        $resolved = DeployVersion::resolveLocalRef(base_path());
        if ($resolved['source'] === 'env') {
            return response()->json([
                'ok' => false,
                'code' => 'deploy_ref_env_overrides_file',
                'message' => 'Активна переменная DEPLOY_GIT_REF в .env — она важнее файла deploy.json. Измените или удалите её на сервере, затем при необходимости сохраните метку здесь.',
            ], 422);
        }

        $validated = $request->validate([
            'ref' => ['required', 'string', 'min:7', 'max:40', 'regex:/^[0-9a-fA-F]+$/'],
        ]);

        try {
            DeployVersion::writeDeployJson($validated['ref']);
        } catch (\JsonException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Не удалось сформировать deploy.json: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Метка версии сохранена в storage/app/deploy.json. Нажмите «Проверить обновления» ещё раз.',
            'ref' => strtolower(trim($validated['ref'])),
        ]);
    }

    /**
     * @return array{ok: bool, code?: string, message?: string, has_updates?: bool|null, behind_count?: int|null, can_pull?: bool, check_method?: string, ...}
     */
    private function computeUpdateAvailability(): array
    {
        $basePath = base_path();
        if (DeployVersion::isGitWorkingTree($basePath)) {
            return $this->computeUpdateAvailabilityViaLocalGit($basePath);
        }

        return $this->computeUpdateAvailabilityViaGitHub($basePath);
    }

    private function computeUpdateAvailabilityViaLocalGit(string $basePath): array
    {
        try {
            $this->runGitProcess(['git', 'fetch', '--prune'], $basePath);

            $upstreamBranch = $this->runGitProcess(
                ['git', 'rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{u}'],
                $basePath
            )->getOutput();
            $upstreamBranch = trim($upstreamBranch);
            if ($upstreamBranch === '') {
                throw new \RuntimeException('Для текущей ветки не найден upstream-репозиторий.');
            }

            $behindCountRaw = $this->runGitProcess(
                ['git', 'rev-list', '--count', "HEAD..{$upstreamBranch}"],
                $basePath
            )->getOutput();
            $behindCount = (int) trim($behindCountRaw);

            $headSha = trim($this->runGitProcess(['git', 'rev-parse', 'HEAD'], $basePath)->getOutput());

            $remoteVersionLabel = null;
            $updateChangelog = [];
            if ($behindCount > 0) {
                try {
                    $upstreamSha = trim($this->runGitProcess(['git', 'rev-parse', $upstreamBranch], $basePath)->getOutput());
                    if ($upstreamSha !== '') {
                        try {
                            $remoteVersionLabel = trim($this->runGitProcess(
                                ['git', 'describe', '--tags', '--always', $upstreamSha],
                                $basePath
                            )->getOutput());
                        } catch (\Throwable) {
                            $remoteVersionLabel = substr($upstreamSha, 0, 7);
                        }
                        if ($remoteVersionLabel === '') {
                            $remoteVersionLabel = substr($upstreamSha, 0, 7);
                        }
                    }
                    $logs = trim($this->runGitProcess(
                        ['git', 'log', '--format=%s', '-n', '40', 'HEAD..'.$upstreamBranch],
                        $basePath
                    )->getOutput());
                    if ($logs !== '') {
                        $updateChangelog = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $logs))));
                    }
                } catch (\Throwable) {
                }
            }

            $row = [
                'ok' => true,
                'has_updates' => $behindCount > 0,
                'behind_count' => $behindCount,
                'upstream' => $upstreamBranch,
                'local_ref' => $headSha !== '' ? $headSha : null,
                'local_ref_source' => 'git_head',
                'can_pull' => true,
                'check_method' => 'git',
                'message' => $behindCount > 0
                    ? "Найдены обновления: {$behindCount} коммит(ов)."
                    : 'Локальный репозиторий уже актуален.',
                'remote_version_label' => $remoteVersionLabel,
                'update_changelog' => $updateChangelog,
            ];

            if ($behindCount === 0 && $headSha !== '') {
                $persist = DeployVersion::tryPersistDeployRef($basePath, $headSha);
                $row['deploy_ref_saved'] = $persist['saved'];
                if ($persist['saved']) {
                    $row['deploy_ref_note'] = 'Метка в deploy.json синхронизирована с текущим HEAD ('.substr($persist['ref'], 0, 7).').';
                } elseif ($persist['skipped_env']) {
                    $row['deploy_ref_note'] = 'deploy.json не обновлялся: задан DEPLOY_GIT_REF в .env.';
                } elseif ($persist['error'] !== null) {
                    $row['deploy_ref_note'] = 'Не удалось записать deploy.json: '.$persist['error'];
                }
            }

            return $row;
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => $this->formatGitOperationError('Не удалось проверить обновления', $e),
            ];
        }
    }

    private function computeUpdateAvailabilityViaGitHub(string $basePath): array
    {
        $repoSpec = (string) config('deploy.github_repo', '');
        $branch = (string) config('deploy.github_branch', 'master');
        $token = config('deploy.github_token');
        $token = is_string($token) ? $token : null;

        $parsed = GitHubDeployApi::parseRepo($repoSpec);
        if ($parsed === null) {
            return [
                'ok' => false,
                'code' => 'invalid_repo_config',
                'message' => 'Укажите репозиторий в .env: DEPLOY_GITHUB_REPO=владелец/имя (например axobeasty/it2).',
            ];
        }

        $tip = GitHubDeployApi::branchTip($parsed['owner'], $parsed['repo'], $branch, $token);
        if ($tip === null) {
            return [
                'ok' => false,
                'code' => 'github_unreachable',
                'message' => 'Не удалось получить данные с GitHub (ветка «'.$branch.'», репозиторий «'.$repoSpec.'»). '
                    .'Проверьте имя репозитория и ветку. Для приватного репозитория задайте DEPLOY_GITHUB_TOKEN с правом read на код.',
            ];
        }

        $local = DeployVersion::resolveLocalRef($basePath);
        if ($local['ref'] === null) {
            return [
                'ok' => true,
                'has_updates' => null,
                'comparison_skipped' => true,
                'behind_count' => null,
                'local_ref' => null,
                'local_ref_source' => 'none',
                'remote_ref' => $tip['sha'],
                'remote_short' => $tip['short'],
                'remote_branch' => $branch,
                'remote_compare_url' => $tip['html_url'],
                'can_pull' => false,
                'check_method' => 'github_api',
                'message' => 'Чтобы сравнивать версии без git clone на сервере, зафиксируйте текущий commit после деплоя: '
                    .'создайте файл '.DeployVersion::deployJsonPath().' с содержимым вида {"ref":"<полный или короткий SHA>"} '
                    .'или задайте DEPLOY_GIT_REF в .env. Последний commit на '.$branch.': '.$tip['short'].'.',
            ];
        }

        $cmp = GitHubDeployApi::compare($parsed['owner'], $parsed['repo'], $local['ref'], $branch, $token);
        if ($cmp === null) {
            return [
                'ok' => false,
                'code' => 'github_compare_failed',
                'message' => 'Не удалось сравнить версии с GitHub. Проверьте, что ref в deploy.json или DEPLOY_GIT_REF — существующий commit в репозитории «'.$repoSpec.'».',
            ];
        }

        $hasUpdates = ($cmp['ahead_by'] > 0) || ($cmp['status'] === 'diverged');
        $msg = 'Код на сервере по метке деплоя совпадает с веткой '.$branch.'.';
        if ($hasUpdates) {
            if ($cmp['status'] === 'diverged') {
                $msg = 'Ветка '.$branch.' и метка на сервере разошлись (diverged). Обновляйте вручную или разберите расхождение. На GitHub впереди на '.$cmp['ahead_by'].' коммит(ов).';
            } else {
                $msg = 'Доступны обновления на GitHub: '.$cmp['ahead_by'].' коммит(ов) в ветке '.$branch.'.';
            }
        }

        $localRefOut = $local['ref'];
        $deployRefSaved = false;
        $deployRefNote = null;

        if (! $hasUpdates && $cmp['status'] === 'identical') {
            $persist = DeployVersion::tryPersistDeployRef($basePath, $tip['sha']);
            $deployRefSaved = $persist['saved'];
            if ($persist['saved']) {
                $localRefOut = $persist['ref'];
                $deployRefNote = 'Метка в deploy.json обновлена до commit с ветки '.$branch.' ('.$tip['short'].').';
                $msg .= ' '.$deployRefNote;
            } elseif ($persist['skipped_env']) {
                $deployRefNote = 'deploy.json не меняли: задан DEPLOY_GIT_REF в .env.';
            } elseif ($persist['error'] !== null) {
                $deployRefNote = 'Не удалось записать deploy.json: '.$persist['error'];
            }
        }

        $remoteVersionLabel = null;
        $updateChangelog = [];
        if ($hasUpdates) {
            $remoteVersionLabel = GitHubDeployApi::rawVersionFile($parsed['owner'], $parsed['repo'], $branch)
                ?? $tip['short'];
            $updateChangelog = $cmp['commit_subjects'] ?? [];
        }

        return [
            'ok' => true,
            'has_updates' => $hasUpdates,
            'behind_count' => $cmp['ahead_by'],
            'compare_status' => $cmp['status'],
            'local_ref' => $localRefOut,
            'local_ref_source' => $local['source'],
            'remote_ref' => $tip['sha'],
            'remote_short' => $tip['short'],
            'remote_branch' => $branch,
            'remote_compare_url' => $cmp['html_url'],
            'can_pull' => false,
            'check_method' => 'github_api',
            'message' => $msg,
            'deploy_ref_saved' => $deployRefSaved,
            'deploy_ref_note' => $deployRefNote,
            'remote_version_label' => $remoteVersionLabel,
            'update_changelog' => $updateChangelog,
        ];
    }

    private function formatGitOperationError(string $prefix, \Throwable $e): string
    {
        $msg = $e->getMessage();
        if (stripos($msg, 'not a git repository') !== false) {
            return $prefix.': на сервере нет Git-репозитория в каталоге приложения. '
                .'Нужен полноценный clone с папкой .git или обновляйте файлы другим способом.';
        }

        return $prefix.': '.$msg;
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

    private function runGitProcess(array $command, string $cwd): Process
    {
        $process = new Process($command, $cwd, null, null, 60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * Строки git status --porcelain, которые мешают pull (всё кроме чисто неотслеживаемых ??).
     *
     * @return list<string>
     */
    private function getGitBlockingStatusLines(string $basePath): array
    {
        $process = new Process(['git', 'status', '--porcelain'], $basePath, null, null, 30);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $raw = trim($process->getOutput());
        if ($raw === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $blocking = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_starts_with($line, '?? ')) {
                continue;
            }
            $blocking[] = $line;
        }

        return $blocking;
    }
}
