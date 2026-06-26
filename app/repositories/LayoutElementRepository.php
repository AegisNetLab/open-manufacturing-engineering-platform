<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LayoutElementRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByProjectId(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM layout_elements WHERE project_id = :project_id ORDER BY id ASC'
        );
        $statement->execute(['project_id' => $projectId]);

        return array_map([$this, 'mapRow'], $statement->fetchAll());
    }

    public function replaceProjectLayout(int $projectId, array $elements): array
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM layout_elements WHERE project_id = :project_id');
            $delete->execute(['project_id' => $projectId]);

            $insert = $this->connection->prepare(
                'INSERT INTO layout_elements
                    (project_id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json, created_at, updated_at)
                 VALUES
                    (:project_id, :name, :element_type, :x_position, :y_position, :width, :height, :rotation, :color, :metadata_json, NOW(), NOW())'
            );

            foreach ($elements as $element) {
                $insert->execute([
                    'project_id' => $projectId,
                    'name' => $element['name'],
                    'element_type' => $element['element_type'],
                    'x_position' => $element['x_position'],
                    'y_position' => $element['y_position'],
                    'width' => $element['width'],
                    'height' => $element['height'],
                    'rotation' => $element['rotation'],
                    'color' => $element['color'] ?? null,
                    'metadata_json' => json_encode($element['metadata'] ?? [], JSON_THROW_ON_ERROR),
                ]);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }

        return $this->findByProjectId($projectId);
    }

    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'project_id' => (int) $row['project_id'],
            'name' => $row['name'],
            'element_type' => $row['element_type'],
            'x_position' => (float) $row['x_position'],
            'y_position' => (float) $row['y_position'],
            'width' => (float) $row['width'],
            'height' => (float) $row['height'],
            'rotation' => (int) $row['rotation'],
            'color' => $row['color'],
            'metadata' => json_decode((string) ($row['metadata_json'] ?? '{}'), true) ?: [],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
