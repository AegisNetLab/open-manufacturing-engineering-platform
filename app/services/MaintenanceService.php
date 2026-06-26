<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DirectoryIterator;
use RuntimeException;

final class MaintenanceService
{
    /**
     * @return list<array{file:string,path:string,bytes:int,created_at:string,age_days:int}>
     */
    public function listBackupFiles(string $backupDirectory): array
    {
        if (!is_dir($backupDirectory)) {
            return [];
        }

        $files = [];
        $now = time();

        foreach (new DirectoryIterator($backupDirectory) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            if (!str_ends_with($entry->getFilename(), '.sql')) {
                continue;
            }

            $modifiedAt = $entry->getMTime();
            $files[] = [
                'file' => $entry->getFilename(),
                'path' => $entry->getPathname(),
                'bytes' => $entry->getSize(),
                'created_at' => (new DateTimeImmutable('@' . $modifiedAt))->format(DATE_ATOM),
                'age_days' => max(0, (int) floor(($now - $modifiedAt) / 86400)),
            ];
        }

        usort(
            $files,
            static fn (array $left, array $right): int => strcmp($right['created_at'], $left['created_at'])
        );

        return $files;
    }

    /**
     * @return array{total_files:int,total_bytes:int,delete_candidates:list<array{file:string,path:string,bytes:int,created_at:string,age_days:int}>}
     */
    public function planBackupCleanup(string $backupDirectory, int $retentionDays, int $minimumFilesToKeep = 1): array
    {
        if ($retentionDays < 0) {
            throw new RuntimeException('Retention days cannot be negative.');
        }

        if ($minimumFilesToKeep < 0) {
            throw new RuntimeException('Minimum files to keep cannot be negative.');
        }

        $files = $this->listBackupFiles($backupDirectory);
        $totalBytes = array_sum(array_map(static fn (array $file): int => $file['bytes'], $files));
        $deleteCandidates = [];

        foreach ($files as $index => $file) {
            if ($index < $minimumFilesToKeep) {
                continue;
            }

            if ($file['age_days'] >= $retentionDays) {
                $deleteCandidates[] = $file;
            }
        }

        return [
            'total_files' => count($files),
            'total_bytes' => $totalBytes,
            'delete_candidates' => $deleteCandidates,
        ];
    }

    /**
     * @return array{deleted_files:int,deleted_bytes:int,files:list<string>}
     */
    public function cleanupBackups(
        string $backupDirectory,
        int $retentionDays,
        int $minimumFilesToKeep = 1,
        bool $dryRun = false
    ): array {
        $plan = $this->planBackupCleanup($backupDirectory, $retentionDays, $minimumFilesToKeep);
        $deletedFiles = [];
        $deletedBytes = 0;

        foreach ($plan['delete_candidates'] as $candidate) {
            $deletedFiles[] = $candidate['file'];
            $deletedBytes += $candidate['bytes'];

            if (!$dryRun && is_file($candidate['path']) && !unlink($candidate['path'])) {
                throw new RuntimeException('Backup file could not be deleted: ' . $candidate['file']);
            }
        }

        return [
            'deleted_files' => count($deletedFiles),
            'deleted_bytes' => $deletedBytes,
            'files' => $deletedFiles,
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        foreach ($units as $unit) {
            if ($value < 1024) {
                return number_format($value, 2) . ' ' . $unit;
            }
            $value /= 1024;
        }

        return number_format($value, 2) . ' PB';
    }
}
