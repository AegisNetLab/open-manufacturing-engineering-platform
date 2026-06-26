<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProjectPackageRepository;
use InvalidArgumentException;

final class ProjectPackageService
{
    public function __construct(private readonly ProjectPackageRepository $repository)
    {
    }

    public function exportProject(int $projectId): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Project ID is required.');
        }

        $package = $this->repository->exportProject($projectId);
        if ($package === null) {
            throw new InvalidArgumentException('Project not found.');
        }

        return $package;
    }

    public function importProject(array $package): int
    {
        $this->validatePackage($package);

        return $this->repository->importProject($package);
    }

    public function resultsCsv(int $projectId): string
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Project ID is required.');
        }

        $rows = $this->repository->resultsCsv($projectId);
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new InvalidArgumentException('Unable to create CSV stream.');
        }

        fputcsv($stream, [
            'run_id',
            'scenario_name',
            'status',
            'started_at',
            'finished_at',
            'duration_minutes',
            'arrival_rate',
            'random_seed',
            'throughput_per_hour',
            'average_lead_time_minutes',
            'average_wip',
            'resource_utilization_percent',
            'oee_percent',
        ]);

        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['run_id'],
                $row['scenario_name'],
                $row['status'],
                $row['started_at'],
                $row['finished_at'],
                $row['duration_minutes'],
                $row['arrival_rate'],
                $row['random_seed'],
                $row['throughput_per_hour'],
                $row['average_lead_time_minutes'],
                $row['average_wip'],
                $row['resource_utilization_percent'],
                $row['oee_percent'],
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
    }

    private function validatePackage(array $package): void
    {
        if (($package['format'] ?? '') !== 'openmep.project-package') {
            throw new InvalidArgumentException('Unsupported package format.');
        }

        if ((int) ($package['format_version'] ?? 0) !== 1) {
            throw new InvalidArgumentException('Unsupported package version.');
        }

        if (!isset($package['project']) || !is_array($package['project'])) {
            throw new InvalidArgumentException('Package project section is missing.');
        }

        if (trim((string) ($package['project']['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Package project name is required.');
        }
    }
}
