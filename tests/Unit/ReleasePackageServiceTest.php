<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReleasePackageService;
use Tests\Support\TestCase;

final class ReleasePackageServiceTest extends TestCase
{
    public function run(): void
    {
        $root = sys_get_temp_dir() . '/openmep_release_test_' . uniqid('', true);
        mkdir($root . '/config', 0777, true);
        mkdir($root . '/app', 0777, true);
        mkdir($root . '/releases', 0777, true);
        file_put_contents($root . '/VERSION', '9.9.9-test');
        file_put_contents($root . '/README.md', '# Test');
        file_put_contents($root . '/config/config.example.php', '<?php return [];');
        file_put_contents($root . '/config/config.php', '<?php return ["secret" => true];');
        file_put_contents($root . '/app/Test.php', '<?php');
        file_put_contents($root . '/releases/old.zip', 'old');

        $service = new ReleasePackageService();
        $files = array_map(
            static fn ($file): string => str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)),
            $service->collectFiles($root)
        );

        $this->assertTrue(in_array('README.md', $files, true), 'README should be included.');
        $this->assertTrue(in_array('config/config.example.php', $files, true), 'Example config should be included.');
        $this->assertFalse(in_array('config/config.php', $files, true), 'Local config must be excluded.');
        $this->assertFalse(in_array('releases/old.zip', $files, true), 'Previous releases must be excluded.');

        $output = $root . '/dist';
        $result = $service->build($root, $output);
        $this->assertTrue(is_file($result['archive']), 'Archive should be created.');
        $this->assertTrue(is_file($result['checksum']), 'Checksum should be created.');
        $this->assertSame('9.9.9-test', $result['version'], 'Version should be read from VERSION.');
        $this->assertTrue($result['files'] >= 4, 'Archive should contain project files.');

        $this->deleteDirectory($root);
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
