<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(private readonly DashboardRepository $repository)
    {
    }

    /** @return array<string,mixed> */
    public function summary(): array
    {
        return [
            'metrics' => $this->repository->metrics(),
            'recent_projects' => $this->repository->recentProjects(),
            'latest_results' => $this->repository->latestSimulationResults(),
            'readiness' => $this->repository->projectReadinessSummary(),
        ];
    }
}
