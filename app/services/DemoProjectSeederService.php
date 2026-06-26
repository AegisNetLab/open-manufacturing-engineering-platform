<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

final class DemoProjectSeederService
{
    public const DEFAULT_PROJECT_NAME = 'Demo: Automotive Assembly Line';

    /**
     * @return array<string, int|string>
     */
    public function buildPlan(string $projectName = self::DEFAULT_PROJECT_NAME): array
    {
        return [
            'project_name' => $projectName,
            'projects' => 1,
            'layout_elements' => count($this->layoutElements()),
            'resources' => count($this->resources()),
            'operations' => count($this->operations()),
            'connections' => count($this->connections()),
            'scenarios' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function seed(PDO $connection, string $projectName = self::DEFAULT_PROJECT_NAME, bool $force = false): array
    {
        $connection->beginTransaction();

        try {
            $existingProjectId = $this->findProjectIdByName($connection, $projectName);
            if ($existingProjectId !== null) {
                if (!$force) {
                    throw new RuntimeException(
                        'Demo project already exists. Re-run with --force to replace it.'
                    );
                }

                $delete = $connection->prepare('DELETE FROM projects WHERE id = :id');
                $delete->execute(['id' => $existingProjectId]);
            }

            $projectId = $this->insertProject($connection, $projectName);
            $layoutIds = $this->insertLayoutElements($connection, $projectId);
            $resourceIds = $this->insertResources($connection, $projectId);
            $operationIds = $this->insertOperations($connection, $projectId, $layoutIds, $resourceIds);
            $this->insertConnections($connection, $operationIds);
            $scenarioId = $this->insertScenario($connection, $projectId);

            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }

        return [
            'project_id' => $projectId,
            'project_name' => $projectName,
            'scenario_id' => $scenarioId,
            'layout_elements' => count($layoutIds),
            'resources' => count($resourceIds),
            'operations' => count($operationIds),
            'connections' => count($this->connections()),
        ];
    }

    private function findProjectIdByName(PDO $connection, string $projectName): ?int
    {
        $statement = $connection->prepare('SELECT id FROM projects WHERE name = :name ORDER BY id DESC LIMIT 1');
        $statement->execute(['name' => $projectName]);
        $row = $statement->fetch();

        return $row ? (int) $row['id'] : null;
    }

    private function insertProject(PDO $connection, string $projectName): int
    {
        $statement = $connection->prepare(
            'INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
             VALUES (:name, :description, :production_type, :shift_length_minutes, NOW(), NOW())'
        );
        $statement->execute([
            'name' => $projectName,
            'description' => 'Reference demo project for evaluating the OpenMEP workflow from layout to simulation.',
            'production_type' => 'mixed',
            'shift_length_minutes' => 480,
        ]);

        return (int) $connection->lastInsertId();
    }

    /**
     * @return array<string, int>
     */
    private function insertLayoutElements(PDO $connection, int $projectId): array
    {
        $statement = $connection->prepare(
            'INSERT INTO layout_elements
                (project_id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :element_type, :x_position, :y_position, :width, :height, :rotation, :color, :metadata_json, NOW(), NOW())'
        );

        $ids = [];
        foreach ($this->layoutElements() as $key => $element) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $element['name'],
                'element_type' => $element['type'],
                'x_position' => $element['x'],
                'y_position' => $element['y'],
                'width' => $element['width'],
                'height' => $element['height'],
                'rotation' => $element['rotation'],
                'color' => $element['color'],
                'metadata_json' => json_encode($element['metadata'], JSON_THROW_ON_ERROR),
            ]);
            $ids[$key] = (int) $connection->lastInsertId();
        }

