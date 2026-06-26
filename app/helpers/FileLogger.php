<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

final class FileLogger
{
    public function __construct(private readonly string $logDirectory)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function exception(Throwable $throwable, array $context = []): void
    {
        $this->error($throwable->getMessage(), array_merge($context, [
            'exception' => get_class($throwable),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        if (!is_dir($this->logDirectory) && !mkdir($this->logDirectory, 0775, true) && !is_dir($this->logDirectory)) {
            error_log('OpenMEP logger could not create log directory: ' . $this->logDirectory);
            return;
        }

        $payload = [
            'timestamp' => gmdate('c'),
            'level' => $level,
            'request_id' => RequestContext::requestId(),
            'message' => $message,
            'context' => $this->sanitizeContext($context),
        ];

        $file = $this->logDirectory . DIRECTORY_SEPARATOR . 'app-' . gmdate('Y-m-d') . '.log';
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('OpenMEP logger could not write to log file: ' . $file);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $redactedKeys = ['password', 'passwd', 'secret', 'token', 'authorization'];
        $sanitized = [];

        foreach ($context as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, $redactedKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $sanitized[$key] = $value;
                continue;
            }

            $sanitized[$key] = get_debug_type($value);
        }

        return $sanitized;
    }
}
