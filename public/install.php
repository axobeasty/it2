<?php

/**
 * Веб-установщик IT-Master (Laravel).
 * После успешной установки создаётся storage/framework/installer.lock.
 * На production удалите этот файл или ограничьте доступ.
 */

declare(strict_types=1);

session_start();

const INSTALLER_ROOT = __DIR__ . '/..';
const INSTALLER_LOCK = INSTALLER_ROOT . '/storage/framework/installer.lock';
const INSTALLER_ENV_EXAMPLE = INSTALLER_ROOT . '/.env.example';
const INSTALLER_ENV = INSTALLER_ROOT . '/.env';
const COMPOSER_INSTALLER_SHA384 = 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47';

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

function installer_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function installer_env_quote(string $value): string
{
    if ($value === '') {
        return '""';
    }
    if (preg_match('/^[A-Za-z0-9_.:@%\/\\\\-]+$/', $value)) {
        return $value;
    }

    return '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\"', '', ''], $value).'"';
}

function installer_php_binary(): string
{
    $php = PHP_BINARY;
    if ($php !== '' && @is_executable($php)) {
        return $php;
    }

    return 'php';
}

function installer_ensure_directories(): array
{
    $errors = [];
    $relative = [
        'storage/framework',
        'storage/framework/sessions',
        'storage/framework/cache',
        'storage/framework/cache/data',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
        'database',
    ];
    foreach ($relative as $rel) {
        $path = INSTALLER_ROOT.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_dir($path)) {
            continue;
        }
        if (! @mkdir($path, 0775, true) && ! is_dir($path)) {
            $errors[] = 'Не удалось создать каталог: '.$rel;
        }
    }

    return $errors;
}

function installer_proc(string $command, string $cwd): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($command, $descriptors, $pipes, $cwd);
    if (! is_resource($process)) {
        return ['code' => -1, 'output' => 'Не удалось запустить процесс. Проверьте proc_open и права.'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);

    $out = trim(($stdout ?? '')."\n".($stderr ?? ''));

    return ['code' => $code, 'output' => $out];
}

function installer_requirements(): array
{
    $checks = [];
    $coreOk = true;

    $minPhp = '8.2.0';
    $phpOk = version_compare(PHP_VERSION, $minPhp, '>=');
    $checks[] = ['label' => 'PHP >= '.$minPhp, 'ok' => $phpOk, 'detail' => 'Текущая версия: '.PHP_VERSION];
    $coreOk = $coreOk && $phpOk;

    $exts = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'];
    foreach ($exts as $ext) {
        $loaded = extension_loaded($ext);
        $checks[] = ['label' => 'Расширение '.$ext, 'ok' => $loaded, 'detail' => $loaded ? 'подключено' : 'не найдено'];
        $coreOk = $coreOk && $loaded;
    }

    $zip = extension_loaded('zip');
    $checks[] = [
        'label' => 'Расширение zip (для Composer)',
        'ok' => $zip,
        'detail' => $zip ? 'подключено' : 'без ext-zip composer install может завершиться ошибкой',
    ];

    $urlFopen = filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN);
    $checks[] = [
        'label' => 'allow_url_fopen (скачивание Composer)',
        'ok' => $urlFopen,
        'detail' => $urlFopen ? 'да' : 'для кнопки «Скачать composer.phar» включите в php.ini или загрузите composer.phar вручную в корень проекта',
    ];

    $intl = extension_loaded('intl');
    $checks[] = [
        'label' => 'Расширение intl (рекомендуется)',
        'ok' => true,
        'detail' => $intl ? 'подключено' : 'не найдено — при сбоях локали установите ext-intl',
    ];

    $pdoSqlite = extension_loaded('pdo_sqlite');
    $checks[] = ['label' => 'Расширение pdo_sqlite (для SQLite)', 'ok' => $pdoSqlite, 'detail' => $pdoSqlite ? 'есть' : 'нужно для встроенной БД'];
    $pdoMysql = extension_loaded('pdo_mysql');
    $checks[] = ['label' => 'Расширение pdo_mysql (для MySQL)', 'ok' => true, 'detail' => $pdoMysql ? 'есть' : 'нужно, если выберете MySQL'];

    $vendor = is_file(INSTALLER_ROOT.'/vendor/autoload.php');
    $checks[] = [
        'label' => 'Зависимости Composer (vendor/)',
        'ok' => $vendor,
        'detail' => $vendor ? 'найдено' : 'установите на шаге «Composer»',
    ];

    $envExample = is_readable(INSTALLER_ENV_EXAMPLE);
    $checks[] = ['label' => 'Файл .env.example', 'ok' => $envExample, 'detail' => $envExample ? 'читается' : 'отсутствует'];
    $coreOk = $coreOk && $envExample;

    $procOpen = function_exists('proc_open');
    $checks[] = ['label' => 'Функция proc_open', 'ok' => $procOpen, 'detail' => $procOpen ? 'доступна' : 'нужна для команд; включите в php.ini'];
    $coreOk = $coreOk && $procOpen;

    return [
        'checks' => $checks,
        'core_ok' => $coreOk,
        'vendor_ok' => $vendor,
    ];
}