        return $ids;
    }

    /**
     * @return array<string, int>
     */
    private function insertResources(PDO $connection, int $projectId): array
    {
        $statement = $connection->prepare(
            'INSERT INTO resources (project_id, name, resource_type, quantity, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :resource_type, :quantity, :metadata_json, NOW(), NOW())'
        );

        $ids = [];
        foreach ($this->resources() as $key => $resource) {
            $statement->execute([
                'project_id' => $projectId,
                'name' => $resource['name'],
                'resource_type' => $resource['type'],
                'quantity' => $resource['quantity'],
                'metadata_json' => json_encode($resource['metadata'], JSON_THROW_ON_ERROR),
            ]);
            $ids[$key] = (int) $connection->lastInsertId();
        }

        return $ids;
    }

    /**
     * @param array<string, int> $layoutIds
     * @param array<string, int> $resourceIds
     * @return array<string, int>
     */
    private function insertOperations(PDO $connection, int $projectId, array $layoutIds, array $resourceIds): array
    {
        $operationStatement = $connection->prepare(
            'INSERT INTO operations
                (project_id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds, batch_size, scrap_rate, rework_rate, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :operation_code, :name, :linked_layout_element_id, :cycle_time_seconds, :setup_time_seconds, :batch_size, :scrap_rate, :rework_rate, :metadata_json, NOW(), NOW())'
        );
        $assignmentStatement = $connection->prepare(
            'INSERT INTO operation_resources (operation_id, resource_id, required_quantity)
             VALUES (:operation_id, :resource_id, :required_quantity)'
        );

        $ids = [];
        foreach ($this->operations() as $key => $operation) {
            $layoutKey = $operation['layout_key'];
            $metadata = [
                'node_id' => $key,
                'node_type' => $operation['node_type'],
                'x' => $operation['x'],
                'y' => $operation['y'],
                'color' => $operation['color'],
                'is_validated' => true,
                'notes' => $operation['notes'],
            ];

            $operationStatement->execute([
                'project_id' => $projectId,
                'operation_code' => $operation['code'],
                'name' => $operation['name'],
                'linked_layout_element_id' => $layoutKey !== null ? ($layoutIds[$layoutKey] ?? null) : null,
                'cycle_time_seconds' => $operation['cycle_time_minutes'] * 60,
                'setup_time_seconds' => $operation['setup_time_minutes'] * 60,
                'batch_size' => $operation['batch_size'],
                'scrap_rate' => $operation['scrap_rate'],
                'rework_rate' => $operation['rework_rate'],
                'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR),
            ]);
            $operationId = (int) $connection->lastInsertId();
            $ids[$key] = $operationId;

            $resourceKey = $operation['resource_key'];
            if ($resourceKey !== null && isset($resourceIds[$resourceKey])) {
                $assignmentStatement->execute([
                    'operation_id' => $operationId,
                    'resource_id' => $resourceIds[$resourceKey],
                    'required_quantity' => $operation['required_quantity'],
                ]);
            }
        }

        return $ids;
    }

    /** @param array<string, int> $operationIds */
    private function insertConnections(PDO $connection, array $operationIds): void
    {
        $statement = $connection->prepare(
            'INSERT INTO process_connections
                (source_operation_id, target_operation_id, connection_type, probability, metadata_json)
             VALUES
                (:source_operation_id, :target_operation_id, :connection_type, :probability, :metadata_json)'
        );

        foreach ($this->connections() as $connectionDefinition) {
            $statement->execute([
                'source_operation_id' => $operationIds[$connectionDefinition['from']],
                'target_operation_id' => $operationIds[$connectionDefinition['to']],
                'connection_type' => $connectionDefinition['type'],
                'probability' => $connectionDefinition['probability'],
                'metadata_json' => json_encode($connectionDefinition['metadata'], JSON_THROW_ON_ERROR),
            ]);
        }
    }

    private function insertScenario(PDO $connection, int $projectId): int
    {
        $statement = $connection->prepare(
            'INSERT INTO simulation_scenarios
                (project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :duration_minutes, :arrival_rate, :random_seed, :metadata_json, NOW(), NOW())'
        );
        $statement->execute([
            'project_id' => $projectId,
            'name' => 'Baseline 8-hour shift',
            'duration_minutes' => 480,
            'arrival_rate' => 10.00,
            'random_seed' => 42,
            'metadata_json' => json_encode([
                'description' => 'Default deterministic scenario for smoke testing and demonstrations.',
                'created_by' => 'DemoProjectSeederService',
            ], JSON_THROW_ON_ERROR),
        ]);

        return (int) $connection->lastInsertId();
    }

    /** @return array<string, array<string, mixed>> */
    private function layoutElements(): array
    {
        return [
            'raw_warehouse' => ['name' => 'Raw Material Warehouse', 'type' => 'storage', 'x' => 1, 'y' => 1, 'width' => 10, 'height' => 6, 'rotation' => 0, 'color' => '#33691E', 'metadata' => ['icon' => 'warehouse']],
            'cnc_01' => ['name' => 'CNC-01', 'type' => 'machine', 'x' => 14, 'y' => 2, 'width' => 3, 'height' => 2, 'rotation' => 0, 'color' => '#1565C0', 'metadata' => ['cell' => 'machining']],
            'cnc_02' => ['name' => 'CNC-02', 'type' => 'machine', 'x' => 14, 'y' => 5.5, 'width' => 3, 'height' => 2, 'rotation' => 0, 'color' => '#1565C0', 'metadata' => ['cell' => 'machining']],
            'wip_buffer' => ['name' => 'WIP Buffer', 'type' => 'buffer', 'x' => 18, 'y' => 10.5, 'width' => 3, 'height' => 2, 'rotation' => 0, 'color' => '#F57F17', 'metadata' => ['capacity' => 30]],
            'weld_robot' => ['name' => 'Welding Robot', 'type' => 'machine', 'x' => 23, 'y' => 3, 'width' => 2, 'height' => 2, 'rotation' => 0, 'color' => '#B71C1C', 'metadata' => ['cell' => 'welding']],
            'assembly' => ['name' => 'Assembly Station', 'type' => 'machine', 'x' => 33, 'y' => 2, 'width' => 4, 'height' => 2.5, 'rotation' => 0, 'color' => '#1B5E20', 'metadata' => ['cell' => 'assembly']],
            'inspection' => ['name' => 'Final Inspection', 'type' => 'machine', 'x' => 40, 'y' => 6, 'width' => 2, 'height' => 1.5, 'rotation' => 0, 'color' => '#006064', 'metadata' => ['cell' => 'quality']],
            'finished_warehouse' => ['name' => 'Finished Goods Warehouse', 'type' => 'storage', 'x' => 47, 'y' => 1, 'width' => 10, 'height' => 6, 'rotation' => 0, 'color' => '#33691E', 'metadata' => ['icon' => 'warehouse']],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function resources(): array
    {
        return [
            'cnc_pool' => ['name' => 'CNC Machine Pool', 'type' => 'Machine', 'quantity' => 2, 'metadata' => ['linked_layout_names' => ['CNC-01', 'CNC-02']]],
            'welder' => ['name' => 'Welding Robot', 'type' => 'Machine', 'quantity' => 1, 'metadata' => ['linked_layout_names' => ['Welding Robot']]],
            'assembler' => ['name' => 'Assembly Operator Team', 'type' => 'Operator', 'quantity' => 2, 'metadata' => ['skill' => 'assembly']],
            'quality' => ['name' => 'Quality Technician', 'type' => 'Operator', 'quantity' => 1, 'metadata' => ['skill' => 'inspection']],
            'buffer' => ['name' => 'WIP Buffer Capacity', 'type' => 'Buffer', 'quantity' => 30, 'metadata' => ['unit' => 'parts']],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function operations(): array
    {
        return [
            'start' => ['code' => 'START', 'name' => 'Start', 'node_type' => 'start', 'layout_key' => null, 'resource_key' => null, 'required_quantity' => 1, 'cycle_time_minutes' => 0, 'setup_time_minutes' => 0, 'batch_size' => 1, 'scrap_rate' => 0, 'rework_rate' => 0, 'x' => 70, 'y' => 210, 'color' => '#1B5E20', 'notes' => 'Material release point.'],
            'machining' => ['code' => 'OP10', 'name' => 'CNC Machining', 'node_type' => 'operation', 'layout_key' => 'cnc_01', 'resource_key' => 'cnc_pool', 'required_quantity' => 1, 'cycle_time_minutes' => 4.5, 'setup_time_minutes' => 0.5, 'batch_size' => 1, 'scrap_rate' => 1.5, 'rework_rate' => 0, 'x' => 230, 'y' => 180, 'color' => '#1565C0', 'notes' => 'Shared pool using two CNC machines.'],
            'buffer' => ['code' => 'BUF20', 'name' => 'Intermediate Buffer', 'node_type' => 'buffer', 'layout_key' => 'wip_buffer', 'resource_key' => 'buffer', 'required_quantity' => 1, 'cycle_time_minutes' => 0, 'setup_time_minutes' => 0, 'batch_size' => 1, 'scrap_rate' => 0, 'rework_rate' => 0, 'x' => 410, 'y' => 210, 'color' => '#F57F17', 'notes' => 'FIFO WIP buffer.'],
            'welding' => ['code' => 'OP30', 'name' => 'Robot Welding', 'node_type' => 'operation', 'layout_key' => 'weld_robot', 'resource_key' => 'welder', 'required_quantity' => 1, 'cycle_time_minutes' => 6, 'setup_time_minutes' => 0.25, 'batch_size' => 1, 'scrap_rate' => 2, 'rework_rate' => 4, 'x' => 570, 'y' => 180, 'color' => '#B71C1C', 'notes' => 'Main bottleneck candidate.'],
            'assembly' => ['code' => 'OP40', 'name' => 'Final Assembly', 'node_type' => 'operation', 'layout_key' => 'assembly', 'resource_key' => 'assembler', 'required_quantity' => 1, 'cycle_time_minutes' => 8, 'setup_time_minutes' => 0.5, 'batch_size' => 1, 'scrap_rate' => 0.5, 'rework_rate' => 0, 'x' => 750, 'y' => 180, 'color' => '#1B5E20', 'notes' => 'Manual assisted final assembly.'],
            'inspection' => ['code' => 'QC50', 'name' => 'Final Inspection', 'node_type' => 'inspection', 'layout_key' => 'inspection', 'resource_key' => 'quality', 'required_quantity' => 1, 'cycle_time_minutes' => 1.5, 'setup_time_minutes' => 0, 'batch_size' => 1, 'scrap_rate' => 1, 'rework_rate' => 2, 'x' => 930, 'y' => 180, 'color' => '#006064', 'notes' => 'Quality gate before completion.'],
            'rework' => ['code' => 'RW60', 'name' => 'Rework', 'node_type' => 'operation', 'layout_key' => 'assembly', 'resource_key' => 'assembler', 'required_quantity' => 1, 'cycle_time_minutes' => 12, 'setup_time_minutes' => 0, 'batch_size' => 1, 'scrap_rate' => 0, 'rework_rate' => 0, 'x' => 930, 'y' => 330, 'color' => '#6A1B9A', 'notes' => 'Explicit rework loop to inspection.'],
            'end' => ['code' => 'END', 'name' => 'End', 'node_type' => 'end', 'layout_key' => 'finished_warehouse', 'resource_key' => null, 'required_quantity' => 1, 'cycle_time_minutes' => 0, 'setup_time_minutes' => 0, 'batch_size' => 1, 'scrap_rate' => 0, 'rework_rate' => 0, 'x' => 1110, 'y' => 210, 'color' => '#7F0000', 'notes' => 'Finished goods received.'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function connections(): array
    {
        return [
            ['from' => 'start', 'to' => 'machining', 'type' => 'normal', 'probability' => 100.00, 'metadata' => []],
            ['from' => 'machining', 'to' => 'buffer', 'type' => 'normal', 'probability' => 100.00, 'metadata' => []],
            ['from' => 'buffer', 'to' => 'welding', 'type' => 'normal', 'probability' => 100.00, 'metadata' => []],
            ['from' => 'welding', 'to' => 'assembly', 'type' => 'normal', 'probability' => 96.00, 'metadata' => []],
            ['from' => 'welding', 'to' => 'rework', 'type' => 'rework', 'probability' => 4.00, 'metadata' => ['reason' => 'weld_rework']],
            ['from' => 'assembly', 'to' => 'inspection', 'type' => 'normal', 'probability' => 100.00, 'metadata' => []],
            ['from' => 'inspection', 'to' => 'end', 'type' => 'normal', 'probability' => 98.00, 'metadata' => []],
            ['from' => 'inspection', 'to' => 'rework', 'type' => 'rework', 'probability' => 2.00, 'metadata' => ['reason' => 'inspection_rework']],
            ['from' => 'rework', 'to' => 'inspection', 'type' => 'normal', 'probability' => 100.00, 'metadata' => []],
        ];
    }
}
