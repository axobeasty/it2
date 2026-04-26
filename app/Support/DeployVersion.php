<?php

namespace App\Support;

use Symfony\Component\Process\Process;

final class DeployVersion
{
    public static function deployJsonPath(): string
    {
        return storage_path('app/deploy.json');
    }

    public static function readDeployJson(): ?array
    {
        $path = self::deployJsonPath();
        if (! is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Локальный commit SHA (полный или короткий), зафиксированный при деплое.
     */
    public static function localRefFromFile(): ?string
    {
        $data = self::readDeployJson();
        if (! $data) {
            return null;
        }
        $ref = $data['ref'] ?? $data['commit'] ?? null;

        return is_string($ref) && trim($ref) !== '' ? trim($ref) : null;
    }

    public static function isGitWorkingTree(string $basePath): bool
    {
        $gitPath = $basePath.DIRECTORY_SEPARATOR.'.git';

        return is_dir($gitPath) || is_file($gitPath);
    }

    /**
     * Порядок: DEPLOY_GIT_REF → storage/app/deploy.json → git rev-parse HEAD (если есть .git).
     */
    public static function resolveLocalRef(string $basePath): array
    {
        $envRef = env('DEPLOY_GIT_REF');
        if (is_string($envRef) && trim($envRef) !== '') {
            return ['ref' => trim($envRef), 'source' => 'env'];
        }

        $fileRef = self::localRefFromFile();
        if ($fileRef !== null) {
            return ['ref' => $fileRef, 'source' => 'deploy_json'];
        }

        if (self::isGitWorkingTree($basePath)) {
            $head = self::gitHeadSha($basePath);
            if ($head !== null) {
                return ['ref' => $head, 'source' => 'git_head'];
            }
        }

        return ['ref' => null, 'source' => 'none'];
    }

    /**
     * Версия релиза: .env → VERSION → deploy.json → composer.json → git describe / короткий SHA.
     *
     * @return array{version: ?string, source: 'env'|'version_file'|'deploy_json'|'composer'|'git_describe'|'git_head'|null}
     */
    public static function resolveReleaseVersion(string $basePath): array
    {
        $fromConfig = config('app.release_version');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return ['version' => trim($fromConfig), 'source' => 'env'];
        }

        $versionFile = $basePath.DIRECTORY_SEPARATOR.'VERSION';
        if (is_readable($versionFile)) {
            $line = @file($versionFile, FILE_IGNORE_NEW_LINES);
            $raw = is_array($line) && isset($line[0]) ? trim((string) $line[0]) : '';
            if ($raw !== '') {
                return ['version' => $raw, 'source' => 'version_file'];
            }
        }

        $data = self::readDeployJson();
        if (is_array($data)) {
            $rel = $data['release'] ?? null;
            if (is_string($rel) && trim($rel) !== '') {
                return ['version' => trim($rel), 'source' => 'deploy_json'];
            }
        }

        if (self::isGitWorkingTree($basePath)) {
            $desc = self::gitDescribe($basePath, 'HEAD');
            if (is_string($desc) && $desc !== '') {
                return ['version' => $desc, 'source' => 'git_describe'];
            }
            $head = self::gitHeadSha($basePath);
            if (is_string($head) && $head !== '') {
                return ['version' => substr($head, 0, 7), 'source' => 'git_head'];
            }
        }

        $composerPath = $basePath.DIRECTORY_SEPARATOR.'composer.json';
        if (is_readable($composerPath)) {
            $rawComposer = @file_get_contents($composerPath);
            if (is_string($rawComposer) && $rawComposer !== '') {
                $decoded = json_decode($rawComposer, true);
                if (is_array($decoded)) {
                    $cv = $decoded['version'] ?? null;
                    if (is_string($cv) && trim($cv) !== '') {
                        return ['version' => trim($cv), 'source' => 'composer'];
                    }
                }
            }
        }

        return ['version' => null, 'source' => null];
    }

    /**
     * @throws \JsonException
     */
    public static function writeDeployJson(string $ref): void
    {
        $ref = strtolower(trim($ref));
        if ($ref === '') {
            throw new \InvalidArgumentException('Пустой ref.');
        }

        $path = self::deployJsonPath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            throw new \RuntimeException('Каталог storage/app не найден.');
        }

        if (! is_writable($dir)) {
            throw new \RuntimeException('Нет прав на запись в storage/app.');
        }

        if (file_exists($path) && ! is_writable($path)) {
            throw new \RuntimeException('Файл deploy.json есть, но PHP не может его перезаписать (права на файл).');
        }

        $existing = self::readDeployJson();
        $payload = [
            'ref' => $ref,
            'updated_at' => gmdate('c'),
        ];
        if (is_array($existing) && isset($existing['release']) && is_string($existing['release']) && trim($existing['release']) !== '') {
            $payload['release'] = trim($existing['release']);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json) === false) {
            throw new \RuntimeException('Не удалось записать файл deploy.json.');
        }
    }

    /**
     * Сохраняет ref в deploy.json, если версия не зафиксирована только через DEPLOY_GIT_REF в .env.
     *
     * @return array{saved: bool, ref: ?string, skipped_env: bool, error: ?string}
     */
    public static function tryPersistDeployRef(string $basePath, string $ref): array
    {
        $ref = strtolower(trim($ref));
        if ($ref === '') {
            return ['saved' => false, 'ref' => null, 'skipped_env' => false, 'error' => 'Пустой ref.'];
        }

        $resolved = self::resolveLocalRef($basePath);
        if ($resolved['source'] === 'env') {
            return ['saved' => false, 'ref' => null, 'skipped_env' => true, 'error' => null];
        }

        try {
            self::writeDeployJson($ref);

            return ['saved' => true, 'ref' => $ref, 'skipped_env' => false, 'error' => null];
        } catch (\Throwable $e) {
            return ['saved' => false, 'ref' => null, 'skipped_env' => false, 'error' => $e->getMessage()];
        }
    }

    private static function gitHeadSha(string $basePath): ?string
    {
        try {
            $process = new Process(['git', 'rev-parse', 'HEAD'], $basePath, null, null, 20);
            $process->run();
            if (! $process->isSuccessful()) {
                return null;
            }
            $out = trim($process->getOutput());

            return $out !== '' ? $out : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function gitDescribe(string $basePath, string $rev): ?string
    {
        try {
            $process = new Process(['git', 'describe', '--tags', '--always', $rev], $basePath, null, null, 20);
            $process->run();
            if (! $process->isSuccessful()) {
                return null;
            }
            $out = trim($process->getOutput());

            return $out !== '' ? $out : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