function installer_build_env(array $in): string
{
    if (! is_readable(INSTALLER_ENV_EXAMPLE)) {
        throw new RuntimeException('Не найден .env.example');
    }

    $env = file_get_contents(INSTALLER_ENV_EXAMPLE);
    if ($env === false) {
        throw new RuntimeException('Не удалось прочитать .env.example');
    }

    $appName = (string) ($in['app_name'] ?? 'IT-Master');
    $appUrl = rtrim((string) ($in['app_url'] ?? 'http://localhost'), '/');
    $db = (string) ($in['db_connection'] ?? 'sqlite');

    $env = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME='.installer_env_quote($appName), $env);
    $env = preg_replace('/^APP_URL=.*$/m', 'APP_URL='.installer_env_quote($appUrl), $env);
    $env = preg_replace('/^APP_ENV=.*$/m', 'APP_ENV=local', $env);
    $env = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=true', $env);
    $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=', $env);

    $env = preg_replace('/^SESSION_DRIVER=.*$/m', 'SESSION_DRIVER=file', $env);
    $env = preg_replace('/^CACHE_STORE=.*$/m', 'CACHE_STORE=file', $env);
    $env = preg_replace('/^QUEUE_CONNECTION=.*$/m', 'QUEUE_CONNECTION=sync', $env);

    if ($db === 'mysql') {
        $env = preg_replace('/^DB_CONNECTION=.*$/m', 'DB_CONNECTION=mysql', $env);
        $host = (string) ($in['db_host'] ?? '127.0.0.1');
        $port = (string) ($in['db_port'] ?? '3306');
        $database = (string) ($in['db_database'] ?? 'laravel');
        $user = (string) ($in['db_username'] ?? 'root');
        $pass = (string) ($in['db_password'] ?? '');

        $env = preg_replace('/^# DB_HOST=.*$/m', 'DB_HOST='.installer_env_quote($host), $env);
        $env = preg_replace('/^# DB_PORT=.*$/m', 'DB_PORT='.installer_env_quote($port), $env);
        $env = preg_replace('/^# DB_DATABASE=.*$/m', 'DB_DATABASE='.installer_env_quote($database), $env);
        $env = preg_replace('/^# DB_USERNAME=.*$/m', 'DB_USERNAME='.installer_env_quote($user), $env);
        $env = preg_replace('/^# DB_PASSWORD=.*$/m', 'DB_PASSWORD='.installer_env_quote($pass), $env);
    } else {
        $env = preg_replace('/^DB_CONNECTION=.*$/m', 'DB_CONNECTION=sqlite', $env);
    }

    return $env;
}

function installer_run_artisan(string $arguments): array
{
    $root = INSTALLER_ROOT;
    $php = installer_php_binary();
    $artisan = $root.DIRECTORY_SEPARATOR.'artisan';
    $cmd = escapeshellarg($php).' '.escapeshellarg($artisan).' '.$arguments;

    return installer_proc($cmd, $root);
}

function installer_api_verify_token(): bool
{
    $t = (string) ($_POST['installer_token'] ?? '');

    return isset($_SESSION['installer_token']) && hash_equals($_SESSION['installer_token'], $t);
}

