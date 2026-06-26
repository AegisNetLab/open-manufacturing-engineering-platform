<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use PDO;

final class ProjectRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @param array{query?: string, production_type?: string, sort?: string, direction?: string} $filters
     * @return Project[]
     */
    public function findAll(array $filters = []): array
    {
        return $this->findPage($filters, 0, 0);
    }

    /**
     * @param array{query?: string, production_type?: string, sort?: string, direction?: string} $filters
     * @return Project[]
     */
    public function findPage(array $filters, int $limit, int $offset): array
    {
        [$whereSql, $params] = $this->buildProjectListWhere($filters);
        [$sort, $direction] = $this->resolveProjectListSort($filters);

        $sql = 'SELECT id, name, description, production_type, shift_length_minutes, created_at, updated_at
                FROM projects' . $whereSql . sprintf(' ORDER BY %s %s, id DESC', $sort, $direction);

        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
            $params['limit'] = $limit;
            $params['offset'] = max(0, $offset);
        }

        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                in_array($key, ['limit', 'offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $statement->execute();

        return array_map(
            static fn (array $row): Project => Project::fromArray($row),
            $statement->fetchAll()
        );
    }

    /** @param array{query?: string, production_type?: string} $filters */
    public function countAll(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildProjectListWhere($filters);

        $statement = $this->connection->prepare('SELECT COUNT(*) FROM projects' . $whereSql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array{query?: string, production_type?: string} $filters
     * @return array{0:string,1:array<string,string>}
     */
    private function buildProjectListWhere(array $filters): array
    {
        $where = [];
        $params = [];

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $where[] = '(name LIKE :query OR description LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $productionType = trim((string) ($filters['production_type'] ?? ''));
        if ($productionType !== '') {
            $where[] = 'production_type = :production_type';
            $params['production_type'] = $productionType;
        }

        return [$where === [] ? '' : ' WHERE ' . implode(' AND ', $where), $params];
    }

    /**
     * @param array{sort?: string, direction?: string} $filters
     * @return array{0:string,1:string}
     */
    private function resolveProjectListSort(array $filters): array
    {
        $sortMap = [
            'name' => 'name',
            'production_type' => 'production_type',
            'shift_length_minutes' => 'shift_length_minutes',
            'updated_at' => 'updated_at',
            'created_at' => 'created_at',
        ];

        $sort = $sortMap[(string) ($filters['sort'] ?? 'updated_at')] ?? 'updated_at';
        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        return [$sort, $direction];
    }

    public function findById(int $id): ?Project
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, description, production_type, shift_length_minutes, created_at, updated_at
             FROM projects
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? Project::fromArray($row) : null;
    }

    public function create(Project $project): Project
    {
        $statement = $this->connection->prepare(
            'INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
             VALUES (:name, :description, :production_type, :shift_length_minutes, NOW(), NOW())'
        );
        $statement->execute([
            'name' => $project->name,
            'description' => $project->description,
            'production_type' => $project->productionType,
            'shift_length_minutes' => $project->shiftLengthMinutes,
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function update(Project $project): ?Project
    {
        $statement = $this->connection->prepare(
            'UPDATE projects
             SET name = :name,
                 description = :description,
                 production_type = :production_type,
                 shift_length_minutes = :shift_length_minutes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'production_type' => $project->productionType,
            'shift_length_minutes' => $project->shiftLengthMinutes,
        ]);

        return $this->findById((int) $project->id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM projects WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function duplicate(int $sourceProjectId, ?string $targetName = null): ?Project
    {
        $source = $this->findById($sourceProjectId);
        if ($source === null) {
            return null;
        }

        $this->connection->beginTransaction();

        try {
            $projectName = $targetName !== null && trim($targetName) !== ''
                ? trim($targetName)
                : $source->name . ' (Copy)';

            $statement = $this->connection->prepare(
                'INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
                 VALUES (:name, :description, :production_type, :shift_length_minutes, NOW(), NOW())'
            );
            $statement->execute([
                'name' => $projectName,
                'description' => $source->description,
                'production_type' => $source->productionType,
                'shift_length_minutes' => $source->shiftLengthMinutes,
            ]);
            $newProjectId = (int) $this->connection->lastInsertId();

            $layoutIdMap = $this->duplicateLayoutElements($sourceProjectId, $newProjectId);
            $resourceIdMap = $this->duplicateResources($sourceProjectId, $newProjectId);
            $operationIdMap = $this->duplicateOperations($sourceProjectId, $newProjectId, $layoutIdMap);
            $this->duplicateProcessConnections($operationIdMap);
            $this->duplicateOperationResources($operationIdMap, $resourceIdMap);
            $this->duplicateScenarios($sourceProjectId, $newProjectId);

            $this->connection->commit();

            return $this->findById($newProjectId);
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
    }

    /** @return array<int,int> */
    private function duplicateLayoutElements(int $sourceProjectId, int $newProjectId): array
    {
        $rows = $this->fetchAllByProject(
            'SELECT id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json
             FROM layout_elements
             WHERE project_id = :project_id',
            $sourceProjectId
        );
        $insert = $this->connection->prepare(
            'INSERT INTO layout_elements
                (project_id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :element_type, :x_position, :y_position, :width, :height, :rotation, :color, :metadata_json, NOW(), NOW())'
        );

        $map = [];
        foreach ($rows as $row) {
            $insert->execute([
                'project_id' => $newProjectId,
                'name' => $row['name'],
                'element_type' => $row['element_type'],
                'x_position' => $row['x_position'],
                'y_position' => $row['y_position'],
                'width' => $row['width'],
                'height' => $row['height'],
                'rotation' => $row['rotation'],
                'color' => $row['color'],
                'metadata_json' => $row['metadata_json'],
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    /** @return array<int,int> */
    private function duplicateResources(int $sourceProjectId, int $newProjectId): array
    {
        $rows = $this->fetchAllByProject(
            'SELECT id, name, resource_type, quantity, metadata_json
             FROM resources
             WHERE project_id = :project_id',
            $sourceProjectId
        );
        $insert = $this->connection->prepare(
            'INSERT INTO resources (project_id, name, resource_type, quantity, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :resource_type, :quantity, :metadata_json, NOW(), NOW())'
        );

        $map = [];
        foreach ($rows as $row) {
            $insert->execute([
                'project_id' => $newProjectId,
                'name' => $row['name'],
                'resource_type' => $row['resource_type'],
                'quantity' => $row['quantity'],
                'metadata_json' => $row['metadata_json'],
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    /** @param array<int,int> $layoutIdMap @return array<int,int> */
    private function duplicateOperations(int $sourceProjectId, int $newProjectId, array $layoutIdMap): array
    {
        $rows = $this->fetchAllByProject(
            'SELECT id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds,
                    batch_size, scrap_rate, rework_rate, metadata_json
             FROM operations
             WHERE project_id = :project_id',
            $sourceProjectId
        );
        $insert = $this->connection->prepare(
            'INSERT INTO operations
                (project_id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds,
                 batch_size, scrap_rate, rework_rate, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :operation_code, :name, :linked_layout_element_id, :cycle_time_seconds, :setup_time_seconds,
                 :batch_size, :scrap_rate, :rework_rate, :metadata_json, NOW(), NOW())'
        );

        $map = [];
        foreach ($rows as $row) {
            $oldLayoutId = isset($row['linked_layout_element_id']) ? (int) $row['linked_layout_element_id'] : 0;
            $insert->execute([
                'project_id' => $newProjectId,
                'operation_code' => $row['operation_code'],
                'name' => $row['name'],
                'linked_layout_element_id' => $oldLayoutId > 0 ? ($layoutIdMap[$oldLayoutId] ?? null) : null,
                'cycle_time_seconds' => $row['cycle_time_seconds'],
                'setup_time_seconds' => $row['setup_time_seconds'],
                'batch_size' => $row['batch_size'],
                'scrap_rate' => $row['scrap_rate'],
                'rework_rate' => $row['rework_rate'],
                'metadata_json' => $row['metadata_json'],
            ]);
            $map[(int) $row['id']] = (int) $this->connection->lastInsertId();
        }

        return $map;
    }

    /** @param array<int,int> $operationIdMap */
    private function duplicateProcessConnections(array $operationIdMap): void
    {
        if ($operationIdMap === []) {
            return;
        }

        $rows = $this->fetchProcessConnections(array_keys($operationIdMap));
        $insert = $this->connection->prepare(
            'INSERT INTO process_connections
                (source_operation_id, target_operation_id, connection_type, probability, metadata_json)
             VALUES
                (:source_operation_id, :target_operation_id, :connection_type, :probability, :metadata_json)'
        );

        foreach ($rows as $row) {
            $source = $operationIdMap[(int) $row['source_operation_id']] ?? null;
            $target = $operationIdMap[(int) $row['target_operation_id']] ?? null;
            if ($source === null || $target === null) {
                continue;
            }

            $insert->execute([
                'source_operation_id' => $source,
                'target_operation_id' => $target,
                'connection_type' => $row['connection_type'],
                'probability' => $row['probability'],
                'metadata_json' => $row['metadata_json'],
            ]);
        }
    }

    /** @param array<int,int> $operationIdMap @param array<int,int> $resourceIdMap */
    private function duplicateOperationResources(array $operationIdMap, array $resourceIdMap): void
    {
        if ($operationIdMap === [] || $resourceIdMap === []) {
            return;
        }

        $rows = $this->fetchOperationResources(array_keys($operationIdMap));
        $insert = $this->connection->prepare(
            'INSERT INTO operation_resources (operation_id, resource_id, required_quantity)
             VALUES (:operation_id, :resource_id, :required_quantity)'
        );

        foreach ($rows as $row) {
            $operationId = $operationIdMap[(int) $row['operation_id']] ?? null;
            $resourceId = $resourceIdMap[(int) $row['resource_id']] ?? null;
            if ($operationId === null || $resourceId === null) {
                continue;
            }

            $insert->execute([
                'operation_id' => $operationId,
                'resource_id' => $resourceId,
                'required_quantity' => $row['required_quantity'],
            ]);
        }
    }

    private function duplicateScenarios(int $sourceProjectId, int $newProjectId): void
    {
        $rows = $this->fetchAllByProject(
            'SELECT name, duration_minutes, arrival_rate, random_seed, metadata_json
             FROM simulation_scenarios
             WHERE project_id = :project_id',
            $sourceProjectId
        );
        $insert = $this->connection->prepare(
            'INSERT INTO simulation_scenarios
                (project_id, name, duration_minutes, arrival_rate, random_seed, metadata_json, created_at, updated_at)
             VALUES
                (:project_id, :name, :duration_minutes, :arrival_rate, :random_seed, :metadata_json, NOW(), NOW())'
        );

        foreach ($rows as $row) {
            $insert->execute([
                'project_id' => $newProjectId,
                'name' => $row['name'],
                'duration_minutes' => $row['duration_minutes'],
                'arrival_rate' => $row['arrival_rate'],
                'random_seed' => $row['random_seed'],
                'metadata_json' => $row['metadata_json'],
            ]);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function fetchAllByProject(string $sql, int $projectId): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute(['project_id' => $projectId]);

        return $statement->fetchAll();
    }

    /** @param int[] $operationIds @return array<int,array<string,mixed>> */
    private function fetchProcessConnections(array $operationIds): array
    {
        $placeholders = implode(',', array_fill(0, count($operationIds), '?'));
        $statement = $this->connection->prepare(
            'SELECT source_operation_id, target_operation_id, connection_type, probability, metadata_json
             FROM process_connections
             WHERE source_operation_id IN (' . $placeholders . ')'
        );
        $statement->execute($operationIds);

        return $statement->fetchAll();
    }

    /** @param int[] $operationIds @return array<int,array<string,mixed>> */
    private function fetchOperationResources(array $operationIds): array
    {
        $placeholders = implode(',', array_fill(0, count($operationIds), '?'));
        $statement = $this->connection->prepare(
            'SELECT operation_id, resource_id, required_quantity
             FROM operation_resources
             WHERE operation_id IN (' . $placeholders . ')'
        );
        $statement->execute($operationIds);

        return $statement->fetchAll();
    }

}
