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

header('Content-Type: text/html; charset=utf-8');

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

function installer_requirements(): array
{
    $checks = [];
    $ok = true;

    $minPhp = '8.2.0';
    $phpOk = version_compare(PHP_VERSION, $minPhp, '>=');
    $checks[] = ['label' => 'PHP >= '.$minPhp, 'ok' => $phpOk, 'detail' => 'Текущая версия: '.PHP_VERSION];
    $ok = $ok && $phpOk;

    $exts = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'];
    foreach ($exts as $ext) {
        $loaded = extension_loaded($ext);
        $checks[] = ['label' => 'Расширение '.$ext, 'ok' => $loaded, 'detail' => $loaded ? 'подключено' : 'не найдено'];
        $ok = $ok && $loaded;
    }

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
    $checks[] = ['label' => 'Зависимости Composer (vendor/)', 'ok' => $vendor, 'detail' => $vendor ? 'найдено' : 'выполните: composer install'];
    $ok = $ok && $vendor;

    $envExample = is_readable(INSTALLER_ENV_EXAMPLE);
    $checks[] = ['label' => 'Файл .env.example', 'ok' => $envExample, 'detail' => $envExample ? 'читается' : 'отсутствует'];
    $ok = $ok && $envExample;

    $procOpen = function_exists('proc_open');
    $checks[] = ['label' => 'Функция proc_open', 'ok' => $procOpen, 'detail' => $procOpen ? 'доступна' : 'нужна для artisan; включите в php.ini'];
    $ok = $ok && $procOpen;

    return ['checks' => $checks, 'ok' => $ok];
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
    $php = PHP_BINARY;
    if ($php === '' || ! is_executable($php)) {
        $php = 'php';
    }

    $artisan = $root.DIRECTORY_SEPARATOR.'artisan';
    $cmd = escapeshellarg($php).' '.escapeshellarg($artisan).' '.$arguments;

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($cmd, $descriptors, $pipes, $root);
    if (! is_resource($process)) {
        return ['code' => -1, 'output' => 'Не удалось запустить процесс artisan.'];
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);

    $out = trim($stdout."\n".$stderr);

    return ['code' => $code, 'output' => $out];
}

// -------------------------------------------------------------------------
// Routing
// -------------------------------------------------------------------------

