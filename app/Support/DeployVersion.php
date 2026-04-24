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

        $payload = [
            'ref' => $ref,
            'updated_at' => gmdate('c'),
        ];

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
}
