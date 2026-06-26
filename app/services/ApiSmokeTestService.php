<?php

declare(strict_types=1);

namespace App\Services;

final class ApiSmokeTestService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 5
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaultChecks(bool $skipDatabaseChecks = false): array
    {
        $checks = [
            [
                'name' => 'System health',
                'method' => 'GET',
                'path' => '/api/system/health.php',
                'expected_status' => [200, 503],
                'requires_database' => false,
            ],
            [
                'name' => 'CSRF token endpoint',
                'method' => 'GET',
                'path' => '/api/system/csrf-token.php',
                'expected_status' => [200],
                'requires_database' => false,
            ],
        ];

        if (!$skipDatabaseChecks) {
            $checks[] = [
                'name' => 'Project list endpoint',
                'method' => 'GET',
                'path' => '/api/projects/list.php',
                'expected_status' => [200],
                'requires_database' => true,
            ];
            $checks[] = [
                'name' => 'Dashboard summary endpoint',
                'method' => 'GET',
                'path' => '/api/dashboard/summary.php',
                'expected_status' => [200],
                'requires_database' => true,
            ];
        }

        return $checks;
    }

    public static function normalizeBaseUrl(string $baseUrl): string
    {
        return rtrim(trim($baseUrl), '/');
    }

    public function buildUrl(string $path): string
    {
        return self::normalizeBaseUrl($this->baseUrl) . '/' . ltrim($path, '/');
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @return array{success: bool, total: int, passed: int, failed: int, checks: array<int, array<string, mixed>>}
     */
    public function run(array $checks): array
    {
        $results = [];
        $passed = 0;

        foreach ($checks as $check) {
            $result = $this->runCheck($check);
            if ($result['passed'] === true) {
                $passed++;
            }
            $results[] = $result;
        }

        $total = count($results);

        return [
            'success' => $passed === $total,
            'total' => $total,
            'passed' => $passed,
            'failed' => $total - $passed,
            'checks' => $results,
        ];
    }

    /**
     * @param array<string, mixed> $check
     * @return array<string, mixed>
     */
    private function runCheck(array $check): array
    {
        $method = strtoupper((string) ($check['method'] ?? 'GET'));
        $path = (string) ($check['path'] ?? '/');
        $expectedStatuses = array_map('intval', (array) ($check['expected_status'] ?? [200]));
        $url = $this->buildUrl($path);

        $headers = [
            'Accept: application/json',
            'User-Agent: OpenMEP-ApiSmokeTest/1.0',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $startedAt = microtime(true);
        $responseBody = @file_get_contents($url, false, $context);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($responseBody === false) {
            return [
                'name' => (string) ($check['name'] ?? $path),
                'method' => $method,
                'url' => $url,
                'status' => null,
                'duration_ms' => $durationMs,
                'passed' => false,
                'message' => 'Request failed. Check the base URL and whether the application is reachable.',
            ];
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        $decoded = json_decode($responseBody, true);
        $isJson = is_array($decoded);
        $hasStandardShape = $isJson && array_key_exists('success', $decoded);
        $passed = in_array($statusCode, $expectedStatuses, true) && $hasStandardShape;

        return [
            'name' => (string) ($check['name'] ?? $path),
            'method' => $method,
            'url' => $url,
            'status' => $statusCode,
            'duration_ms' => $durationMs,
            'passed' => $passed,
            'message' => $passed
                ? 'Endpoint returned a standard OpenMEP JSON response.'
                : $this->failureMessage($statusCode, $expectedStatuses, $hasStandardShape),
        ];
    }

    /**
     * @param array<int, string> $headers
     */
    private function extractStatusCode(array $headers): ?int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $expectedStatuses
     */
    private function failureMessage(?int $statusCode, array $expectedStatuses, bool $hasStandardShape): string
    {
        if (!$hasStandardShape) {
            return 'Endpoint did not return the standard OpenMEP JSON response shape.';
        }

        return sprintf(
            'Unexpected HTTP status. Expected %s, received %s.',
            implode('|', $expectedStatuses),
            $statusCode === null ? 'none' : (string) $statusCode
        );
    }
}