if (is_file(INSTALLER_LOCK)) {
    http_response_code(200);
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Уже установлено</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:40rem;margin:2rem auto;padding:0 1rem;line-height:1.5} a{color:#0d6efd}</style></head><body>';
    echo '<h1>Установка уже выполнена</h1>';
    echo '<p>Файл-маркер <code>storage/framework/installer.lock</code> найден. Откройте <a href="/">главную страницу</a>.</p>';
    echo '<p>Чтобы снова запустить мастер (осторожно: можно перезаписать .env), удалите <code>installer.lock</code> и при необходимости этот файл <code>public/install.php</code> после работы.</p>';
    echo '</body></html>';
    exit;
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
if ($step < 1 || $step > 2) {
    $step = 1;
}

$dirErrors = installer_ensure_directories();
$req = installer_requirements();

// POST install
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['installer_action']) && $_POST['installer_action'] === 'install') {
    if (empty($_SESSION['installer_token']) || ! hash_equals($_SESSION['installer_token'], (string) ($_POST['installer_token'] ?? ''))) {
        http_response_code(403);
        echo 'Неверный токен сессии. Обновите страницу и попробуйте снова.';
        exit;
    }

    if ($dirErrors !== [] || ! $req['ok']) {
        http_response_code(400);
        echo 'Требования не выполнены. Вернитесь на шаг 1.';
        exit;
    }

    $dbConn = $_POST['db_connection'] === 'mysql' ? 'mysql' : 'sqlite';
    $data = [
        'app_name' => trim((string) ($_POST['app_name'] ?? '')),
        'app_url' => trim((string) ($_POST['app_url'] ?? '')),
        'db_connection' => $dbConn,
        'db_host' => trim((string) ($_POST['db_host'] ?? '')),
        'db_port' => trim((string) ($_POST['db_port'] ?? '3306')),
        'db_database' => trim((string) ($_POST['db_database'] ?? '')),
        'db_username' => trim((string) ($_POST['db_username'] ?? '')),
        'db_password' => (string) ($_POST['db_password'] ?? ''),
    ];

    if ($data['app_name'] === '' || $data['app_url'] === '') {
        http_response_code(400);
        echo 'Укажите название приложения и URL.';
        exit;
    }

    if ($dbConn === 'mysql') {
        if ($data['db_database'] === '' || $data['db_username'] === '') {
            http_response_code(400);
            echo 'Для MySQL укажите имя БД и пользователя.';
            exit;
        }
        if (! extension_loaded('pdo_mysql')) {
            http_response_code(400);
            echo 'Расширение pdo_mysql не установлено.';
            exit;
        }
    } else {
        if (! extension_loaded('pdo_sqlite')) {
            http_response_code(400);
            echo 'Расширение pdo_sqlite не установлено.';
            exit;
        }
    }

    try {
        $envContent = installer_build_env($data);
    } catch (Throwable $e) {
        http_response_code(500);
        echo installer_h($e->getMessage());
        exit;
    }

    if (@file_put_contents(INSTALLER_ENV, $envContent) === false) {
        http_response_code(500);
        echo 'Не удалось записать файл .env — проверьте права на каталог проекта.';
        exit;
    }

    if ($dbConn === 'sqlite') {
        $sqlitePath = INSTALLER_ROOT.'/database/database.sqlite';
        if (! is_file($sqlitePath)) {
            touch($sqlitePath);
        }
    }

    $log = [];
    $failed = false;

    foreach (['key:generate --force', 'migrate --force', 'db:seed --force'] as $artisanArgs) {
        $r = installer_run_artisan($artisanArgs);
        $log[] = '$ php artisan '.$artisanArgs."\n".$r['output'];
        if ($r['code'] !== 0) {
            $failed = true;
            break;
        }
    }

    if (! $failed) {
        $link = installer_run_artisan('storage:link');
        $log[] = '$ php artisan storage:link'."\n".$link['output'];
    }

    if ($failed) {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Ошибка установки</title>';
        echo '<style>body{font-family:monospace;max-width:52rem;margin:2rem auto;padding:0 1rem;white-space:pre-wrap;background:#1a1a1a;color:#eee}</style></head><body>';
        echo "<h1 style=\"font-family:system-ui\">Ошибка при выполнении artisan</h1>\n";
        echo installer_h(implode("\n\n", $log));
        echo "\n\nИсправьте проблему, удалите частично созданный .env при необходимости и обновите страницу.";
        echo '</body></html>';
        exit;
    }

    $lockBody = "installed_at=".gmdate('c')."\n";
    if (@file_put_contents(INSTALLER_LOCK, $lockBody) === false) {
        http_response_code(500);
        echo 'Установка прошла, но не удалось записать installer.lock. Создайте вручную файл storage/framework/installer.lock';
        exit;
    }

    $_SESSION['installer_token'] = null;

    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="utf-8"><title>Готово</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:40rem;margin:2rem auto;padding:0 1rem;line-height:1.5} code{background:#f4f4f4;padding:2px 6px;border-radius:4px} .warn{background:#fff3cd;padding:1rem;border-radius:8px;margin:1rem 0} pre{overflow:auto;background:#f8f9fa;padding:1rem;border-radius:8px;font-size:.85rem}</style></head><body>';
    echo '<h1>Установка завершена</h1>';
    echo '<p>Сайт готов. Перейдите на <a href="/">главную страницу</a>.</p>';
    echo '<div class="warn"><strong>Безопасность:</strong> удалите или защитите файл <code>public/install.php</code> на сервере.</div>';
    echo '<p>Демо-вход после сидера (см. <code>database/seeders/TestUser.php</code>): логин <code>test</code>, пароль <code>123</code>.</p>';
    echo '<details><summary>Журнал команд</summary><pre>'.installer_h(implode("\n\n", $log)).'</pre></details>';
    echo '</body></html>';
    exit;
}

