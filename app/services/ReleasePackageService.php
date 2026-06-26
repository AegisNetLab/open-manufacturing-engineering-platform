<?php

declare(strict_types=1);

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ReleasePackageService
{
    /**
     * @param list<string> $excludePatterns
     * @return array{archive:string, checksum:string, files:int, bytes:int, version:string}
     */
    public function build(string $projectRoot, string $outputDir, ?string $version = null, array $excludePatterns = []): array
    {
        $projectRoot = rtrim(realpath($projectRoot) ?: $projectRoot, DIRECTORY_SEPARATOR);
        if (!is_dir($projectRoot)) {
            throw new RuntimeException('Project root does not exist.');
        }

        $outputDir = rtrim($outputDir, DIRECTORY_SEPARATOR);
        if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            throw new RuntimeException('Output directory could not be created.');
        }

        $version = $version ?: $this->readVersion($projectRoot);
        $archiveName = 'open-manufacturing-engineering-platform-' . $this->sanitizeVersion($version) . '.zip';
        $archivePath = $outputDir . DIRECTORY_SEPARATOR . $archiveName;
        $checksumPath = $archivePath . '.sha256';

        if (is_file($archivePath)) {
            unlink($archivePath);
        }
        if (is_file($checksumPath)) {
            unlink($checksumPath);
        }

        $collectedFiles = $this->collectFiles($projectRoot, $excludePatterns);
        $files = count($collectedFiles);
        $bytes = array_sum(array_map(static fn (SplFileInfo $file): int => $file->getSize(), $collectedFiles));

        if (class_exists('ZipArchive')) {
            $this->writeWithZipArchive($archivePath, $projectRoot, $collectedFiles);
        } else {
            $this->writeWithCliZip($archivePath, $projectRoot, $collectedFiles);
        }

        $checksum = hash_file('sha256', $archivePath);
        file_put_contents($checksumPath, $checksum . '  ' . basename($archivePath) . PHP_EOL);

        return [
            'archive' => $archivePath,
            'checksum' => $checksumPath,
            'files' => $files,
            'bytes' => $bytes,
            'version' => $version,
        ];
    }


    /** @param list<SplFileInfo> $files */
    private function writeWithZipArchive(string $archivePath, string $projectRoot, array $files): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Release archive could not be opened for writing.');
        }

        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));
            $zip->addFile($file->getPathname(), 'open-manufacturing-engineering-platform/' . $relative);
        }

        $zip->close();
    }

    /** @param list<SplFileInfo> $files */
    private function writeWithCliZip(string $archivePath, string $projectRoot, array $files): void
    {
        $zipBinary = trim((string) shell_exec('command -v zip 2>/dev/null'));
        if ($zipBinary === '') {
            throw new RuntimeException('Neither PHP ZipArchive nor the zip command is available.');
        }

        $stagingRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'openmep_release_' . bin2hex(random_bytes(8));
        $packageRoot = $stagingRoot . DIRECTORY_SEPARATOR . 'open-manufacturing-engineering-platform';
        if (!mkdir($packageRoot, 0775, true) && !is_dir($packageRoot)) {
            throw new RuntimeException('Release staging directory could not be created.');
        }

        try {
            foreach ($files as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));
                $target = $packageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                $targetDir = dirname($target);
                if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                    throw new RuntimeException('Release staging subdirectory could not be created.');
                }
                copy($file->getPathname(), $target);
            }

            $command = 'cd ' . escapeshellarg($stagingRoot) . ' && ' . escapeshellarg($zipBinary)
                . ' -qr ' . escapeshellarg($archivePath) . ' open-manufacturing-engineering-platform';
            exec($command, $output, $exitCode);
            if ($exitCode !== 0) {
                throw new RuntimeException('The zip command failed while creating the release archive.');
            }
        } finally {
            $this->deleteDirectory($stagingRoot);
        }
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

    /**
     * @param list<string> $excludePatterns
     * @return list<SplFileInfo>
     */
    public function collectFiles(string $projectRoot, array $excludePatterns = []): array
    {
        $defaultPatterns = [
            '#(^|/)\.git(/|$)#',
            '#(^|/)node_modules(/|$)#',
            '#(^|/)vendor(/|$)#',
            '#(^|/)releases(/|$)#',
            '#(^|/)\.DS_Store$#',
            '#(^|/)config/config\.php$#',
            '#(^|/)\.env$#',
            '#(^|/)tmp(/|$)#',
            '#(^|/)storage(/|$)#',
        ];
        $patterns = array_merge($defaultPatterns, $excludePatterns);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));
            if ($this->isExcluded($relative, $patterns)) {
                continue;
            }

            $files[] = $file;
        }

        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    /** @param list<string> $patterns */
    private function isExcluded(string $relativePath, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $relativePath) === 1) {
                return true;
            }
        }

        return false;
    }

    private function readVersion(string $projectRoot): string
    {
        $versionFile = $projectRoot . DIRECTORY_SEPARATOR . 'VERSION';
        if (!is_file($versionFile)) {
            return '0.1.0-dev';
        }

        $version = trim((string) file_get_contents($versionFile));

        return $version !== '' ? $version : '0.1.0-dev';
    }

    private function sanitizeVersion(string $version): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '-', $version) ?: 'dev';
    }
}
