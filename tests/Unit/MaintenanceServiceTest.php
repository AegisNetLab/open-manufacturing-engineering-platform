<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MaintenanceService;
use Tests\Support\TestCase;

final class MaintenanceServiceTest extends TestCase
{
    public function run(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'openmep-maintenance-' . uniqid('', true);
        mkdir($directory, 0775, true);

        $newest = $directory . DIRECTORY_SEPARATOR . 'openmep-backup-new.sql';
        $oldOne = $directory . DIRECTORY_SEPARATOR . 'openmep-backup-old-one.sql';
        $oldTwo = $directory . DIRECTORY_SEPARATOR . 'openmep-backup-old-two.sql';
        $ignored = $directory . DIRECTORY_SEPARATOR . 'notes.txt';

        file_put_contents($newest, 'new');
        file_put_contents($oldOne, str_repeat('a', 2048));
        file_put_contents($oldTwo, 'old');
        file_put_contents($ignored, 'ignore');

        touch($newest, time());
        touch($oldOne, time() - 40 * 86400);
        touch($oldTwo, time() - 50 * 86400);

        $service = new MaintenanceService();
        $files = $service->listBackupFiles($directory);
        $this->assertSame(3, count($files));

        $plan = $service->planBackupCleanup($directory, 30, 1);
        $this->assertSame(3, $plan['total_files']);
        $this->assertSame(2, count($plan['delete_candidates']));

        $dryRun = $service->cleanupBackups($directory, 30, 1, true);
        $this->assertSame(2, $dryRun['deleted_files']);
        $this->assertTrue(is_file($oldOne));

        $result = $service->cleanupBackups($directory, 30, 1, false);
        $this->assertSame(2, $result['deleted_files']);
        $this->assertTrue(is_file($newest));
        $this->assertFalse(is_file($oldOne));
        $this->assertFalse(is_file($oldTwo));
        $this->assertTrue(is_file($ignored));
        $this->assertSame('2.00 KB', $service->formatBytes(2048));

        @unlink($newest);
        @unlink($ignored);
        @rmdir($directory);
    }
}
