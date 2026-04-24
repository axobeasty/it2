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
