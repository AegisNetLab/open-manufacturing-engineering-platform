<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array<string,int> */
    public function metrics(): array
    {
        return [
            'projects' => $this->countTable('projects'),
            'layout_elements' => $this->countTable('layout_elements'),
            'operations' => $this->countTable('operations'),
            'resources' => $this->countTable('resources'),
            'simulation_runs' => $this->countTable('simulation_runs'),
            'simulation_results' => $this->countTable('simulation_results'),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function recentProjects(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, description, production_type, shift_length_minutes, created_at, updated_at
             FROM projects
             ORDER BY updated_at DESC, id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min(20, $limit)), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function latestSimulationResults(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            'SELECT p.id AS project_id,
                    p.name AS project_name,
                    sr.id AS run_id,
                    sr.finished_at,
                    ss.name AS scenario_name,
                    res.throughput_per_hour,
                    res.average_lead_time_minutes,
                    res.average_wip,
                    res.resource_utilization_percent,
                    res.oee_percent
             FROM simulation_results res
             INNER JOIN simulation_runs sr ON sr.id = res.simulation_run_id
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             INNER JOIN projects p ON p.id = ss.project_id
             ORDER BY sr.finished_at DESC, sr.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min(20, $limit)), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function projectReadinessSummary(int $limit = 5): array
    {
        $statement = $this->connection->prepare(
            'SELECT p.id,
                    p.name,
                    COUNT(DISTINCT le.id) AS layout_elements,
                    COUNT(DISTINCT r.id) AS resources,
                    COUNT(DISTINCT o.id) AS operations,
                    COUNT(DISTINCT pc.id) AS connections,
                    COUNT(DISTINCT ss.id) AS scenarios
             FROM projects p
             LEFT JOIN layout_elements le ON le.project_id = p.id
             LEFT JOIN resources r ON r.project_id = p.id
             LEFT JOIN operations o ON o.project_id = p.id
             LEFT JOIN process_connections pc ON pc.source_operation_id = o.id
             LEFT JOIN simulation_scenarios ss ON ss.project_id = p.id
             GROUP BY p.id, p.name
             ORDER BY p.updated_at DESC, p.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min(20, $limit)), PDO::PARAM_INT);
        $statement->execute();

        return array_map(static function (array $row): array {
            $layoutElements = (int) $row['layout_elements'];
            $resources = (int) $row['resources'];
            $operations = (int) $row['operations'];
            $connections = (int) $row['connections'];
            $scenarios = (int) $row['scenarios'];

            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'layout_elements' => $layoutElements,
                'resources' => $resources,
                'operations' => $operations,
                'connections' => $connections,
                'scenarios' => $scenarios,
                'ready' => $layoutElements > 0 && $resources > 0 && $operations > 1 && $connections > 0 && $scenarios > 0,
            ];
        }, $statement->fetchAll());
    }

    private function countTable(string $tableName): int
    {
        $allowedTables = [
            'projects',
            'layout_elements',
            'operations',
            'resources',
            'simulation_runs',
            'simulation_results',
        ];

        if (!in_array($tableName, $allowedTables, true)) {
            return 0;
        }

        return (int) $this->connection->query('SELECT COUNT(*) FROM ' . $tableName)->fetchColumn();
    }
}
