<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProcessRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function loadByProjectId(int $projectId): array
    {
        $operationsStatement = $this->connection->prepare(
            'SELECT * FROM operations WHERE project_id = :project_id ORDER BY id ASC'
        );
        $operationsStatement->execute(['project_id' => $projectId]);
        $operations = array_map([$this, 'mapOperation'], $operationsStatement->fetchAll());

        if ($operations === []) {
            return ['operations' => [], 'connections' => [], 'version' => 1];
        }

        $operationIds = array_column($operations, 'id');
        $placeholders = implode(',', array_fill(0, count($operationIds), '?'));

        $resourcesStatement = $this->connection->prepare(
            "SELECT operation_id, resource_id, required_quantity, r.name AS resource_name
             FROM operation_resources opr
             INNER JOIN resources r ON r.id = opr.resource_id
             WHERE opr.operation_id IN ($placeholders)
             ORDER BY opr.operation_id ASC, opr.id ASC"
        );
        $resourcesStatement->execute($operationIds);
        $resourceAssignments = [];
        foreach ($resourcesStatement->fetchAll() as $row) {
            $operationId = (int) $row['operation_id'];
            $resourceAssignments[$operationId][] = [
                'resource_id' => (int) $row['resource_id'],
                'resource_name' => (string) $row['resource_name'],
                'required_quantity' => (int) $row['required_quantity'],
            ];
        }

        foreach ($operations as &$operation) {
            $assignments = $resourceAssignments[$operation['id']] ?? [];
            $operation['resource_assignments'] = $assignments;
            if ($assignments !== []) {
                $operation['resource_id'] = $assignments[0]['resource_id'];
                $operation['resource_name'] = $assignments[0]['resource_name'];
                $operation['required_quantity'] = $assignments[0]['required_quantity'];
            }
        }
        unset($operation);

        $connectionsStatement = $this->connection->prepare(
            "SELECT pc.* FROM process_connections pc WHERE pc.source_operation_id IN ($placeholders) ORDER BY pc.id ASC"
        );
        $connectionsStatement->execute($operationIds);
        $byId = [];
        foreach ($operations as $operation) {
            $byId[$operation['id']] = $operation;
        }

        $connections = array_map(
            fn (array $row): array => $this->mapConnection($row, $byId),
            $connectionsStatement->fetchAll()
        );

        return ['operations' => $operations, 'connections' => $connections, 'version' => 1];
    }

    public function replaceProjectProcess(int $projectId, array $operations, array $connections, bool $isValidated): array
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM operations WHERE project_id = :project_id');
            $delete->execute(['project_id' => $projectId]);

            $insertOperation = $this->connection->prepare(
                'INSERT INTO operations
                    (project_id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds, batch_size, scrap_rate, rework_rate, metadata_json, created_at, updated_at)
                 VALUES
                    (:project_id, :operation_code, :name, :linked_layout_element_id, :cycle_time_seconds, :setup_time_seconds, :batch_size, :scrap_rate, :rework_rate, :metadata_json, NOW(), NOW())'
            );

            $insertOperationResource = $this->connection->prepare(
                'INSERT INTO operation_resources (operation_id, resource_id, required_quantity)
                 VALUES (:operation_id, :resource_id, :required_quantity)'
            );

            $nodeToOperationId = [];
            foreach ($operations as $operation) {
                $metadata = $operation['metadata'];
                $metadata['is_validated'] = $isValidated;
                $insertOperation->execute([
                    'project_id' => $projectId,
                    'operation_code' => $operation['operation_code'],
                    'name' => $operation['name'],
                    'linked_layout_element_id' => $operation['linked_layout_element_id'],
                    'cycle_time_seconds' => $operation['cycle_time_seconds'],
                    'setup_time_seconds' => $operation['setup_time_seconds'],
                    'batch_size' => $operation['batch_size'],
                    'scrap_rate' => $operation['scrap_rate'],
                    'rework_rate' => $operation['rework_rate'],
                    'metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR),
                ]);
                $operationId = (int) $this->connection->lastInsertId();
                $nodeToOperationId[$operation['node_id']] = $operationId;

                if (!empty($operation['resource_id'])) {
                    $insertOperationResource->execute([
                        'operation_id' => $operationId,
                        'resource_id' => (int) $operation['resource_id'],
                        'required_quantity' => max(1, (int) ($operation['required_quantity'] ?? 1)),
                    ]);
                }
            }

            $insertConnection = $this->connection->prepare(
                'INSERT INTO process_connections
                    (source_operation_id, target_operation_id, connection_type, probability, metadata_json)
                 VALUES
                    (:source_operation_id, :target_operation_id, :connection_type, :probability, :metadata_json)'
            );

            foreach ($connections as $connection) {
                if (!isset($nodeToOperationId[$connection['source_node_id']], $nodeToOperationId[$connection['target_node_id']])) {
                    continue;
                }

                $insertConnection->execute([
                    'source_operation_id' => $nodeToOperationId[$connection['source_node_id']],
                    'target_operation_id' => $nodeToOperationId[$connection['target_node_id']],
                    'connection_type' => $connection['connection_type'],
                    'probability' => $connection['probability'],
                    'metadata_json' => json_encode($connection['metadata'], JSON_THROW_ON_ERROR),
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }

        return $this->loadByProjectId($projectId);
    }

    private function mapOperation(array $row): array
    {
        $metadata = json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [];

        return [
            'id' => (int) $row['id'],
            'project_id' => (int) $row['project_id'],
            'node_id' => (string) ($metadata['node_id'] ?? ('op_' . $row['id'])),
            'node_type' => (string) ($metadata['node_type'] ?? 'operation'),
            'operation_code' => $row['operation_code'],
            'name' => $row['name'],
            'linked_layout_element_id' => $row['linked_layout_element_id'] !== null ? (int) $row['linked_layout_element_id'] : null,
            'resource_id' => !empty($metadata['resource_id']) ? (int) $metadata['resource_id'] : null,
            'resource_name' => (string) ($metadata['resource_name'] ?? $metadata['resource'] ?? ''),
            'required_quantity' => max(1, (int) ($metadata['required_quantity'] ?? 1)),
            'resource_assignments' => [],
            'cycle_time_minutes' => round(((float) $row['cycle_time_seconds']) / 60, 2),
            'setup_time_minutes' => round(((float) $row['setup_time_seconds']) / 60, 2),
            'batch_size' => (int) $row['batch_size'],
            'scrap_rate' => (float) $row['scrap_rate'],
            'rework_rate' => (float) $row['rework_rate'],
            'x' => (float) ($metadata['x'] ?? 80),
            'y' => (float) ($metadata['y'] ?? 80),
            'color' => (string) ($metadata['color'] ?? '#1565C0'),
            'mtbf_hours' => (float) ($metadata['mtbf_hours'] ?? 0),
            'mttr_hours' => (float) ($metadata['mttr_hours'] ?? 0),
            'notes' => (string) ($metadata['notes'] ?? ''),
            'metadata' => $metadata,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function mapConnection(array $row, array $operationsById): array
    {
        return [
            'id' => (int) $row['id'],
            'source_operation_id' => (int) $row['source_operation_id'],
            'target_operation_id' => (int) $row['target_operation_id'],
            'source_node_id' => (string) ($operationsById[(int) $row['source_operation_id']]['node_id'] ?? ''),
            'target_node_id' => (string) ($operationsById[(int) $row['target_operation_id']]['node_id'] ?? ''),
            'connection_type' => $row['connection_type'],
            'probability' => (float) $row['probability'],
            'metadata' => json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [],
        ];
    }
}
