<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectDiagnosticsRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function projectExists(int $projectId): bool
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM projects WHERE id = :id');
        $statement->execute(['id' => $projectId]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @return array<string, int>
     */
    public function collectCounts(int $projectId): array
    {
        return [
            'layout_elements' => $this->countByProject('layout_elements', $projectId),
            'resources' => $this->countByProject('resources', $projectId),
            'operations' => $this->countByProject('operations', $projectId),
            'process_connections' => $this->countProcessConnections($projectId),
            'simulation_scenarios' => $this->countByProject('simulation_scenarios', $projectId),
            'simulation_runs' => $this->countSimulationRuns($projectId),
            'simulation_results' => $this->countSimulationResults($projectId),
        ];
    }

    private function countByProject(string $table, int $projectId): int
    {
        $allowedTables = ['layout_elements', 'resources', 'operations', 'simulation_scenarios'];
        if (!in_array($table, $allowedTables, true)) {
            return 0;
        }

        $statement = $this->connection->prepare("SELECT COUNT(*) FROM {$table} WHERE project_id = :project_id");
        $statement->execute(['project_id' => $projectId]);

        return (int) $statement->fetchColumn();
    }

    private function countProcessConnections(int $projectId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM process_connections pc
             INNER JOIN operations source_op ON source_op.id = pc.source_operation_id
             WHERE source_op.project_id = :project_id'
        );
        $statement->execute(['project_id' => $projectId]);

        return (int) $statement->fetchColumn();
    }

    private function countSimulationRuns(int $projectId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM simulation_runs sr
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             WHERE ss.project_id = :project_id'
        );
        $statement->execute(['project_id' => $projectId]);

        return (int) $statement->fetchColumn();
    }

    private function countSimulationResults(int $projectId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM simulation_results result
             INNER JOIN simulation_runs run ON run.id = result.simulation_run_id
             INNER JOIN simulation_scenarios scenario ON scenario.id = run.scenario_id
             WHERE scenario.project_id = :project_id'
        );
        $statement->execute(['project_id' => $projectId]);

        return (int) $statement->fetchColumn();
    }
}
