<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

final class GitHubDeployApi
{
    /**
     * @return array{owner: string, repo: string}|null
     */
    public static function parseRepo(string $repoSpec): ?array
    {
        $repoSpec = trim($repoSpec);
        if ($repoSpec === '') {
            return null;
        }
        $parts = explode('/', $repoSpec, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return ['owner' => $parts[0], 'repo' => $parts[1]];
    }

    /**
     * @return array{sha: string, short: string, html_url: ?string}|null
     */
    public static function branchTip(string $owner, string $repo, string $branch, ?string $token): ?array
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/commits/".rawurlencode($branch);
        $response = self::request($url, $token);
        if (! $response->successful()) {
            return null;
        }
        $sha = $response->json('sha');
        if (! is_string($sha) || strlen($sha) < 7) {
            return null;
        }

        return [
            'sha' => $sha,
            'short' => substr($sha, 0, 7),
            'html_url' => $response->json('html_url'),
        ];
    }

    /**
     * Сравнение base (деплой) … head (ветка на GitHub).
     *
     * @return array{
     *   status: string,
     *   ahead_by: int,
     *   behind_by: int,
     *   html_url: ?string
     * }|null
     */
    public static function compare(string $owner, string $repo, string $baseSha, string $headRef, ?string $token): ?array
    {
        $pair = rawurlencode($baseSha).'...'.rawurlencode($headRef);
        $url = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$pair}";
        $response = self::request($url, $token);
        if (! $response->successful()) {
            return null;
        }

        $commits = $response->json('commits');
        $subjects = [];
        if (is_array($commits)) {
            foreach (array_slice($commits, 0, 50) as $c) {
                $msg = $c['commit']['message'] ?? '';
                if (! is_string($msg)) {
                    continue;
                }
                $parts = preg_split("/\r\n|\r|\n/", $msg, 2);
                $line = trim((string) ($parts[0] ?? ''));
                if ($line !== '') {
                    $subjects[] = $line;
                }
            }
        }

        return [
            'status' => (string) $response->json('status', ''),
            'ahead_by' => (int) $response->json('ahead_by', 0),
            'behind_by' => (int) $response->json('behind_by', 0),
            'html_url' => $response->json('html_url'),
            'commit_subjects' => $subjects,
        ];
    }

    /**
     * Первая строка файла VERSION на ветке (публичный raw GitHub).
     */
    public static function rawVersionFile(string $owner, string $repo, string $branch): ?string
    {
        $url = 'https://raw.githubusercontent.com/'
            .rawurlencode($owner).'/'.rawurlencode($repo).'/'.rawurlencode($branch).'/VERSION';
        $response = Http::timeout(10)
            ->withHeaders(['Accept' => 'text/plain'])
            ->get($url);
        if (! $response->successful()) {
            return null;
        }
        $body = trim($response->body());
        $line = strtok($body, "\r\n") ?: '';
        $line = trim((string) $line);

        return $line !== '' ? $line : null;
    }

    private static function request(string $url, ?string $token): \Illuminate\Http\Client\Response
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
        if (is_string($token) && $token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return Http::withHeaders($headers)
            ->timeout(25)
            ->get($url);
    }
}
