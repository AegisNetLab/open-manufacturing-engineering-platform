<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Resource;
use PDO;

final class ResourceRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return Resource[] */
    public function findByProject(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, name, resource_type, quantity, metadata_json, created_at, updated_at
             FROM resources
             WHERE project_id = :project_id
             ORDER BY resource_type ASC, name ASC, id ASC'
        );
        $statement->execute(['project_id' => $projectId]);

        return array_map(
            static fn (array $row): Resource => Resource::fromArray($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?Resource
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, name, resource_type, quantity, metadata_json, created_at, updated_at
             FROM resources
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? Resource::fromArray($row) : null;
    }

    public function create(Resource $resource): Resource
    {
        $statement = $this->connection->prepare(
            'INSERT INTO resources (project_id, name, resource_type, quantity, metadata_json, created_at, updated_at)
             VALUES (:project_id, :name, :resource_type, :quantity, :metadata_json, NOW(), NOW())'
        );
        $statement->execute([
            'project_id' => $resource->projectId,
            'name' => $resource->name,
            'resource_type' => $resource->resourceType,
            'quantity' => $resource->quantity,
            'metadata_json' => $resource->metadata === null ? null : json_encode($resource->metadata, JSON_THROW_ON_ERROR),
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function update(Resource $resource): ?Resource
    {
        $statement = $this->connection->prepare(
            'UPDATE resources
             SET name = :name,
                 resource_type = :resource_type,
                 quantity = :quantity,
                 metadata_json = :metadata_json,
                 updated_at = NOW()
             WHERE id = :id AND project_id = :project_id'
        );
        $statement->execute([
            'id' => $resource->id,
            'project_id' => $resource->projectId,
            'name' => $resource->name,
            'resource_type' => $resource->resourceType,
            'quantity' => $resource->quantity,
            'metadata_json' => $resource->metadata === null ? null : json_encode($resource->metadata, JSON_THROW_ON_ERROR),
        ]);

        return $this->findById((int) $resource->id);
    }

    public function delete(int $id, int $projectId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM resources WHERE id = :id AND project_id = :project_id');
        $statement->execute(['id' => $id, 'project_id' => $projectId]);

        return $statement->rowCount() > 0;
    }
}
