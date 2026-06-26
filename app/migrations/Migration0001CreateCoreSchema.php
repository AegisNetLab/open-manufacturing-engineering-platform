<?php

declare(strict_types=1);

namespace App\Migrations;

use PDO;

final class Migration0001CreateCoreSchema implements MigrationInterface
{
    public function version(): string
    {
        return '0001_create_core_schema';
    }

    public function description(): string
    {
        return 'Create the core OpenMEP database tables.';
    }

    public function up(PDO $connection): void
    {
        foreach ($this->statements() as $statement) {
            $connection->exec($statement);
        }
    }

    /**
     * @return array<int, string>
     */
    private function statements(): array
    {
        return [
            <<<'SQL'
CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    production_type ENUM('serial', 'job_shop', 'mixed') NOT NULL DEFAULT 'serial',
    shift_length_minutes INT NOT NULL DEFAULT 480,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_projects_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS layout_elements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    element_type VARCHAR(50) NOT NULL,
    x_position DECIMAL(10,2) NOT NULL DEFAULT 0,
    y_position DECIMAL(10,2) NOT NULL DEFAULT 0,
    width DECIMAL(10,2) NOT NULL DEFAULT 1,
    height DECIMAL(10,2) NOT NULL DEFAULT 1,
    rotation SMALLINT NOT NULL DEFAULT 0,
    color VARCHAR(20) NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_layout_elements_project_id (project_id),
    CONSTRAINT fk_layout_elements_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    resource_type VARCHAR(30) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_resources_project_id (project_id),
    CONSTRAINT fk_resources_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS operations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    operation_code VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    linked_layout_element_id BIGINT UNSIGNED NULL,
    cycle_time_seconds DECIMAL(10,2) NOT NULL DEFAULT 0,
    setup_time_seconds DECIMAL(10,2) NOT NULL DEFAULT 0,
    batch_size INT NOT NULL DEFAULT 1,
    scrap_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    rework_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_operations_project_code (project_id, operation_code),
    INDEX idx_operations_project_id (project_id),
    INDEX idx_operations_layout_element_id (linked_layout_element_id),
    CONSTRAINT fk_operations_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_operations_layout_element
        FOREIGN KEY (linked_layout_element_id) REFERENCES layout_elements (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS process_connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_operation_id BIGINT UNSIGNED NOT NULL,
    target_operation_id BIGINT UNSIGNED NOT NULL,
    connection_type VARCHAR(30) NOT NULL DEFAULT 'normal',
    probability DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    metadata_json JSON NULL,
    PRIMARY KEY (id),
    INDEX idx_process_connections_source (source_operation_id),
    INDEX idx_process_connections_target (target_operation_id),
    INDEX idx_process_connections_pair (source_operation_id, target_operation_id),
    CONSTRAINT fk_process_connections_source
        FOREIGN KEY (source_operation_id) REFERENCES operations (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_process_connections_target
        FOREIGN KEY (target_operation_id) REFERENCES operations (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS operation_resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    operation_id BIGINT UNSIGNED NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    required_quantity INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_operation_resources (operation_id, resource_id),
    INDEX idx_operation_resources_operation_id (operation_id),
    INDEX idx_operation_resources_resource_id (resource_id),
    CONSTRAINT fk_operation_resources_operation
        FOREIGN KEY (operation_id) REFERENCES operations (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_operation_resources_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS simulation_scenarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    arrival_rate DECIMAL(10,2) NOT NULL,
    random_seed INT NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_simulation_scenarios_project_id (project_id),
    CONSTRAINT fk_simulation_scenarios_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS simulation_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scenario_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'running',
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    PRIMARY KEY (id),
    INDEX idx_simulation_runs_scenario_id (scenario_id),
    CONSTRAINT fk_simulation_runs_scenario
        FOREIGN KEY (scenario_id) REFERENCES simulation_scenarios (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS simulation_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    simulation_run_id BIGINT UNSIGNED NOT NULL,
    throughput_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0,
    average_lead_time_minutes DECIMAL(10,2) NOT NULL DEFAULT 0,
    average_wip DECIMAL(10,2) NOT NULL DEFAULT 0,
    resource_utilization_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    oee_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_simulation_results_run_id (simulation_run_id),
    CONSTRAINT fk_simulation_results_run
        FOREIGN KEY (simulation_run_id) REFERENCES simulation_runs (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        ];
    }
}