$_SESSION['installer_token'] = $_SESSION['installer_token'] ?? bin2hex(random_bytes(16));
$token = $_SESSION['installer_token'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка IT-Master</title>
    <style>
        :root { --bg: #f5f7fb; --card: #fff; --accent: #0d6efd; --text: #212529; --muted: #6c757d; --danger: #dc3545; --ok: #198754; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); margin: 0; line-height: 1.5; }
        .wrap { max-width: 42rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        .sub { color: var(--muted); margin: 0 0 1.5rem; font-size: .95rem; }
        .card { background: var(--card); border-radius: 12px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 16px rgba(0,0,0,.06); margin-bottom: 1rem; }
        .steps { display: flex; gap: .5rem; margin-bottom: 1.25rem; font-size: .9rem; }
        .steps span { padding: .35rem .75rem; border-radius: 8px; background: #e9ecef; color: var(--muted); }
        .steps span.on { background: #d9e1ef; color: var(--text); font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        td { padding: .5rem 0; border-bottom: 1px solid #eee; vertical-align: top; }
        td:first-child { width: 55%; }
        .badge { display: inline-block; padding: .15rem .5rem; border-radius: 6px; font-size: .75rem; font-weight: 600; }
        .badge.ok { background: #d1e7dd; color: var(--ok); }
        .badge.bad { background: #f8d7da; color: var(--danger); }
        label { display: block; font-weight: 500; margin: .75rem 0 .25rem; font-size: .9rem; }
        input, select { width: 100%; padding: .5rem .65rem; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; }
        .row { display: grid; gap: .75rem; }
        @media (min-width: 520px) { .row-2 { grid-template-columns: 1fr 1fr; } }
        .btn { display: inline-block; margin-top: 1rem; padding: .55rem 1.25rem; background: var(--accent); color: #fff; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; text-decoration: none; font-weight: 500; }
        .btn:hover { filter: brightness(1.05); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .muted { color: var(--muted); font-size: .85rem; margin-top: .25rem; }
        .err { color: var(--danger); font-size: .9rem; margin: .5rem 0 0; }
        code { background: #f1f3f5; padding: .1rem .35rem; border-radius: 4px; font-size: .88em; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Установка IT-Master</h1>
    <p class="sub">Мастер создаст <code>.env</code>, применит миграции и заполнит базу начальными данными (включая демо-пользователя).</p>

    <div class="steps">
        <span class="<?= $step === 1 ? 'on' : '' ?>">1. Проверка</span>
        <span class="<?= $step === 2 ? 'on' : '' ?>">2. Настройка</span>
    </div>

    <?php if ($dirErrors !== []): ?>
        <div class="card">
            <p class="err"><strong>Каталоги:</strong></p>
            <ul><?php foreach ($dirErrors as $e) { echo '<li>'.installer_h($e).'</li>'; } ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <div class="card">
            <h2 style="font-size:1.1rem;margin:0 0 1rem">Требования</h2>
            <table>
                <?php foreach ($req['checks'] as $c): ?>
                    <tr>
                        <td><?= installer_h($c['label']) ?></td>
                        <td>
                            <span class="badge <?= $c['ok'] ? 'ok' : 'bad' ?>"><?= $c['ok'] ? 'Ок' : 'Нет' ?></span>
                            <div class="muted"><?= installer_h($c['detail']) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if (! $req['ok']): ?>
                <p class="err">Устраните отмеченные проблемы и обновите страницу. Обычно достаточно в корне проекта выполнить:</p>
                <pre style="background:#f8f9fa;padding:.75rem;border-radius:8px;overflow:auto">composer install</pre>
            <?php endif; ?>
            <p style="margin-top:1rem">
                <?php if ($req['ok'] && $dirErrors === []): ?>
                    <a class="btn" href="?step=2">Далее →</a>
                <?php else: ?>
                    <button class="btn" type="button" disabled>Далее →</button>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($step === 2): ?>
        <?php if (! $req['ok'] || $dirErrors !== []): ?>
            <div class="card"><p class="err">Сначала пройдите шаг 1 — не все требования выполнены.</p>
                <a class="btn" href="?step=1">← Назад</a></div>
        <?php else: ?>
            <form class="card" method="post" action="install.php">
                <input type="hidden" name="installer_action" value="install">
                <input type="hidden" name="installer_token" value="<?= installer_h($token) ?>">

                <label for="app_name">Название приложения (APP_NAME)</label>
                <input id="app_name" name="app_name" required value="<?= installer_h($_POST['app_name'] ?? 'IT-Master') ?>">

                <label for="app_url">URL сайта (APP_URL)</label>
                <input id="app_url" name="app_url" required placeholder="http://localhost:8000" value="<?= installer_h($_POST['app_url'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost'))) ?>">
                <p class="muted">Укажите тот адрес, по которому открывают систему в браузере.</p>

                <label for="db_connection">База данных</label>
                <select id="db_connection" name="db_connection">
                    <option value="sqlite" selected>SQLite (файл database/database.sqlite)</option>
                    <option value="mysql">MySQL / MariaDB</option>
                </select>

                <div id="mysql-fields" style="display:none;margin-top:.5rem">
                    <div class="row row-2">
                        <div>
                            <label for="db_host">Хост</label>
                            <input id="db_host" name="db_host" value="<?= installer_h($_POST['db_host'] ?? '127.0.0.1') ?>">
                        </div>
                        <div>
                            <label for="db_port">Порт</label>
                            <input id="db_port" name="db_port" value="<?= installer_h($_POST['db_port'] ?? '3306') ?>">
                        </div>
                    </div>
                    <label for="db_database">Имя базы</label>
                    <input id="db_database" name="db_database" value="<?= installer_h($_POST['db_database'] ?? '') ?>">
                    <div class="row row-2">
                        <div>
                            <label for="db_username">Пользователь</label>
                            <input id="db_username" name="db_username" value="<?= installer_h($_POST['db_username'] ?? 'root') ?>">
                        </div>
                        <div>
                            <label for="db_password">Пароль</label>
                            <input id="db_password" name="db_password" type="password" value="">
                        </div>
                    </div>
                </div>

                <p class="muted">Будут выполнены: <code>php artisan key:generate</code>, <code>migrate</code>, <code>db:seed</code>, <code>storage:link</code>.</p>

                <a class="btn" href="?step=1" style="background:#6c757d;margin-right:.5rem">← Назад</a>
                <button class="btn" type="submit">Установить</button>
            </form>
            <script>
                (function () {
                    var sel = document.getElementById('db_connection');
                    var box = document.getElementById('mysql-fields');
                    function sync() { box.style.display = sel.value === 'mysql' ? 'block' : 'none'; }
                    sel.addEventListener('change', sync);
                    sync();
                })();
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
