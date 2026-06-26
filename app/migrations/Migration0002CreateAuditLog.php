<?php

declare(strict_types=1);

namespace App\Migrations;

use PDO;

final class Migration0002CreateAuditLog implements MigrationInterface
{
    public function version(): string
    {
        return '0002_create_audit_log';
    }

    public function description(): string
    {
        return 'Create audit log table for traceable engineering changes.';
    }

    public function up(PDO $connection): void
    {
        $connection->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_audit_log_project_id (project_id),
    INDEX idx_audit_log_entity (entity_type, entity_id),
    INDEX idx_audit_log_created_at (created_at),
    CONSTRAINT fk_audit_log_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
}
