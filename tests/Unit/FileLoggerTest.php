<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\FileLogger;
use App\Helpers\RequestContext;
use Tests\Support\TestCase;

final class FileLoggerTest extends TestCase
{
    public function run(): void
    {
        $directory = sys_get_temp_dir() . '/openmep-logger-test-' . uniqid('', true);
        RequestContext::reset('test-request-123');

        $logger = new FileLogger($directory);
        $logger->info('User login failed', [
            'user' => 'demo',
            'password' => 'secret-value',
            'nested' => [
                'token' => 'abc',
                'safe' => 'value',
            ],
        ]);

        $file = $directory . '/app-' . gmdate('Y-m-d') . '.log';
        $this->assertTrue(is_file($file), 'Log file should be created.');

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertSame(1, count($lines));

        $entry = json_decode($lines[0], true);
        $this->assertTrue(is_array($entry), 'Log entry should be valid JSON.');
        $this->assertSame('INFO', $entry['level']);
        $this->assertSame('test-request-123', $entry['request_id']);
        $this->assertSame('User login failed', $entry['message']);
        $this->assertSame('[redacted]', $entry['context']['password']);
        $this->assertSame('[redacted]', $entry['context']['nested']['token']);
        $this->assertSame('value', $entry['context']['nested']['safe']);

        @unlink($file);
        @rmdir($directory);
        RequestContext::reset();
    }
}
