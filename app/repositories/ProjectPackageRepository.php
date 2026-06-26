<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectPackageRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function exportProject(int $projectId): ?array
    {
        $project = $this->fetchOne(
            'SELECT id, name, description, production_type, shift_length_minutes, created_at, updated_at FROM projects WHERE id = :id',
            ['id' => $projectId]
        );

        if ($project === null) {
            return null;
        }

        $layoutElements = $this->fetchAll(
            'SELECT id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json
             FROM layout_elements WHERE project_id = :project_id ORDER BY id ASC',
            ['project_id' => $projectId]
        );

        $resources = $this->fetchAll(
            'SELECT id, name, resource_type, quantity, metadata_json
             FROM resources WHERE project_id = :project_id ORDER BY id ASC',
            ['project_id' => $projectId]
        );

        $operations = $this->fetchAll(
            'SELECT id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds, batch_size,
                    scrap_rate, rework_rate, metadata_json
             FROM operations WHERE project_id = :project_id ORDER BY id ASC',
            ['project_id' => $projectId]
        );

        $operationIds = array_map(static fn (array $row): int => (int) $row['id'], $operations);
        $connections = [];
        $operationResources = [];

        if ($operationIds !== []) {
            $placeholders = implode(',', array_fill(0, count($operationIds), '?'));
            $connections = $this->fetchAll(
                "SELECT id, source_operation_id, target_operation_id, connection_type, probability, metadata_json
                 FROM process_connections WHERE source_operation_id IN ($placeholders) ORDER BY id ASC",
                $operationIds
            );

            $operationResources = $this->fetchAll(
                "SELECT operation_id, resource_id, required_quantity
                 FROM operation_resources WHERE operation_id IN ($placeholders) ORDER BY operation_id ASC, resource_id ASC",
                $operationIds
            );
        }

        $scenarios = $this->fetchAll(
            'SELECT id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at
             FROM simulation_scenarios WHERE project_id = :project_id ORDER BY id ASC',
            ['project_id' => $projectId]
        );

        return [
            'format' => 'openmep.project-package',
            'format_version' => 1,
            'exported_at' => gmdate('c'),
            'project' => $this->normalizeRow($project),
            'layout_elements' => array_map(fn (array $row): array => $this->normalizeRow($row), $layoutElements),
            'resources' => array_map(fn (array $row): array => $this->normalizeRow($row), $resources),
            'operations' => array_map(fn (array $row): array => $this->normalizeRow($row), $operations),
            'process_connections' => array_map(fn (array $row): array => $this->normalizeRow($row), $connections),
            'operation_resources' => array_map(fn (array $row): array => $this->normalizeRow($row), $operationResources),
            'simulation_scenarios' => array_map(fn (array $row): array => $this->normalizeRow($row), $scenarios),
        ];
    }

    public function importProject(array $package): int
    {
        $this->connection->beginTransaction();

        try {
            $project = $package['project'];
            $statement = $this->connection->prepare(
                'INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
                 VALUES (:name, :description, :production_type, :shift_length_minutes, NOW(), NOW())'
            );
            $statement->execute([
                'name' => $this->importedName((string) $project['name']),
                'description' => $project['description'] ?? null,
                'production_type' => $project['production_type'] ?? 'serial',
                'shift_length_minutes' => (int) ($project['shift_length_minutes'] ?? 480),
            ]);
            $projectId = (int) $this->connection->lastInsertId();

            $layoutMap = $this->importLayoutElements($projectId, $package['layout_elements'] ?? []);
            $resourceMap = $this->importResources($projectId, $package['resources'] ?? []);
            $operationMap = $this->importOperations($projectId, $package['operations'] ?? [], $layoutMap);
            $this->importConnections($package['process_connections'] ?? [], $operationMap);
            $this->importOperationResources($package['operation_resources'] ?? [], $operationMap, $resourceMap);
            $this->importScenarios($projectId, $package['simulation_scenarios'] ?? []);

            $this->connection->commit();
            return $projectId;
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }

    public function resultsCsv(int $projectId): array
    {
        return $this->fetchAll(
            'SELECT sr.id AS run_id, ss.name AS scenario_name, sr.status, sr.started_at, sr.finished_at,
                    ss.duration_minutes, ss.arrival_rate, ss.random_seed,
                    res.throughput_per_hour, res.average_lead_time_minutes, res.average_wip,
                    res.resource_utilization_percent, res.oee_percent
             FROM simulation_results res
             INNER JOIN simulation_runs sr ON sr.id = res.simulation_run_id
             INNER JOIN simulation_scenarios ss ON ss.id = sr.scenario_id
             WHERE ss.project_id = :project_id
             ORDER BY sr.id DESC',
            ['project_id' => $projectId]
        );
    }

    /** @return array<string, mixed>|null */
    private function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($key === 'metadata_json') {
                $row['metadata'] = json_decode((string) ($value ?? '{}'), true) ?: [];
                unset($row[$key]);
                continue;
            }

            if (is_numeric($value) && !str_contains((string) $value, '.')) {
                $row[$key] = (int) $value;
            } elseif (is_numeric($value)) {
                $row[$key] = (float) $value;
            }
        }

        return $row;
    }

    private function importedName(string $name): string
    {
        $suffix = ' (Imported ' . gmdate('Y-m-d H:i') . ' UTC)';
        return substr($name . $suffix, 0, 150);
    }

    private function encodeMetadata(array $row): string
    {
        return json_encode($row['metadata'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function importLayoutElements(int $projectId, array $rows): array
    {
        $map = [];
        $statement = $this->connection->prepare(
            'INSERT INTO layout_elements
                (project_id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :element_type, :x_position, :y_position, :width, :height, :rotation, :color, :metadata_json, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $row['name'],
                'element_type' => $row['element_type'],
                'x_position' => $row['x_position'],
                'y_position' => $row['y_position'],
                'width' => $row['width'],
                'height' => $row['height'],
                'rotation' => $row['rotation'] ?? 0,
                'color' => $row['color'] ?? null,
                'metadata_json' => $this->encodeMetadata($row),
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    private function importResources(int $projectId, array $rows): array
    {
        $map = [];
        $statement = $this->connection->prepare(
            'INSERT INTO resources (project_id, name, resource_type, quantity, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :resource_type, :quantity, :metadata_json, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $row['name'],
                'resource_type' => $row['resource_type'],
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'metadata_json' => $this->encodeMetadata($row),
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    private function importOperations(int $projectId, array $rows, array $layoutMap): array
    {
        $map = [];
        $statement = $this->connection->prepare(
            'INSERT INTO operations
                (project_id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds, batch_size,
                 scrap_rate, rework_rate, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :operation_code, :name, :linked_layout_element_id, :cycle_time_seconds, :setup_time_seconds,
                 :batch_size, :scrap_rate, :rework_rate, :metadata_json, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $oldLayoutId = (int) ($row['linked_layout_element_id'] ?? 0);
            $statement->execute([
                'project_id' => $projectId,
                'operation_code' => $row['operation_code'],
                'name' => $row['name'],
                'linked_layout_element_id' => $oldLayoutId > 0 ? ($layoutMap[$oldLayoutId] ?? null) : null,
                'cycle_time_seconds' => (float) ($row['cycle_time_seconds'] ?? 0),
                'setup_time_seconds' => (float) ($row['setup_time_seconds'] ?? 0),
                'batch_size' => max(1, (int) ($row['batch_size'] ?? 1)),
                'scrap_rate' => (float) ($row['scrap_rate'] ?? 0),
                'rework_rate' => (float) ($row['rework_rate'] ?? 0),
                'metadata_json' => $this->encodeMetadata($row),
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    private function importConnections(array $rows, array $operationMap): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO process_connections (source_operation_id, target_operation_id, connection_type, probability, metadata_json)
             VALUES (:source_operation_id, :target_operation_id, :connection_type, :probability, :metadata_json)'
        );

        foreach ($rows as $row) {
            $oldSource = (int) $row['source_operation_id'];
            $oldTarget = (int) $row['target_operation_id'];
            if (!isset($operationMap[$oldSource], $operationMap[$oldTarget])) {
                continue;
            }

            $statement->execute([
                'source_operation_id' => $operationMap[$oldSource],
                'target_operation_id' => $operationMap[$oldTarget],
                'connection_type' => $row['connection_type'] ?? 'normal',
                'probability' => (float) ($row['probability'] ?? 100),
                'metadata_json' => $this->encodeMetadata($row),
            ]);
        }
    }

    private function importOperationResources(array $rows, array $operationMap, array $resourceMap): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO operation_resources (operation_id, resource_id, required_quantity)
             VALUES (:operation_id, :resource_id, :required_quantity)'
        );

        foreach ($rows as $row) {
            $oldOperation = (int) $row['operation_id'];
            $oldResource = (int) $row['resource_id'];
            if (!isset($operationMap[$oldOperation], $resourceMap[$oldResource])) {
                continue;
            }

            $statement->execute([
                'operation_id' => $operationMap[$oldOperation],
                'resource_id' => $resourceMap[$oldResource],
                'required_quantity' => max(1, (int) ($row['required_quantity'] ?? 1)),
            ]);
        }
    }

    private function importScenarios(int $projectId, array $rows): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO simulation_scenarios
                (project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :duration_minutes, :arrival_rate, :random_seed, :metadata_json, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $row['name'],
                'duration_minutes' => max(1, (int) ($row['duration_minutes'] ?? 480)),
                'arrival_rate' => (float) ($row['arrival_rate'] ?? 1),
                'random_seed' => isset($row['random_seed']) ? (int) $row['random_seed'] : null,
                'metadata_json' => $this->encodeMetadata($row),
            ]);
        }
    }
}
