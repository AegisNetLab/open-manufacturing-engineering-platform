<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SimulationRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function loadModel(int $projectId): array
    {
        $operationsStatement = $this->connection->prepare(
            'SELECT * FROM operations WHERE project_id = :project_id ORDER BY id ASC'
        );
        $operationsStatement->execute(['project_id' => $projectId]);
        $operations = [];
        foreach ($operationsStatement->fetchAll() as $row) {
            $metadata = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];
            $operations[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'operation_code' => (string) $row['operation_code'],
                'name' => (string) $row['name'],
                'node_type' => (string) ($metadata['node_type'] ?? 'operation'),
                'cycle_time_seconds' => (float) $row['cycle_time_seconds'],
                'setup_time_seconds' => (float) $row['setup_time_seconds'],
                'batch_size' => max(1, (int) $row['batch_size']),
                'scrap_rate' => (float) $row['scrap_rate'],
                'rework_rate' => (float) $row['rework_rate'],
                'mtbf_hours' => (float) ($metadata['mtbf_hours'] ?? 0),
                'mttr_hours' => (float) ($metadata['mttr_hours'] ?? 0),
                'resource_id' => !empty($metadata['resource_id']) ? (int) $metadata['resource_id'] : null,
                'resource_name' => (string) ($metadata['resource_name'] ?? $metadata['resource'] ?? ''),
                'required_quantity' => max(1, (int) ($metadata['required_quantity'] ?? 1)),
            ];
        }

        $connections = [];
        if ($operations !== []) {
            $placeholders = implode(',', array_fill(0, count($operations), '?'));
            $connectionsStatement = $this->connection->prepare(
                "SELECT * FROM process_connections WHERE source_operation_id IN ($placeholders) ORDER BY id ASC"
            );
            $connectionsStatement->execute(array_keys($operations));
            foreach ($connectionsStatement->fetchAll() as $row) {
                $connections[] = [
                    'source_operation_id' => (int) $row['source_operation_id'],
                    'target_operation_id' => (int) $row['target_operation_id'],
                    'connection_type' => (string) $row['connection_type'],
                    'probability' => (float) $row['probability'],
                ];
            }
        }

        $resourcesStatement = $this->connection->prepare(
            'SELECT * FROM resources WHERE project_id = :project_id ORDER BY id ASC'
        );
        $resourcesStatement->execute(['project_id' => $projectId]);
        $resources = [];
        foreach ($resourcesStatement->fetchAll() as $row) {
            $resources[(string) $row['name']] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'resource_type' => (string) $row['resource_type'],
                'quantity' => max(1, (int) $row['quantity']),
                'metadata' => json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [],
            ];
        }

        if ($operations !== []) {
            $placeholders = implode(',', array_fill(0, count($operations), '?'));
            $assignmentStatement = $this->connection->prepare(
                "SELECT opr.operation_id, opr.resource_id, opr.required_quantity, r.name AS resource_name
                 FROM operation_resources opr
                 INNER JOIN resources r ON r.id = opr.resource_id
                 WHERE opr.operation_id IN ($placeholders)
                 ORDER BY opr.operation_id ASC, opr.id ASC"
            );
            $assignmentStatement->execute(array_keys($operations));
            foreach ($assignmentStatement->fetchAll() as $row) {
                $operationId = (int) $row['operation_id'];
                if (!isset($operations[$operationId])) {
                    continue;
                }
                $operations[$operationId]['resource_id'] = (int) $row['resource_id'];
                $operations[$operationId]['resource_name'] = (string) $row['resource_name'];
                $operations[$operationId]['required_quantity'] = max(1, (int) $row['required_quantity']);
            }
        }

        return ['operations' => array_values($operations), 'operations_by_id' => $operations, 'connections' => $connections, 'resources' => $resources];
    }

    public function createScenario(array $scenario): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO simulation_scenarios (project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :duration_minutes, :arrival_rate, :random_seed, :metadata_json, NOW(), NOW())'
        );
        $statement->execute([
            'project_id' => $scenario['project_id'],
            'name' => $scenario['name'],
            'duration_minutes' => $scenario['duration_minutes'],
            'arrival_rate' => $scenario['arrival_rate'],
            'random_seed' => $scenario['random_seed'],
            'metadata_json' => json_encode($scenario['metadata'] ?? [], JSON_THROW_ON_ERROR),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function createRun(int $scenarioId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO simulation_runs (scenario_id, status, started_at) VALUES (:scenario_id, :status, NOW())'
        );
        $statement->execute(['scenario_id' => $scenarioId, 'status' => 'running']);

        return (int) $this->connection->lastInsertId();
    }

    public function completeRun(int $runId, array $result): void
    {
        $this->connection->beginTransaction();
        try {
            $updateRun = $this->connection->prepare(
                'UPDATE simulation_runs SET status = :status, finished_at = NOW() WHERE id = :id'
            );
            $updateRun->execute(['id' => $runId, 'status' => 'completed']);

            $insertResult = $this->connection->prepare(
                'INSERT INTO simulation_results
                    (simulation_run_id, throughput_per_hour, average_lead_time_minutes, average_wip, resource_utilization_percent, oee_percent, metadata_json, created_at)
                 VALUES
                    (:simulation_run_id, :throughput_per_hour, :average_lead_time_minutes, :average_wip, :resource_utilization_percent, :oee_percent, :metadata_json, NOW())'
            );
            $insertResult->execute([
                'simulation_run_id' => $runId,
                'throughput_per_hour' => $result['throughput_per_hour'],
                'average_lead_time_minutes' => $result['average_lead_time_minutes'],
                'average_wip' => $result['average_wip'],
                'resource_utilization_percent' => $result['resource_utilization_percent'],
                'oee_percent' => $result['oee_percent'],
                'metadata_json' => json_encode($result['metadata'], JSON_THROW_ON_ERROR),
            ]);
            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }

    public function latestRun(int $projectId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT sr.id, sr.status, sr.started_at, sr.finished_at, ss.name AS scenario_name
             FROM simulation_runs sr
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             WHERE ss.project_id = :project_id
             ORDER BY sr.id DESC
             LIMIT 1'
        );
        $statement->execute(['project_id' => $projectId]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function resultsByProject(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT sr.id AS run_id, sr.status, sr.started_at, sr.finished_at, ss.name AS scenario_name,
                    ss.duration_minutes, ss.arrival_rate, res.*
             FROM simulation_results res
             INNER JOIN simulation_runs sr ON sr.id = res.simulation_run_id
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             WHERE ss.project_id = :project_id
             ORDER BY sr.id DESC
             LIMIT 20'
        );
        $statement->execute(['project_id' => $projectId]);

        return array_map(static function (array $row): array {
            $row['metadata'] = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];
            unset($row['metadata_json']);
            return $row;
        }, $statement->fetchAll());
    }

    public function latestResultByProject(int $projectId): ?array
    {
        $results = $this->resultsByProject($projectId);

        return $results[0] ?? null;
    }

    public function resultByProjectAndRun(int $projectId, int $runId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT sr.id AS run_id, sr.status, sr.started_at, sr.finished_at, ss.name AS scenario_name,
                    ss.duration_minutes, ss.arrival_rate, res.*
             FROM simulation_results res
             INNER JOIN simulation_runs sr ON sr.id = res.simulation_run_id
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             WHERE ss.project_id = :project_id AND sr.id = :run_id
             LIMIT 1'
        );
        $statement->execute(['project_id' => $projectId, 'run_id' => $runId]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        $row['metadata'] = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];
        unset($row['metadata_json']);

        return $row;
    }

}
