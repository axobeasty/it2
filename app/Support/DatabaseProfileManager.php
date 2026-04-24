<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    public function migrateBetweenProfiles(string $sourceProfile, string $targetProfile): void
    {
        $sourceConnection = $this->connectionForProfile($sourceProfile);
        $targetConnection = $this->connectionForProfile($targetProfile);

        if ($sourceConnection === $targetConnection) {
            throw new RuntimeException('Источник и назначение миграции совпадают.');
        }

        // Подтягиваем структуру таблиц в целевой БД перед переносом данных.
        Artisan::call('migrate', [
            '--database' => $targetConnection,
            '--force' => true,
        ]);

        $sourceTables = $this->tablesForConnection($sourceConnection);
        $targetTables = $this->tablesForConnection($targetConnection);
        $tableNames = array_values(array_intersect($sourceTables, $targetTables));

        DB::connection($targetConnection)->beginTransaction();
        try {
            foreach ($tableNames as $tableName) {
                if ($this->shouldSkipTable($tableName)) {
                    continue;
                }

                $rows = DB::connection($sourceConnection)->table($tableName)->get()->map(
                    static fn ($row) => (array) $row
                )->all();

                DB::connection($targetConnection)->table($tableName)->truncate();

                if (! empty($rows)) {
                    foreach (array_chunk($rows, 500) as $chunk) {
                        DB::connection($targetConnection)->table($tableName)->insert($chunk);
                    }
                }
            }

            DB::connection($targetConnection)->commit();
        } catch (Throwable $e) {
            DB::connection($targetConnection)->rollBack();
            throw new RuntimeException('Ошибка переноса данных: '.$e->getMessage(), 0, $e);
        }
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
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
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
}
