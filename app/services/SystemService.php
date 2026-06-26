<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

final class SystemService
{
    public function __construct(private readonly ?PDO $connection = null)
    {
    }

    /** @return array<string, mixed> */
    public function health(): array
    {
        $checks = [
            'php' => $this->checkPhpVersion(),
            'extensions' => $this->checkExtensions(),
            'database' => $this->checkDatabase(),
            'writable_paths' => $this->checkWritablePaths(),
        ];

        $healthy = true;
        foreach ($checks as $check) {
            if (($check['status'] ?? 'fail') !== 'ok') {
                $healthy = false;
                break;
            }
        }

        return [
            'status' => $healthy ? 'ok' : 'degraded',
            'application' => 'Open Manufacturing Engineering Platform',
            'version' => $this->version(),
            'environment' => $this->environment(),
            'checked_at' => gmdate('c'),
            'checks' => $checks,
        ];
    }

    /** @return array<string, mixed> */
    private function checkPhpVersion(): array
    {
        return [
            'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'fail',
            'current' => PHP_VERSION,
            'required' => '>=8.1.0',
        ];
    }

    /** @return array<string, mixed> */
    private function checkExtensions(): array
    {
        $required = ['pdo', 'pdo_mysql', 'json'];
        $missing = [];

        foreach ($required as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return [
            'status' => $missing === [] ? 'ok' : 'fail',
            'required' => $required,
            'missing' => $missing,
        ];
    }

    /** @return array<string, mixed> */
    private function checkDatabase(): array
    {
        if (!$this->connection instanceof PDO) {
            return [
                'status' => 'skipped',
                'message' => 'Database connection was not injected.',
            ];
        }

        try {
            $statement = $this->connection->query('SELECT 1 AS health_check');
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => (($row['health_check'] ?? null) === 1 || ($row['health_check'] ?? null) === '1') ? 'ok' : 'fail',
                'driver' => $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'fail',
                'message' => 'Database query failed.',
            ];
        }
    }

    /** @return array<string, mixed> */
    private function checkWritablePaths(): array
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            'root' => $root,
        ];

        $notWritable = [];
        foreach ($paths as $name => $path) {
            if (!is_writable($path)) {
                $notWritable[] = $name;
            }
        }

        return [
            'status' => $notWritable === [] ? 'ok' : 'fail',
            'checked' => array_keys($paths),
            'not_writable' => $notWritable,
        ];
    }

    private function version(): string
    {
        $versionFile = dirname(__DIR__, 2) . '/VERSION';

        if (is_file($versionFile)) {
            return trim((string) file_get_contents($versionFile));
        }

        return '0.1.0-dev';
    }

    private function environment(): string
    {
        $configFile = dirname(__DIR__, 2) . '/config/config.php';
        if (!is_file($configFile)) {
            return 'unknown';
        }

        $config = require $configFile;

        return (string) ($config['app']['environment'] ?? 'development');
    }
}
