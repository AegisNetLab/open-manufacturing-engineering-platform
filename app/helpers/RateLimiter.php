<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ApiException;

final class RateLimiter
{
    private const DEFAULT_LIMIT = 120;
    private const DEFAULT_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly string $storagePath,
        private readonly int $limit = self::DEFAULT_LIMIT,
        private readonly int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ) {
    }

    public static function fromProjectDefaults(): self
    {
        return new self(dirname(__DIR__, 2) . '/storage/rate_limits');
    }

    /**
     * @return array{allowed:bool,remaining:int,retry_after:int,reset_at:int}
     */
    public function attempt(string $identity, string $scope, ?int $now = null): array
    {
        $now ??= time();
        $this->ensureStoragePath();

        $file = $this->bucketFile($identity, $scope);
        $bucket = $this->readBucket($file);

        if ($bucket === [] || ($bucket['reset_at'] ?? 0) <= $now) {
            $bucket = [
                'count' => 0,
                'reset_at' => $now + $this->windowSeconds,
            ];
        }

        $allowed = $bucket['count'] < $this->limit;

        if ($allowed) {
            $bucket['count']++;
            $this->writeBucket($file, $bucket);
        }

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $this->limit - (int) $bucket['count']),
            'retry_after' => max(0, (int) $bucket['reset_at'] - $now),
            'reset_at' => (int) $bucket['reset_at'],
        ];
    }

    public static function enforceForCurrentRequest(): void
    {
        if (PHP_SAPI === 'cli' || !self::isApiRequest()) {
            return;
        }

        $limiter = self::fromProjectDefaults();
        $identity = self::currentIdentity();
        $scope = self::currentScope();
        $result = $limiter->attempt($identity, $scope);

        header('X-RateLimit-Limit: ' . self::DEFAULT_LIMIT);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);

        if (!$result['allowed']) {
            header('Retry-After: ' . $result['retry_after']);
            throw new ApiException(
                'Too many requests. Please wait before retrying.',
                429,
                'rate_limit_exceeded'
            );
        }
    }

    public function clear(): void
    {
        if (!is_dir($this->storagePath)) {
            return;
        }

        foreach (glob($this->storagePath . '/*.json') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function isApiRequest(): bool
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

        return str_starts_with($uri, '/api/') || str_contains($script, '/api/');
    }

    private static function currentIdentity(): string
    {
        $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwardedFor !== '') {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private static function currentScope(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: 'unknown';

        return $method . ' ' . $path;
    }

    private function bucketFile(string $identity, string $scope): string
    {
        return $this->storagePath . '/' . hash('sha256', $identity . '|' . $scope) . '.json';
    }

    /**
     * @return array{count:int,reset_at:int}|array{}
     */
    private function readBucket(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return [
            'count' => (int) ($decoded['count'] ?? 0),
            'reset_at' => (int) ($decoded['reset_at'] ?? 0),
        ];
    }

    /**
     * @param array{count:int,reset_at:int} $bucket
     */
    private function writeBucket(string $file, array $bucket): void
    {
        file_put_contents($file, json_encode($bucket, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function ensureStoragePath(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }
}
