<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class DatabaseProfileManager
{
    /**
     * Таблицы Laravel для внутренней работы, их не переносим между БД.
     */
    private const SKIP_TABLES = [
        'migrations',
        'failed_jobs',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
    ];

    public function applyDatabaseSettings(array $settings): void
    {
        $this->writeEnvValues([
            'DB_ACTIVE_PROFILE' => $settings['db_profile'],
            'DB_REMOTE_HOST' => $settings['remote_host'],
            'DB_REMOTE_PORT' => $settings['remote_port'],
            'DB_REMOTE_DATABASE' => $settings['remote_database'],
            'DB_REMOTE_USERNAME' => $settings['remote_username'],
            'DB_REMOTE_PASSWORD' => $settings['remote_password'],
            'DB_REMOTE_CHARSET' => $settings['remote_charset'],
            'DB_REMOTE_COLLATION' => $settings['remote_collation'],
        ]);

        $this->clearRuntimeCaches();
    }

    public function migrateBetweenProfiles(string $sourceProfile, string $targetProfile, ?callable $progress = null): void
    {
        $sourceConnection = $this->connectionForProfile($sourceProfile);
        $targetConnection = $this->connectionForProfile($targetProfile);

        if ($sourceConnection === $targetConnection) {
            throw new RuntimeException('Источник и назначение миграции совпадают.');
        }

        $this->notify($progress, 5, 'Проверка подключений и подготовка миграции...');

        // Подтягиваем структуру таблиц в целевой БД перед переносом данных.
        Artisan::call('migrate', [
            '--database' => $targetConnection,
            '--force' => true,
        ]);
        $this->notify($progress, 20, 'Структура целевой БД актуализирована (migrate).');

        $sourceTables = $this->tablesForConnection($sourceConnection);
        $targetTables = $this->tablesForConnection($targetConnection);
        $tableNames = array_values(array_intersect($sourceTables, $targetTables));
        $totalTables = max(count($tableNames), 1);

        DB::connection($targetConnection)->beginTransaction();
        try {
            $processed = 0;
            foreach ($tableNames as $tableName) {
                if ($this->shouldSkipTable($tableName)) {
                    continue;
                }

                DB::connection($targetConnection)->table($tableName)->truncate();
                $copiedRows = 0;
                $sourceTableQuery = DB::connection($sourceConnection)->table($tableName);
                if (Schema::connection($sourceConnection)->hasColumn($tableName, 'id')) {
                    $sourceTableQuery
                        ->orderBy('id')
                        ->chunkById(500, function ($chunk) use ($targetConnection, $tableName, &$copiedRows) {
                            $rows = $chunk->map(static fn ($row) => (array) $row)->all();
                            if ($rows !== []) {
                                DB::connection($targetConnection)->table($tableName)->insert($rows);
                                $copiedRows += count($rows);
                            }
                        });
                } else {
                    $sourceTableQuery
                        ->chunk(500, function ($chunk) use ($targetConnection, $tableName, &$copiedRows) {
                            $rows = $chunk->map(static fn ($row) => (array) $row)->all();
                            if ($rows !== []) {
                                DB::connection($targetConnection)->table($tableName)->insert($rows);
                                $copiedRows += count($rows);
                            }
                        });
                }

                $processed++;
                $percent = 20 + (int) floor(($processed / $totalTables) * 75);
                $this->notify(
                    $progress,
                    min($percent, 95),
                    "Таблица {$tableName}: перенесено строк {$copiedRows}."
                );
            }

            DB::connection($targetConnection)->commit();
            $this->notify($progress, 100, 'Миграция данных завершена успешно.');
        } catch (Throwable $e) {
            DB::connection($targetConnection)->rollBack();
            throw new RuntimeException('Ошибка переноса данных: '.$e->getMessage(), 0, $e);
        }
    }

    public function checkRemoteConnection(): void
    {
        try {
            DB::purge('mysql_remote');
            DB::connection('mysql_remote')->getPdo();
            DB::connection('mysql_remote')->select('SELECT 1');
        } catch (Throwable $e) {
            throw new RuntimeException('Не удалось подключиться к удаленной БД: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array{is_empty: bool, tables_before: int, table_names: array<int, string>}
     */
    public function inspectRemoteDatabase(): array
    {
        $this->checkRemoteConnection();

        $tables = $this->tablesForConnection('mysql_remote');
        $businessTables = array_values(array_filter(
            $tables,
            fn (string $tableName) => ! $this->shouldSkipTable($tableName)
        ));

        return [
            'is_empty' => count($businessTables) === 0,
            'tables_before' => count($businessTables),
            'table_names' => $businessTables,
        ];
    }

    /**
     * @return array{mode: string, tables_before: int}
     */
    public function initializeRemoteDatabase(?callable $progress = null): array
    {
        $this->notify($progress, 5, 'Проверка подключения к удаленной БД...');
        $inspection = $this->inspectRemoteDatabase();
        $this->notify($progress, 15, 'Подключение успешно. Анализ структуры удаленной БД...');
        $isEmpty = (bool) $inspection['is_empty'];
        if ($isEmpty) {
            $this->notify($progress, 35, 'База пустая: запускаем migrate...');
            Artisan::call('migrate', [
                '--database' => 'mysql_remote',
                '--force' => true,
            ]);
            $this->notify($progress, 70, 'Миграции для удаленной БД завершены.');
        } else {
            $this->notify($progress, 35, 'База не пустая: выполняем migrate:fresh (очистка и пересоздание)...');
            Artisan::call('migrate:fresh', [
                '--database' => 'mysql_remote',
                '--force' => true,
            ]);
            $this->notify($progress, 70, 'Очистка и миграции удаленной БД завершены.');
        }

        $this->notify($progress, 85, 'Запускаем сиды (db:seed)...');
        Artisan::call('db:seed', [
            '--database' => 'mysql_remote',
            '--force' => true,
        ]);
        $this->notify($progress, 100, 'Инициализация удаленной БД завершена.');

        return [
            'mode' => $isEmpty ? 'empty' : 'not_empty',
            'tables_before' => (int) $inspection['tables_before'],
        ];
    }

    private function tablesForConnection(string $connection): array
    {
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'sqlite') {
            $tables = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type = 'table'");
            return array_map(static fn ($row) => $row->name, $tables);
        }

        if ($driver === 'mysql') {
            $tables = DB::connection($connection)->select('SHOW TABLES');
            return array_map(static function ($row) {
                $asArray = (array) $row;
                return (string) reset($asArray);
            }, $tables);
        }

        throw new RuntimeException('Неподдерживаемый драйвер БД: '.$driver);
    }

    private function connectionForProfile(string $profile): string
    {
        return $profile === 'remote' ? 'mysql_remote' : 'sqlite';
    }

    private function shouldSkipTable(string $tableName): bool
    {
        return in_array($tableName, self::SKIP_TABLES, true) || $tableName === 'sqlite_sequence';
    }

    private function clearRuntimeCaches(): void
    {
        // Не запускаем artisan clear-команды из web-запроса:
        // на некоторых окружениях это приводит к сбросу соединения браузера.
        DB::purge('sqlite');
        DB::purge('mysql_remote');

        $activeProfile = (string) env('DB_ACTIVE_PROFILE', 'sqlite');
        Config::set('database.default', $activeProfile === 'remote' ? 'mysql_remote' : 'sqlite');
    }

    private function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? (string) file_get_contents($envPath) : '';

        foreach ($values as $key => $value) {
            $escaped = $this->formatEnvValue($value);
            $pattern = "/^".preg_quote($key, '/')."=.*/m";

            if (preg_match($pattern, $envContent) === 1) {
                $envContent = (string) preg_replace($pattern, $key.'='.$escaped, $envContent);
            } else {
                $envContent .= PHP_EOL.$key.'='.$escaped;
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function formatEnvValue(?string $value): string
    {
        $value = $value ?? '';
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s/', $value) === 1) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function notify(?callable $progress, int $percent, string $message): void
    {
        if ($progress !== null) {
            $progress($percent, $message);
        }
    }
}