function installer_api_composer_phar(): array
{
    $php = installer_php_binary();
    $root = INSTALLER_ROOT;
    $steps = [];
    $log = [];

    $setupPath = $root.DIRECTORY_SEPARATOR.'composer-setup.php';

    $r1 = installer_proc(
        escapeshellarg($php).' -r '.escapeshellarg("copy('https://getcomposer.org/installer', 'composer-setup.php');"),
        $root
    );
    $steps[] = ['label' => 'Загрузка composer-setup.php', 'code' => $r1['code'], 'output' => $r1['output']];
    $log[] = '$ php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"'."\n".$r1['output'];
    if ($r1['code'] !== 0) {
        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    $verifyCode = "if (hash_file('sha384', 'composer-setup.php') === '".COMPOSER_INSTALLER_SHA384."') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }";
    $r2 = installer_proc(escapeshellarg($php).' -r '.escapeshellarg($verifyCode), $root);
    $steps[] = ['label' => 'Проверка SHA-384 установщика', 'code' => $r2['code'], 'output' => $r2['output']];
    $log[] = '$ php -r "hash_file(...composer-setup.php) === <ожидаемый хэш> ..."'."\n".$r2['output'];
    if ($r2['code'] !== 0) {
        if (is_file($setupPath)) {
            @unlink($setupPath);
        }

        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    $r3 = installer_proc(escapeshellarg($php).' '.escapeshellarg('composer-setup.php'), $root);
    $steps[] = ['label' => 'Запуск composer-setup.php', 'code' => $r3['code'], 'output' => $r3['output']];
    $log[] = '$ php composer-setup.php'."\n".$r3['output'];
    if ($r3['code'] !== 0) {
        if (is_file($setupPath)) {
            @unlink($setupPath);
        }

        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    $r4 = installer_proc(escapeshellarg($php).' -r '.escapeshellarg("unlink('composer-setup.php');"), $root);
    $steps[] = ['label' => 'Удаление composer-setup.php', 'code' => $r4['code'], 'output' => $r4['output']];
    $log[] = '$ php -r "unlink(\'composer-setup.php\');"'."\n".$r4['output'];

    $phar = $root.DIRECTORY_SEPARATOR.'composer.phar';
    $ok = is_file($phar);

    return [
        'ok' => $ok && $r4['code'] === 0,
        'steps' => $steps,
        'log' => implode("\n\n", $log),
        'composer_phar' => $ok,
    ];
}

function installer_api_composer_install(): array
{
    $root = INSTALLER_ROOT;
    $phar = $root.DIRECTORY_SEPARATOR.'composer.phar';
    if (! is_file($phar)) {
        return ['ok' => false, 'log' => 'Файл composer.phar не найден. Сначала скачайте Composer.', 'steps' => []];
    }

    $php = installer_php_binary();
    $cmd = escapeshellarg($php).' '.escapeshellarg('composer.phar').' install --no-interaction --prefer-dist';
    $r = installer_proc($cmd, $root);
    $log = '$ php composer.phar install --no-interaction --prefer-dist'."\n".$r['output'];
    $vendorOk = is_file($root.'/vendor/autoload.php');

    return [
        'ok' => $r['code'] === 0 && $vendorOk,
        'steps' => [['label' => 'composer install', 'code' => $r['code'], 'output' => $r['output']]],
        'log' => $log,
        'vendor_ok' => $vendorOk,
    ];
}

function installer_api_finalize(array $data): array
{
    $dbConn = ($data['db_connection'] ?? '') === 'mysql' ? 'mysql' : 'sqlite';

    if (($data['app_name'] ?? '') === '' || ($data['app_url'] ?? '') === '') {
        return ['ok' => false, 'log' => 'Укажите название приложения и URL.', 'steps' => []];
    }

    if (! is_file(INSTALLER_ROOT.'/vendor/autoload.php')) {
        return ['ok' => false, 'log' => 'Нет vendor/. Сначала выполните «Установить зависимости».', 'steps' => []];
    }

    if ($dbConn === 'mysql') {
        if (($data['db_database'] ?? '') === '' || ($data['db_username'] ?? '') === '') {
            return ['ok' => false, 'log' => 'Для MySQL укажите имя БД и пользователя.', 'steps' => []];
        }
        if (! extension_loaded('pdo_mysql')) {
            return ['ok' => false, 'log' => 'Расширение pdo_mysql не установлено.', 'steps' => []];
        }
    } else {
        if (! extension_loaded('pdo_sqlite')) {
            return ['ok' => false, 'log' => 'Расширение pdo_sqlite не установлено.', 'steps' => []];
        }
    }

    try {
        $envContent = installer_build_env(array_merge($data, ['db_connection' => $dbConn]));
    } catch (Throwable $e) {
        return ['ok' => false, 'log' => $e->getMessage(), 'steps' => []];
    }

    if (@file_put_contents(INSTALLER_ENV, $envContent) === false) {
        return ['ok' => false, 'log' => 'Не удалось записать .env', 'steps' => []];
    }

    if ($dbConn === 'sqlite') {
        $sqlitePath = INSTALLER_ROOT.'/database/database.sqlite';
        if (! is_file($sqlitePath)) {
            touch($sqlitePath);
        }
    }

    $steps = [];
    $logParts = [];
    $failed = false;

    foreach (['key:generate --force', 'migrate --force', 'db:seed --force'] as $artisanArgs) {
        $r = installer_run_artisan($artisanArgs);
        $steps[] = ['label' => 'artisan '.$artisanArgs, 'code' => $r['code'], 'output' => $r['output']];
        $logParts[] = '$ php artisan '.$artisanArgs."\n".$r['output'];
        if ($r['code'] !== 0) {
            $failed = true;
            break;
        }
    }

    if (! $failed) {
        $link = installer_run_artisan('storage:link');
        $steps[] = ['label' => 'artisan storage:link', 'code' => $link['code'], 'output' => $link['output']];
        $logParts[] = '$ php artisan storage:link'."\n".$link['output'];
        if ($link['code'] !== 0) {
            $failed = true;
        }
    }

    if ($failed) {
        return ['ok' => false, 'log' => implode("\n\n", $logParts), 'steps' => $steps];
    }

    $lockBody = "installed_at=".gmdate('c')."\n";
    if (@file_put_contents(INSTALLER_LOCK, $lockBody) === false) {
        return [
            'ok' => false,
            'log' => implode("\n\n", $logParts)."\n\nНе удалось записать installer.lock",
            'steps' => $steps,
        ];
    }

    return ['ok' => true, 'log' => implode("\n\n", $logParts), 'steps' => $steps];
}

// -------------------------------------------------------------------------
// Routing: уже установлено
// -------------------------------------------------------------------------

if (is_file(INSTALLER_LOCK)) {
    header('Content-Type: text/html; charset=utf-8');
    $bootstrapCss = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css';
    $iconsCss = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css';
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Уже установлено</title>';
    echo '<link href="'.installer_h($bootstrapCss).'" rel="stylesheet"><link href="'.installer_h($iconsCss).'" rel="stylesheet">';
    echo '<style>body{background:#f5f7fb;font-family:\'Segoe UI\',sans-serif;min-height:100vh}.panel{max-width:40rem;margin:2rem auto;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);padding:1.5rem}</style>';
    echo '</head><body class="p-3"><div class="panel">';
    echo '<h1 class="h4 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Установка уже выполнена</h1>';
    echo '<p>Файл <code>storage/framework/installer.lock</code> найден. Откройте <a href="/">главную страницу</a>.</p>';
    echo '<p class="text-muted small mb-0">Чтобы снова запустить мастер, удалите <code>installer.lock</code> (и при необходимости <code>public/install.php</code> после работы).</p>';
    echo '</div></body></html>';
    exit;
}

// -------------------------------------------------------------------------
// API (JSON)
// -------------------------------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['api_action'])
    && isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0
) {
    set_time_limit(900);
    ini_set('max_execution_time', '900');

    header('Content-Type: application/json; charset=utf-8');

    if (! installer_api_verify_token()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Неверный токен сессии. Обновите страницу.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = (string) $_POST['api_action'];

    try {
        if ($action === 'composer_phar') {
            $payload = installer_api_composer_phar();
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'composer_install') {
            $payload = installer_api_composer_install();
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'finalize') {
            $data = [
                'app_name' => trim((string) ($_POST['app_name'] ?? '')),
                'app_url' => trim((string) ($_POST['app_url'] ?? '')),
                'db_connection' => $_POST['db_connection'] === 'mysql' ? 'mysql' : 'sqlite',
                'db_host' => trim((string) ($_POST['db_host'] ?? '')),
                'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
                'db_database' => trim((string) ($_POST['db_database'] ?? '')),
                'db_username' => trim((string) ($_POST['db_username'] ?? '')),
                'db_password' => (string) ($_POST['db_password'] ?? ''),
            ];
            $payload = installer_api_finalize($data);
            if ($payload['ok']) {
                $_SESSION['installer_token'] = bin2hex(random_bytes(16));
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'log' => $e->getMessage(), 'steps' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// HTML UI
// -------------------------------------------------------------------------

$dirErrors = installer_ensure_directories();
$req = installer_requirements();

$_SESSION['installer_token'] = $_SESSION['installer_token'] ?? bin2hex(random_bytes(16));
$token = $_SESSION['installer_token'];

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
if ($step < 1 || $step > 3) {
    $step = 1;
}

$bootstrapCss = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css';
$bootstrapJs = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js';
$iconsCss = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css';

$hasComposerPhar = is_file(INSTALLER_ROOT.'/composer.phar');
$defaultUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка IT-Master</title>
    <link href="<?= installer_h($bootstrapCss) ?>" rel="stylesheet" crossorigin="anonymous">
    <link href="<?= installer_h($iconsCss) ?>" rel="stylesheet">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; margin: 0; }
        .installer-shell { max-width: 48rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        .header-title { font-weight: 600; color: #000; font-size: 1.5rem; }
        .notification-panel {
            border-radius: 12px; background: #ffffff; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            padding: 1.5rem; margin-bottom: 1rem;
        }
        .profile-tabs { display: flex; gap: 12px; background: white; border-radius: 12px; flex-wrap: wrap; margin-bottom: 1.25rem; }
        .profile-tab {
            flex: 1; text-align: center; padding: 10px 14px; border-radius: 10px; font-weight: 500; color: #000;
            background: white; font-size: 0.92rem; min-width: 90px; border: none; text-decoration: none;
            transition: all 0.3s ease;
        }
        .profile-tab.active { background: #d9e1ef; font-weight: 600; }
        .profile-tab:not(.active):hover { background: #f8f9fa; color: #000; }
        .profile-tab:disabled { opacity: 0.45; pointer-events: none; }
        .section-title {
            font-size: 1.15rem; font-weight: 600; color: #333; margin-bottom: 1rem;
            border-bottom: 2px solid #0d6efd; padding-bottom: 0.5rem; display: inline-block;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7); border: none; color: white !important;
            padding: 8px 18px; transition: all 0.3s;
        }
        .btn-gradient:hover { filter: brightness(1.05); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25); color: white !important; }
        .btn-gradient:disabled { opacity: 0.55; transform: none; }
        .console-panel {
            background: #1e1e1e; color: #d4d4d4; font-family: Consolas, 'Courier New', monospace;
            font-size: 0.8rem; border-radius: 10px; padding: 1rem; max-height: 320px; overflow: auto;
            white-space: pre-wrap; word-break: break-word; min-height: 120px;
        }
        .console-panel:empty::before { content: 'Вывод команд появится здесь…'; color: #6e6e6e; }
        .progress { height: 10px; border-radius: 8px; }
        .progress-bar { transition: width 0.35s ease; }
        table.install-checks { font-size: 0.9rem; }
        table.install-checks td { vertical-align: top; }
    </style>
</head>
<body>
<div class="installer-shell">
    <h1 class="header-title mb-1"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Установка IT-Master</h1>
    <p class="text-muted mb-4">Мастер поможет поставить Composer, зависимости, создать <code>.env</code> и подготовить базу.</p>

    <div class="profile-tabs" role="tablist">
        <a class="profile-tab <?= $step === 1 ? 'active' : '' ?>" href="?step=1">1. Проверка</a>
        <a class="profile-tab <?= $step === 2 ? 'active' : '' ?>" href="?step=2" <?= ! $req['core_ok'] || $dirErrors !== [] ? 'tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>2. Composer</a>
        <a class="profile-tab <?= $step === 3 ? 'active' : '' ?>" href="?step=3" <?= ! $req['core_ok'] || $dirErrors !== [] ? 'tabindex="-1" style="opacity:.5;pointer-events:none"' : '' ?>>3. Настройка</a>
    </div>

    <?php if ($dirErrors !== []): ?>
        <div class="notification-panel border border-danger border-opacity-25">
            <div class="section-title text-danger">Каталоги</div>
            <ul class="mb-0"><?php foreach ($dirErrors as $e) {
                echo '<li>'.installer_h($e).'</li>';
            } ?></ul>
        </div>
    <?php endif; ?>

    <div class="mb-3 d-none" id="global-progress-wrap">
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span id="global-progress-label">Выполняется…</span>
            <span id="global-progress-pct">0%</span>
        </div>
        <div class="progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="global-progress-bar" role="progressbar" style="width: 0%"></div>
        </div>
    </div>

    <div class="notification-panel">
        <div class="section-title">Консоль</div>
        <div class="console-panel" id="installer-console"></div>
    </div>

    <?php if ($step === 1): ?>
        <div class="notification-panel">
            <div class="section-title">Требования</div>
            <div class="table-responsive">
                <table class="table install-checks mb-0">
                    <tbody>
                    <?php foreach ($req['checks'] as $c): ?>
                        <tr>
                            <td><?= installer_h($c['label']) ?></td>
                            <td>
                                <?php if ($c['ok']): ?>
                                    <span class="badge text-bg-success">Ок</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Нет</span>
                                <?php endif; ?>
                                <div class="text-muted small"><?= installer_h($c['detail']) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (! $req['core_ok']): ?>
                <p class="text-danger small mt-3 mb-0">Устраните проблемы и обновите страницу. Обязательны: PHP 8.2+, основные расширения, <code>proc_open</code>, читаемый <code>.env.example</code>.</p>
            <?php endif; ?>
            <div class="mt-3">
                <?php if ($req['core_ok'] && $dirErrors === []): ?>
                    <a class="btn btn-gradient" href="?step=2">Далее: Composer <i class="bi bi-arrow-right ms-1"></i></a>
                <?php else: ?>
                    <button class="btn btn-gradient" type="button" disabled>Далее: Composer</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($step === 2): ?>
        <div class="notification-panel">
            <?php if (! $req['core_ok'] || $dirErrors !== []): ?>
                <p class="text-danger">Сначала пройдите шаг 1.</p>
                <a class="btn btn-secondary" href="?step=1"><i class="bi bi-arrow-left"></i> Назад</a>
            <?php else: ?>
                <div class="section-title">Composer</div>
                <p class="text-muted small">Скачивание выполняется командами с официального сайта и проверкой SHA-384 (как в документации Composer).</p>
                <p class="small mb-2">Ожидаемый хэш установщика зафиксирован в <code>install.php</code> (константа). Если установщик обновился на getcomposer.org, хэш нужно обновить вручную.</p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-gradient" id="btn-composer-phar">
                        <i class="bi bi-download me-1"></i> Скачать composer.phar
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-composer-install" <?= ! $hasComposerPhar ? 'disabled' : '' ?>>
                        <i class="bi bi-box-seam me-1"></i> Установить зависимости (composer install)
                    </button>
                </div>

                <p class="small mb-0">
                    <?php if ($req['vendor_ok']): ?>
                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Каталог <code>vendor/</code> найден — можно переходить к настройке.</span>
                    <?php else: ?>
                        <span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>После успешного <code>composer install</code> откроется шаг 3.</span>
                    <?php endif; ?>
                </p>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a class="btn btn-secondary" href="?step=1"><i class="bi bi-arrow-left"></i> Назад</a>
                    <a class="btn btn-gradient <?= $req['vendor_ok'] ? '' : 'disabled' ?>" href="<?= $req['vendor_ok'] ? '?step=3' : '#' ?>" id="link-step3" <?= $req['vendor_ok'] ? '' : 'aria-disabled="true" onclick="return false;"' ?>>Далее: настройка <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($step === 3): ?>
        <div class="notification-panel">
            <?php if (! $req['vendor_ok'] || ! $req['core_ok'] || $dirErrors !== []): ?>
                <p class="text-danger">Нужны выполненные шаги 1–2 и каталог <code>vendor/</code>.</p>
                <a class="btn btn-secondary" href="?step=2">К Composer</a>
            <?php else: ?>
                <div class="section-title">Параметры приложения</div>
                <form id="form-finalize" class="row g-3">
                    <input type="hidden" name="installer_token" value="<?= installer_h($token) ?>">

                    <div class="col-12">
                        <label class="form-label" for="app_name">Название (APP_NAME)</label>
                        <input class="form-control" id="app_name" name="app_name" required value="<?= installer_h($_POST['app_name'] ?? 'IT-Master') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="app_url">URL сайта (APP_URL)</label>
                        <input class="form-control" id="app_url" name="app_url" required placeholder="http://localhost:8000" value="<?= installer_h($_POST['app_url'] ?? $defaultUrl) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="db_connection">База данных</label>
                        <select class="form-select" id="db_connection" name="db_connection">
                            <option value="sqlite" selected>SQLite</option>
                            <option value="mysql">MySQL / MariaDB</option>
                        </select>
                    </div>
                    <div id="mysql-fields" class="col-12" style="display:none">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="db_host">Хост</label>
                                <input class="form-control" id="db_host" name="db_host" value="127.0.0.1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="db_port">Порт</label>
                                <input class="form-control" id="db_port" name="db_port" value="3306">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="db_database">Имя базы</label>
                                <input class="form-control" id="db_database" name="db_database" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="db_username">Пользователь</label>
                                <input class="form-control" id="db_username" name="db_username" value="root">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="db_password">Пароль</label>
                                <input class="form-control" id="db_password" name="db_password" type="password" value="">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <p class="small text-muted mb-0">Будут выполнены: <code>php artisan key:generate</code>, <code>migrate</code>, <code>db:seed</code>, <code>storage:link</code>.</p>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <a class="btn btn-secondary" href="?step=2"><i class="bi bi-arrow-left"></i> Назад</a>
                        <button type="submit" class="btn btn-gradient" id="btn-finalize">
                            <i class="bi bi-lightning-charge me-1"></i> Завершить установку
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script src="<?= installer_h($bootstrapJs) ?>" crossorigin="anonymous"></script>
<script>
(function () {
    var token = <?= json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var consoleEl = document.getElementById('installer-console');
    var progressWrap = document.getElementById('global-progress-wrap');
    var progressBar = document.getElementById('global-progress-bar');
    var progressPct = document.getElementById('global-progress-pct');
    var progressLabel = document.getElementById('global-progress-label');

    function appendConsole(text) {
        if (!consoleEl) return;
        consoleEl.textContent += (consoleEl.textContent ? '\n' : '') + text;
        consoleEl.scrollTop = consoleEl.scrollHeight;
    }

    function setProgress(p, label) {
        p = Math.max(0, Math.min(100, p));
        if (progressWrap) progressWrap.classList.remove('d-none');
        if (progressBar) progressBar.style.width = p + '%';
        if (progressPct) progressPct.textContent = Math.round(p) + '%';
        if (progressLabel && label) progressLabel.textContent = label;
    }

    function hideProgressStripes() {
        if (progressBar) {
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        }
    }

    function fakeProgressWhile(task, label, maxBeforeDone) {
        maxBeforeDone = maxBeforeDone || 88;
        var p = 5;
        setProgress(p, label);
        var id = setInterval(function () {
            p = Math.min(p + Math.random() * 12, maxBeforeDone);
            setProgress(p, label);
        }, 450);
        return task.finally(function () {
            clearInterval(id);
        });
    }

    function postApi(apiAction, fields) {
        var body = new URLSearchParams();
        body.set('api_action', apiAction);
        body.set('installer_token', token);
        if (fields) {
            fields.forEach(function (pair) {
                body.set(pair[0], pair[1]);
            });
        }
        return fetch('install.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        }).then(function (r) {
            return r.text().then(function (t) {
                try {
                    return JSON.parse(t);
                } catch (e) {
                    throw new Error(t.slice(0, 800) || ('HTTP ' + r.status));
                }
            });
        });
    }

    var btnPhar = document.getElementById('btn-composer-phar');
    if (btnPhar) {
        btnPhar.addEventListener('click', function () {
            btnPhar.disabled = true;
            appendConsole('--- Скачивание composer.phar ---');
            fakeProgressWhile(
                postApi('composer_phar').then(function (data) {
                    appendConsole(data.log || JSON.stringify(data));
                    setProgress(100, data.ok ? 'Готово' : 'Ошибка');
                    hideProgressStripes();
                    if (data.ok) {
                        var btnInst = document.getElementById('btn-composer-install');
                        if (btnInst) btnInst.disabled = false;
                    }
                }).catch(function (e) {
                    appendConsole('Ошибка сети: ' + e);
                    setProgress(100, 'Ошибка');
                    hideProgressStripes();
                }),
                'Загрузка и установка Composer…'
            ).finally(function () { btnPhar.disabled = false; });
        });
    }

    var btnCi = document.getElementById('btn-composer-install');
    if (btnCi) {
        btnCi.addEventListener('click', function () {
            btnCi.disabled = true;
            appendConsole('\n--- composer install ---');
            fakeProgressWhile(
                postApi('composer_install').then(function (data) {
                    appendConsole(data.log || JSON.stringify(data));
                    setProgress(100, data.ok ? 'Зависимости установлены' : 'Ошибка');
                    hideProgressStripes();
                    if (data.ok && data.vendor_ok) {
                        var a = document.getElementById('link-step3');
                        if (a) {
                            a.classList.remove('disabled');
                            a.setAttribute('href', '?step=3');
                            a.removeAttribute('aria-disabled');
                            a.onclick = null;
                        }
                    }
                }).catch(function (e) {
                    appendConsole('Ошибка: ' + e);
                    setProgress(100, 'Ошибка');
                    hideProgressStripes();
                }),
                'composer install (может занять несколько минут)…',
                92
            ).finally(function () { btnCi.disabled = false; });
        });
    }

    var dbSel = document.getElementById('db_connection');
    var mysqlBox = document.getElementById('mysql-fields');
    if (dbSel && mysqlBox) {
        function syncDb() {
            mysqlBox.style.display = dbSel.value === 'mysql' ? 'block' : 'none';
        }
        dbSel.addEventListener('change', syncDb);
        syncDb();
    }

    var formFin = document.getElementById('form-finalize');
    if (formFin) {
        formFin.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var btn = document.getElementById('btn-finalize');
            if (btn) btn.disabled = true;
            appendConsole('\n--- Завершение установки (artisan) ---');
            var fd = new FormData(formFin);
            var pairs = [];
            fd.forEach(function (v, k) { pairs.push([k, v]); });

            fakeProgressWhile(
                postApi('finalize', pairs).then(function (data) {
                    appendConsole(data.log || JSON.stringify(data));
                    setProgress(100, data.ok ? 'Установка завершена' : 'Ошибка');
                    hideProgressStripes();
                    if (data.ok) {
                        var wrap = document.createElement('div');
                        wrap.className = 'alert alert-success mt-3';
                        wrap.innerHTML = '<strong>Готово.</strong> Перейдите на <a href="/" class="alert-link">главную</a>. Демо-вход: <code>test</code> / <code>123</code>. Удалите или защитите <code>public/install.php</code> на production.';
                        formFin.appendChild(wrap);
                    }
                }).catch(function (e) {
                    appendConsole('Ошибка: ' + e);
                    setProgress(100, 'Ошибка');
                    hideProgressStripes();
                }),
                'Миграции и сиды…',
                90
            ).finally(function () { if (btn) btn.disabled = false; });
        });
    }
})();
</script>
</body>
</html>
