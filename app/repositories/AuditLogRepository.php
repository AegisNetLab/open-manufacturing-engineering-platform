<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuditLogRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        ?int $projectId,
        string $entityType,
        ?int $entityId,
        string $action,
        string $summary,
        array $metadata = []
    ): void {
        $statement = $this->connection->prepare(
            'INSERT INTO audit_log
                (project_id, entity_type, entity_id, action, summary, metadata_json, created_at)
             VALUES
                (:project_id, :entity_type, :entity_id, :action, :summary, :metadata_json, NOW())'
        );
        $statement->execute([
            'project_id' => $projectId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'summary' => $summary,
            'metadata_json' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findRecent(?int $projectId = null, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));

        if ($projectId !== null) {
            $statement = $this->connection->prepare(
                "SELECT id, project_id, entity_type, entity_id, action, summary, metadata_json, created_at
                 FROM audit_log
                 WHERE project_id = :project_id
                 ORDER BY created_at DESC, id DESC
                 LIMIT {$limit}"
            );
            $statement->execute(['project_id' => $projectId]);
        } else {
            $statement = $this->connection->query(
                "SELECT id, project_id, entity_type, entity_id, action, summary, metadata_json, created_at
                 FROM audit_log
                 ORDER BY created_at DESC, id DESC
                 LIMIT {$limit}"
            );
        }

        return array_map(
            static function (array $row): array {
                $row['id'] = (int) $row['id'];
                $row['project_id'] = $row['project_id'] === null ? null : (int) $row['project_id'];
                $row['entity_id'] = $row['entity_id'] === null ? null : (int) $row['entity_id'];
                $row['metadata'] = $row['metadata_json'] === null
                    ? []
                    : (json_decode((string) $row['metadata_json'], true) ?: []);
                unset($row['metadata_json']);

                return $row;
            },
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
