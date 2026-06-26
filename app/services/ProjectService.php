<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Validators\ProjectValidator;
use InvalidArgumentException;

final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $repository,
        private readonly ProjectValidator $validator,
        private readonly ?AuditLogService $auditLog = null
    ) {
    }

    /** @param array{query?: string, production_type?: string, sort?: string, direction?: string, page?: mixed, per_page?: mixed} $filters */
    public function listProjects(array $filters = []): array
    {
        $filters = $this->normalizeListFilters($filters);
        $page = (int) $filters['page'];
        $perPage = (int) $filters['per_page'];
        $totalItems = $this->repository->countAll($filters);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'projects' => array_map(
                static fn (Project $project): array => $project->toArray(),
                $this->repository->findPage($filters, $perPage, $offset)
            ),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{query?: string, production_type?: string, sort?: string, direction: string, page: int, per_page: int}
     */
    private function normalizeListFilters(array $filters): array
    {
        $normalized = [];

        $query = trim((string) ($filters['query'] ?? ''));
        if ($query !== '') {
            $normalized['query'] = substr($query, 0, 100);
        }

        $productionType = trim((string) ($filters['production_type'] ?? ''));
        if (in_array($productionType, ['serial', 'job_shop', 'mixed'], true)) {
            $normalized['production_type'] = $productionType;
        }

        $sort = (string) ($filters['sort'] ?? 'updated_at');
        if (in_array($sort, ['name', 'production_type', 'shift_length_minutes', 'updated_at', 'created_at'], true)) {
            $normalized['sort'] = $sort;
        }

        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC'));
        $normalized['direction'] = $direction === 'ASC' ? 'ASC' : 'DESC';

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $normalized['page'] = max(1, $page);
        $normalized['per_page'] = max(5, min(100, $perPage));

        return $normalized;
    }

    public function createProject(array $data): array
    {
        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $project = Project::fromArray([
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'production_type' => $data['production_type'] ?? 'serial',
            'shift_length_minutes' => $data['shift_length_minutes'] ?? 480,
        ]);

        $created = $this->repository->create($project);
        $this->auditLog?->record(
            $created->id,
            'project',
            $created->id,
            'created',
            'Project created: ' . $created->name,
            ['production_type' => $created->productionType]
        );

        return $created->toArray();
    }

    public function updateProject(array $data): array
    {
        $errors = $this->validator->validate($data, true);
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        $existing = $this->repository->findById((int) $data['id']);
        if ($existing === null) {
            return [];
        }

        $project = Project::fromArray([
            'id' => (int) $data['id'],
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'production_type' => $data['production_type'] ?? 'serial',
            'shift_length_minutes' => $data['shift_length_minutes'] ?? 480,
        ]);

        $updated = $this->repository->update($project);
        if ($updated !== null) {
            $this->auditLog?->record(
                $updated->id,
                'project',
                $updated->id,
                'updated',
                'Project updated: ' . $updated->name,
                ['production_type' => $updated->productionType]
            );
        }

        return $updated?->toArray() ?? [];
    }


    public function duplicateProject(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'id', 'message' => 'Project ID is required.'],
            ], JSON_THROW_ON_ERROR));
        }

        $name = isset($data['name']) ? trim((string) $data['name']) : null;
        if ($name !== null && $name === '') {
            throw new InvalidArgumentException(json_encode([
                ['field' => 'name', 'message' => 'Duplicate project name cannot be empty.'],
            ], JSON_THROW_ON_ERROR));
        }

        $duplicated = $this->repository->duplicate($id, $name);
        if ($duplicated === null) {
            return [];
        }

        $this->auditLog?->record(
            $duplicated->id,
            'project',
            $duplicated->id,
            'duplicated',
            'Project duplicated: ' . $duplicated->name,
            ['source_project_id' => $id]
        );

        return $duplicated->toArray();
    }

    public function deleteProject(int $id): bool
    {
        $existing = $this->repository->findById($id);
        $deleted = $this->repository->delete($id);
        if ($deleted) {
            $this->auditLog?->record(
                null,
                'project',
                $id,
                'deleted',
                'Project deleted' . ($existing !== null ? ': ' . $existing->name : ''),
                ['project_id' => $id]
            );
        }

        return $deleted;
    }
}
