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

/**
 * Путь к PHP CLI для proc_open (artisan, composer-setup, composer.phar).
 * В IIS/FastCGI PHP_BINARY часто указывает на php-cgi — у него нет флага -r и иной интерфейс.
 */
function installer_php_binary(): string
{
    $binary = (string) PHP_BINARY;
    $base = strtolower(basename($binary));

    if ($binary !== '' && (str_contains($base, 'php-cgi') || $base === 'php-cgi.exe')) {
        $dir = dirname($binary);
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                $dir.DIRECTORY_SEPARATOR.'php.exe',
                $dir.DIRECTORY_SEPARATOR.'php-win.exe',
            ];
        } else {
            $candidates = [
                $dir.'/php',
                (defined('PHP_BINDIR') && PHP_BINDIR !== '') ? rtrim(PHP_BINDIR, '/').'/php' : '',
            ];
        }
        foreach ($candidates as $c) {
            if ($c !== '' && is_file($c) && @is_executable($c)) {
                return $c;
            }
        }
    }

    if ($binary !== '' && @is_executable($binary) && ! str_contains(strtolower(basename($binary)), 'php-cgi')) {
        return $binary;
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

/**
 * Скачивает URL в файл без отдельного процесса PHP.
 * Сначала cURL (не требует allow_url_fopen), иначе file_get_contents при allow_url_fopen.
 *
 * @return array{ok: bool, method?: string, error?: string}
 */
function installer_fetch_url_to_file(string $url, string $destPath): array
{
    if (is_file($destPath)) {
        @unlink($destPath);
    }

    if (extension_loaded('curl') && function_exists('curl_init')) {
        $fp = @fopen($destPath, 'wb');
        if ($fp === false) {
            return ['ok' => false, 'error' => 'Не удалось создать файл: '.$destPath.' (права на каталог?)'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $execOk = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (! $execOk || $httpCode >= 400) {
            @unlink($destPath);
            $msg = $curlErr !== '' ? $curlErr : ('HTTP '.$httpCode);
            if (stripos($curlErr, 'SSL') !== false || stripos($curlErr, 'certificate') !== false) {
                $msg .= ' — укажите в php.ini openssl.cafile (путь к cacert.pem) или включите корневые сертификаты.';
            }

            return ['ok' => false, 'error' => 'cURL: '.$msg];
        }

        if (! is_file($destPath) || filesize($destPath) < 100) {
            @unlink($destPath);

            return ['ok' => false, 'error' => 'cURL: скачан пустой или слишком короткий файл.'];
        }

        return ['ok' => true, 'method' => 'curl'];
    }

    if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 180,
                'follow_location' => 1,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $ctx);
        if ($data === false) {
            $hint = 'Проверьте allow_url_fopen, расширение openssl, сеть и openssl.cafile в php.ini.';
            if (! extension_loaded('curl')) {
                $hint .= ' Либо включите extension=curl в php.ini — тогда скачивание пойдёт без allow_url_fopen.';
            }

            return ['ok' => false, 'error' => 'file_get_contents не смог загрузить URL. '.$hint];
        }

        if (@file_put_contents($destPath, $data) === false) {
            return ['ok' => false, 'error' => 'Не удалось записать файл: '.$destPath];
        }

        return ['ok' => true, 'method' => 'allow_url_fopen'];
    }

    return [
        'ok' => false,
        'error' => 'Нет способа скачать по HTTPS: в php.ini включите allow_url_fopen=On и/или extension=curl. Альтернатива: положите composer.phar в корень проекта вручную.',
    ];
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
    $curlOk = extension_loaded('curl');
    $canFetchHttps = $urlFopen || $curlOk;
    $checks[] = [
        'label' => 'HTTPS: скачивание Composer',
        'ok' => $canFetchHttps,
        'detail' => $canFetchHttps
            ? ($curlOk ? 'доступно (cURL'.($urlFopen ? ' и allow_url_fopen' : ', allow_url_fopen выключен — ок').')' : 'allow_url_fopen')
            : 'В php.ini: allow_url_fopen=On и/или extension=curl; либо положите composer.phar в корень проекта',
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
    $pharPath = $root.DIRECTORY_SEPARATOR.'composer.phar';
    if (is_file($pharPath)) {
        return [
            'ok' => true,
            'steps' => [],
            'log' => 'composer.phar уже найден в корне проекта — загрузка пропущена.',
            'composer_phar' => true,
            'skipped' => true,
        ];
    }

    $steps = [];
    $log = [];

    $setupPath = $root.DIRECTORY_SEPARATOR.'composer-setup.php';

    $dl = installer_fetch_url_to_file('https://getcomposer.org/installer', $setupPath);
    $dlOut = $dl['ok']
        ? ('Успешно, способ: '.($dl['method'] ?? 'unknown'))
        : (string) ($dl['error'] ?? 'Неизвестная ошибка');
    $steps[] = [
        'label' => 'Загрузка composer-setup.php',
        'code' => $dl['ok'] ? 0 : 1,
        'output' => $dlOut,
    ];
    $log[] = 'Загрузка https://getcomposer.org/installer → composer-setup.php'."\n".$dlOut;
    if (! $dl['ok']) {
        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    $hash = @hash_file('sha384', $setupPath);
    $hashOk = is_string($hash) && hash_equals(COMPOSER_INSTALLER_SHA384, $hash);
    $verifyMsg = $hashOk
        ? 'SHA-384 совпадает, установщик проверен.'
        : ('Неверная контрольная сумма (ожидалось совпадение с константой). Получено: '.($hash ?: 'null'));
    $steps[] = [
        'label' => 'Проверка SHA-384 установщика',
        'code' => $hashOk ? 0 : 1,
        'output' => $verifyMsg,
    ];
    $log[] = 'Проверка SHA-384 composer-setup.php'."\n".$verifyMsg;
    if (! $hashOk) {
        if (is_file($setupPath)) {
            @unlink($setupPath);
        }

        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    $log[] = 'Исполняемый PHP (CLI): '.$php;
    $r3 = installer_proc(escapeshellarg($php).' -f '.escapeshellarg('composer-setup.php'), $root);
    $steps[] = ['label' => 'Запуск composer-setup.php', 'code' => $r3['code'], 'output' => $r3['output']];
    $log[] = '$ '.basename($php).' -f composer-setup.php'."\n".$r3['output'];
    if ($r3['code'] !== 0) {
        if (is_file($setupPath)) {
            @unlink($setupPath);
        }

        return ['ok' => false, 'steps' => $steps, 'log' => implode("\n\n", $log)];
    }

    if (is_file($setupPath)) {
        @unlink($setupPath);
    }
    $steps[] = ['label' => 'Удаление composer-setup.php', 'code' => 0, 'output' => 'Файл удалён.'];
    $log[] = 'composer-setup.php удалён после установки.';

    $phar = $root.DIRECTORY_SEPARATOR.'composer.phar';
    $ok = is_file($phar);

    return [
        'ok' => $ok,
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

function installer_api_ensure_composer_stack(): array
{
    $max = (int) ($_SESSION['installer_max_step'] ?? 1);
    if ($max < 2) {
        return [
            'ok' => false,
            'error' => 'step_gate',
            'log' => 'Сначала на шаге 1 нажмите «Далее» и подтвердите проверку требований.',
            'steps' => [],
        ];
    }

    $logParts = [];
    $allSteps = [];

    if (is_file(INSTALLER_ROOT.'/vendor/autoload.php')) {
        $_SESSION['installer_max_step'] = max($max, 3);

        return [
            'ok' => true,
            'skipped' => true,
            'log' => 'Каталог vendor/ уже найден — зависимости установлены, шаг пропущен.',
            'steps' => [],
            'vendor_ok' => true,
            'max_step' => (int) $_SESSION['installer_max_step'],
        ];
    }

    $phar = installer_api_composer_phar();
    $logParts[] = $phar['log'] ?? '';
    if (isset($phar['steps']) && is_array($phar['steps'])) {
        $allSteps = array_merge($allSteps, $phar['steps']);
    }
    if (! $phar['ok']) {
        return [
            'ok' => false,
            'log' => implode("\n\n", array_filter($logParts)),
            'steps' => $allSteps,
        ];
    }

    $inst = installer_api_composer_install();
    $logParts[] = $inst['log'] ?? '';
    if (isset($inst['steps']) && is_array($inst['steps'])) {
        $allSteps = array_merge($allSteps, $inst['steps']);
    }
    if (! $inst['ok']) {
        return [
            'ok' => false,
            'log' => implode("\n\n", array_filter($logParts)),
            'steps' => $allSteps,
        ];
    }

    $_SESSION['installer_max_step'] = 3;

    return [
        'ok' => true,
        'log' => implode("\n\n", array_filter($logParts)),
        'steps' => $allSteps,
        'vendor_ok' => true,
        'max_step' => 3,
    ];
}

function installer_api_finalize(array $data): array
{
    $dbConn = ($data['db_connection'] ?? '') === 'mysql' ? 'mysql' : 'sqlite';

    if ((int) ($_SESSION['installer_max_step'] ?? 1) < 3) {
        return ['ok' => false, 'log' => 'Сначала завершите шаг 2 (Composer и зависимости).', 'steps' => []];
    }

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
    echo '<style>html,body{height:100%;margin:0;overflow:hidden}body{background:#f5f7fb;font-family:\'Segoe UI\',sans-serif;display:flex;align-items:center;justify-content:center;padding:1rem}.panel{max-width:36rem;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);padding:1.25rem}</style>';
    echo '</head><body class="p-3"><div class="panel">';
    echo '<h1 class="h4 mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Установка уже выполнена</h1>';
    echo '<p>Файл <code>storage/framework/installer.lock</code> найден. Откройте <a href="/">главную страницу</a>.</p>';
    echo '<p class="text-muted small mb-0">Чтобы снова запустить мастер, удалите <code>installer.lock</code> (и при необходимости <code>public/install.php</code> после работы).</p>';
    echo '</div></body></html>';
    exit;
}

// -------------------------------------------------------------------------
// Разблокировка шагов (POST)
// -------------------------------------------------------------------------

$dirErrors = installer_ensure_directories();
$req = installer_requirements();
$_SESSION['installer_token'] = $_SESSION['installer_token'] ?? bin2hex(random_bytes(16));
$_SESSION['installer_max_step'] = isset($_SESSION['installer_max_step'])
    ? max(1, min(3, (int) $_SESSION['installer_max_step']))
    : 1;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['installer_unlock'])
    && (string) $_POST['installer_unlock'] === 'step2'
) {
    if (! installer_api_verify_token()) {
        header('Location: install.php?step=1', true, 302);
        exit;
    }
    if ($req['core_ok'] && $dirErrors === []) {
        $_SESSION['installer_max_step'] = max((int) $_SESSION['installer_max_step'], 2);
        header('Location: install.php?step=2', true, 302);
        exit;
    }
    header('Location: install.php?step=1', true, 302);
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
        if ($action === 'ensure_composer_stack') {
            if ((int) ($_SESSION['installer_max_step'] ?? 1) < 2) {
                http_response_code(403);
                echo json_encode([
                    'ok' => false,
                    'error' => 'step_gate',
                    'log' => 'Шаг 2 недоступен: завершите шаг 1.',
                    'steps' => [],
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $payload = installer_api_ensure_composer_stack();
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'composer_phar') {
            if ((int) ($_SESSION['installer_max_step'] ?? 1) < 2) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'step_gate', 'log' => 'Сначала шаг 1.', 'steps' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $payload = installer_api_composer_phar();
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'composer_install') {
            if ((int) ($_SESSION['installer_max_step'] ?? 1) < 2) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'step_gate', 'log' => 'Сначала шаг 1.', 'steps' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }
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

$token = $_SESSION['installer_token'];
$maxStep = (int) $_SESSION['installer_max_step'];

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
if ($step < 1 || $step > 3) {
    $step = 1;
}
if ($step > $maxStep) {
    header('Location: install.php?step='.$maxStep, true, 302);
    exit;
}

$bootstrapCss = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css';
$bootstrapJs = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js';
$iconsCss = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css';

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
        /* Весь интерфейс в пределах экрана без прокрутки; крупный читабельный текст и широкая колонка */
        html, body {
            height: 100%;
            max-height: 100dvh;
            margin: 0;
            overflow: hidden;
            box-sizing: border-box;
        }
        *, *::before, *::after { box-sizing: inherit; }
        body {
            background: #f5f7fb;
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 1.0625rem;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
        }
        .installer-shell {
            flex: 1;
            width: 100%;
            max-width: min(92rem, calc(100vw - 1.25rem));
            margin: 0 auto;
            padding: 0.45rem 0.75rem 0.55rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .installer-top {
            flex: 0 0 auto;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.1rem;
        }
        .header-title {
            font-weight: 600;
            color: #000;
            font-size: 1.625rem;
            margin: 0;
            line-height: 1.2;
        }
        .installer-lead {
            font-size: 1rem;
            color: #495057;
            margin: 0.15rem 0 0.35rem;
            line-height: 1.45;
            max-height: 2.9em;
            overflow: hidden;
        }
        .installer-lead code { font-size: 0.95em; }
        .profile-tabs {
            display: flex;
            gap: 10px;
            background: white;
            border-radius: 12px;
            padding: 6px;
            margin-bottom: 0.4rem;
            flex: 0 0 auto;
        }
        .profile-tab {
            flex: 1;
            text-align: center;
            padding: 0.55rem 0.85rem;
            border-radius: 10px;
            font-weight: 500;
            color: #000;
            background: transparent;
            font-size: 1rem;
            min-width: 0;
            border: none;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .profile-tab.active { background: #d9e1ef; font-weight: 600; }
        .profile-tab:not(.active):hover { background: #f8f9fa; color: #000; }
        .profile-tab:disabled { opacity: 0.45; pointer-events: none; }
        .profile-tab.locked {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
            color: #6c757d !important;
            background: #f1f3f5 !important;
        }
        .installer-workspace {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.4fr);
            gap: 0.65rem;
            overflow: hidden;
        }
        @media (max-width: 767.98px) {
            .installer-workspace {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(92px, 20dvh) minmax(0, 1fr);
            }
            .installer-shell {
                max-width: 100%;
                padding-left: 0.6rem;
                padding-right: 0.6rem;
            }
            .header-title { font-size: 1.35rem; }
        }
        .installer-console-card,
        .installer-main-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, 0.07);
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .installer-console-head {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #495057;
            padding: 0.5rem 0.85rem 0.4rem;
            border-bottom: 1px solid #e9ecef;
            flex: 0 0 auto;
        }
        .console-panel {
            flex: 1;
            min-height: 0;
            background: #1e1e1e;
            color: #e2e2e2;
            font-family: ui-monospace, Consolas, 'Courier New', monospace;
            font-size: 0.9375rem;
            line-height: 1.45;
            padding: 0.55rem 0.75rem;
            overflow: hidden;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .console-panel:empty::before {
            content: 'Вывод команд…';
            color: #9a9a9a;
        }
        .installer-main-inner {
            flex: 1;
            min-height: 0;
            padding: 0.6rem 1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            overflow-x: hidden;
            gap: 0.4rem;
            font-size: 1.0625rem;
        }
        .notification-panel {
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            padding: 0;
            margin: 0;
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 0.35rem;
            padding-bottom: 0.2rem;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            flex: 0 0 auto;
        }
        .step-body {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .step-body-scroll-fake {
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        .installer-hint {
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            margin-bottom: 0.3rem;
        }
        .installer-hint code { font-size: 0.95em; }
        .installer-status-text { font-size: 1.0625rem; margin-bottom: 0.3rem; }
        .installer-footnote {
            font-size: 0.9375rem;
            line-height: 1.45;
            color: #6c757d;
            margin: 0.3rem 0 0;
        }
        .installer-footnote code { font-size: 0.95em; }
        .installer-progress-meta {
            font-size: 0.9375rem;
            color: #6c757d;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            color: white !important;
            padding: 0.55rem 1.35rem;
            font-size: 1.0625rem;
        }
        .btn-gradient.btn-sm {
            padding: 0.45rem 1rem;
            font-size: 1rem;
        }
        .btn-gradient:hover {
            filter: brightness(1.05);
            color: white !important;
        }
        .btn-gradient:disabled { opacity: 0.55; }
        .installer-main-inner .btn-secondary {
            font-size: 1.0625rem;
            padding: 0.5rem 1rem;
        }
        .installer-main-inner .btn-outline-secondary {
            font-size: 1rem;
            padding: 0.45rem 1rem;
        }
        .progress { height: 10px; border-radius: 8px; margin: 0; }
        .progress-bar { transition: width 0.35s ease; }
        #global-progress-wrap {
            flex: 0 0 auto;
            margin: 0 !important;
        }
        table.install-checks {
            font-size: 1rem;
            margin-bottom: 0;
            width: 100%;
            table-layout: fixed;
        }
        table.install-checks td {
            vertical-align: top;
            padding: 0.3rem 0.55rem 0.3rem 0;
            border-color: #eee;
        }
        table.install-checks td:first-child {
            width: 38%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        table.install-checks .cell-detail {
            font-size: 0.9375rem;
            line-height: 1.4;
            max-height: 2.8em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            color: #5c636a;
        }
        table.install-checks .badge {
            font-size: 0.8125rem;
            font-weight: 600;
            vertical-align: middle;
            padding: 0.35em 0.55em;
        }
        .installer-actions {
            flex: 0 0 auto;
            margin-top: 0.35rem;
            padding-top: 0.3rem;
        }
        .installer-warn {
            font-size: 1rem;
            margin-top: 0.3rem;
        }
        .form-finalize-compact .form-label {
            font-size: 1rem;
            margin-bottom: 0.2rem;
            font-weight: 500;
        }
        .form-finalize-compact .form-control,
        .form-finalize-compact .form-select {
            padding: 0.5rem 0.75rem;
            font-size: 1.0625rem;
        }
        .form-finalize-compact.row { --bs-gutter-y: 0.4rem; --bs-gutter-x: 0.65rem; }
        .dir-errors-compact {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
            margin: 0;
            border-radius: 10px;
            line-height: 1.45;
        }
        .dir-errors-compact ul { margin: 0.2rem 0 0; padding-left: 1.2rem; }
    </style>
</head>
<body>
<div class="installer-shell" id="installer-step-meta" data-step="<?= (int) $step ?>" data-max-step="<?= (int) $maxStep ?>" data-autorun="<?= ($step === 2 && $maxStep >= 2 && ! ($req['vendor_ok'] && $maxStep >= 3)) ? '1' : '0' ?>">
    <div class="installer-top">
        <h1 class="header-title"><i class="bi bi-gear-wide-connected text-primary me-1"></i>Установка IT-Master</h1>
    </div>
    <p class="installer-lead">Composer, зависимости, <code>.env</code>, миграции. Шаги по очереди.</p>

    <div class="profile-tabs" role="tablist">
        <a class="profile-tab <?= $step === 1 ? 'active' : '' ?>" href="?step=1">1. Проверка</a>
        <?php if ($maxStep >= 2): ?>
            <a class="profile-tab <?= $step === 2 ? 'active' : '' ?>" href="?step=2">2. Composer</a>
        <?php else: ?>
            <span class="profile-tab locked" title="Сначала завершите шаг 1">2. <i class="bi bi-lock-fill"></i></span>
        <?php endif; ?>
        <?php if ($maxStep >= 3): ?>
            <a class="profile-tab <?= $step === 3 ? 'active' : '' ?>" href="?step=3">3. Настройка</a>
        <?php else: ?>
            <span class="profile-tab locked" title="Сначала завершите шаг 2">3. <i class="bi bi-lock-fill"></i></span>
        <?php endif; ?>
    </div>

    <div class="installer-workspace">
        <aside class="installer-console-card" aria-label="Консоль">
            <div class="installer-console-head">Консоль</div>
            <div class="console-panel" id="installer-console"></div>
        </aside>
        <main class="installer-main-card">
            <div class="installer-main-inner">
                <?php if ($dirErrors !== []): ?>
                    <div class="dir-errors-compact border border-danger border-opacity-50 bg-danger bg-opacity-10">
                        <strong class="text-danger">Каталоги</strong>
                        <ul><?php foreach ($dirErrors as $e) {
                            echo '<li>'.installer_h($e).'</li>';
                        } ?></ul>
                    </div>
                <?php endif; ?>

                <div class="d-none" id="global-progress-wrap">
                    <div class="d-flex justify-content-between mb-1 installer-progress-meta">
                        <span id="global-progress-label">Выполняется…</span>
                        <span id="global-progress-pct">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="global-progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <?php if ($step === 1): ?>
                    <div class="notification-panel">
                        <div class="section-title">Требования</div>
                        <div class="step-body">
                            <div class="step-body-scroll-fake">
                                <table class="table install-checks mb-0">
                                    <tbody>
                                    <?php foreach ($req['checks'] as $c): ?>
                                        <tr title="<?= installer_h($c['detail']) ?>">
                                            <td title="<?= installer_h($c['label']) ?>"><?= installer_h($c['label']) ?></td>
                                            <td title="<?= installer_h($c['detail']) ?>">
                                                <?php if ($c['ok']): ?>
                                                    <span class="badge text-bg-success">Ок</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-danger">Нет</span>
                                                <?php endif; ?>
                                                <div class="cell-detail text-muted"><?= installer_h($c['detail']) ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (! $req['core_ok']): ?>
                                <p class="text-danger mb-0 installer-warn">Исправьте окружение и обновите страницу.</p>
                            <?php endif; ?>
                            <div class="installer-actions">
                                <?php if ($req['core_ok'] && $dirErrors === []): ?>
                                    <form method="post" action="install.php" class="d-inline">
                                        <input type="hidden" name="installer_token" value="<?= installer_h($token) ?>">
                                        <input type="hidden" name="installer_unlock" value="step2">
                                        <button type="submit" class="btn btn-gradient">Далее: Composer <i class="bi bi-arrow-right ms-1"></i></button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-gradient" type="button" disabled>Далее: Composer</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($step === 2): ?>
                    <div class="notification-panel">
                        <?php if ($maxStep < 2 || ! $req['core_ok'] || $dirErrors !== []): ?>
                            <p class="text-danger mb-2">Шаг 2 недоступен.</p>
                            <a class="btn btn-secondary" href="?step=1"><i class="bi bi-arrow-left me-1"></i>Шаг 1</a>
                        <?php else: ?>
                            <div class="section-title">Composer</div>
                            <div class="step-body">
                                <p class="installer-hint mb-1">Автоматически: <code>composer.phar</code> и <code>composer install</code> при необходимости (проверка SHA-384).</p>
                                <p class="installer-status-text mb-1" id="step2-status">
                                    <?php if ($req['vendor_ok'] && $maxStep >= 3): ?>
                                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Готово — шаг 3.</span>
                                    <?php else: ?>
                                        <span class="text-primary"><i class="bi bi-hourglass-split me-1"></i>Запуск…</span>
                                    <?php endif; ?>
                                </p>
                                <button type="button" class="btn btn-outline-secondary" id="btn-retry-stack" <?= ($req['vendor_ok'] && $maxStep >= 3) ? 'disabled' : '' ?>>
                                    <i class="bi bi-arrow-repeat me-1"></i>Повторить
                                </button>
                                <p class="installer-footnote">Хэш установщика: константа <code>COMPOSER_INSTALLER_SHA384</code> в <code>install.php</code>.</p>
                                <div class="installer-actions d-flex flex-wrap gap-2">
                                    <a class="btn btn-secondary" href="?step=1"><i class="bi bi-arrow-left me-1"></i>Назад</a>
                                    <?php if ($maxStep >= 3): ?>
                                        <a class="btn btn-gradient" href="?step=3">Далее: настройка <i class="bi bi-arrow-right ms-1"></i></a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-gradient" disabled id="link-step3-pending"><i class="bi bi-lock me-1"></i>Шаг 3</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 3): ?>
                    <div class="notification-panel">
                        <?php if ($maxStep < 3 || ! $req['core_ok'] || $dirErrors !== [] || ! $req['vendor_ok']): ?>
                            <p class="text-danger mb-2">Шаг 3 недоступен.</p>
                            <a class="btn btn-secondary" href="?step=2"><i class="bi bi-arrow-left me-1"></i>Шаг 2</a>
                        <?php else: ?>
                            <div class="section-title">Параметры</div>
                            <div class="step-body">
                                <form id="form-finalize" class="row form-finalize-compact g-2">
                                    <input type="hidden" name="installer_token" value="<?= installer_h($token) ?>">

                                    <div class="col-md-6">
                                        <label class="form-label" for="app_name">APP_NAME</label>
                                        <input class="form-control" id="app_name" name="app_name" required value="<?= installer_h($_POST['app_name'] ?? 'IT-Master') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="app_url">APP_URL</label>
                                        <input class="form-control" id="app_url" name="app_url" required placeholder="http://localhost:8000" value="<?= installer_h($_POST['app_url'] ?? $defaultUrl) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="db_connection">БД</label>
                                        <select class="form-select" id="db_connection" name="db_connection">
                                            <option value="sqlite" selected>SQLite</option>
                                            <option value="mysql">MySQL</option>
                                        </select>
                                    </div>
                                    <div id="mysql-fields" class="col-12" style="display:none">
                                        <div class="row g-2">
                                            <div class="col-6 col-md-3">
                                                <label class="form-label" for="db_host">Хост</label>
                                                <input class="form-control" id="db_host" name="db_host" value="127.0.0.1">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label" for="db_port">Порт</label>
                                                <input class="form-control" id="db_port" name="db_port" value="3306">
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label" for="db_database">Имя БД</label>
                                                <input class="form-control" id="db_database" name="db_database" value="">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label" for="db_username">Пользователь</label>
                                                <input class="form-control" id="db_username" name="db_username" value="root">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label" for="db_password">Пароль</label>
                                                <input class="form-control" id="db_password" name="db_password" type="password" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="installer-footnote mb-0">Будут выполнены: <code>key:generate</code>, <code>migrate</code>, <code>db:seed</code>, <code>storage:link</code>.</p>
                                    </div>
                                    <div class="col-12 installer-actions d-flex flex-wrap gap-2">
                                        <a class="btn btn-secondary" href="?step=2"><i class="bi bi-arrow-left me-1"></i>Назад</a>
                                        <button type="submit" class="btn btn-gradient" id="btn-finalize">
                                            <i class="bi bi-lightning-charge me-1"></i>Завершить установку
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
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

    var consoleMaxChars = 12000;
    function appendConsole(text) {
        if (!consoleEl) return;
        var next = (consoleEl.textContent ? consoleEl.textContent + '\n' : '') + text;
        if (next.length > consoleMaxChars) {
            next = '… [обрезано]\n' + next.slice(next.length - consoleMaxChars);
        }
        consoleEl.textContent = next;
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

    var stackRunning = false;
    function runEnsureStack(isAuto) {
        var meta = document.getElementById('installer-step-meta');
        if (!meta || meta.dataset.step !== '2') return;
        if (stackRunning) return;
        stackRunning = true;
        var retryBtn = document.getElementById('btn-retry-stack');
        if (retryBtn) retryBtn.disabled = true;
        if (!isAuto) {
            appendConsole('\n--- Повтор: Composer + зависимости ---');
        } else {
            appendConsole('=== Автоматическая установка Composer и зависимостей ===');
        }
        fakeProgressWhile(
            postApi('ensure_composer_stack').then(function (data) {
                appendConsole(data.log || data.error || JSON.stringify(data));
                setProgress(100, data.ok ? 'Готово' : 'Ошибка');
                hideProgressStripes();
                var st = document.getElementById('step2-status');
                if (data.ok && data.vendor_ok) {
                    if (st) st.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Готово. Переход к шагу 3…</span>';
                    setTimeout(function () { window.location.assign('install.php?step=3'); }, 600);
                } else {
                    if (st) st.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Ошибка. Исправьте окружение и нажмите «Повторить».</span>';
                    if (retryBtn) retryBtn.disabled = false;
                }
            }).catch(function (e) {
                appendConsole('Ошибка: ' + e);
                setProgress(100, 'Ошибка');
                hideProgressStripes();
                var st = document.getElementById('step2-status');
                if (st) st.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Сбой запроса. Повторите.</span>';
                if (retryBtn) retryBtn.disabled = false;
            }),
            'Скачивание Composer и composer install (долго при первом запуске)…',
            92
        ).finally(function () { stackRunning = false; });
    }

    (function () {
        var meta = document.getElementById('installer-step-meta');
        if (meta && meta.dataset.autorun === '1') {
            runEnsureStack(true);
        }
    })();

    var btnRetry = document.getElementById('btn-retry-stack');
    if (btnRetry) {
        btnRetry.addEventListener('click', function () { runEnsureStack(false); });
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
