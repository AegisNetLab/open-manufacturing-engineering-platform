<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;

final class AuditLogService
{
    public function __construct(private readonly AuditLogRepository $repository)
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
        $this->repository->record(
            $projectId,
            $this->normalizeToken($entityType),
            $entityId,
            $this->normalizeToken($action),
            trim($summary),
            $this->sanitizeMetadata($metadata)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(?int $projectId = null, int $limit = 100): array
    {
        return $this->repository->findRecent($projectId, $limit);
    }

    private function normalizeToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '_', $value) ?? '';

        return trim($value, '_-') ?: 'unknown';
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];
        foreach ($metadata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $clean[(string) $key] = $value;
                continue;
            }

            if (is_array($value)) {
                $clean[(string) $key] = $value;
            }
        }

        return $clean;
    }
}
