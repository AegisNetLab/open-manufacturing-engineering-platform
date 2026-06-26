USE openmep;

INSERT INTO projects (name, description, production_type, shift_length_minutes, created_at, updated_at)
VALUES ('Demo Manufacturing Cell', 'Demo data for OpenMEP MVP smoke testing.', 'serial', 480, NOW(), NOW());

SET @project_id = LAST_INSERT_ID();

INSERT INTO layout_elements
(project_id, name, element_type, x_position, y_position, width, height, rotation, color, metadata_json, created_at, updated_at)
VALUES
(@project_id, 'Raw Material Warehouse', 'storage', 40, 60, 180, 120, 0, '#33691E', JSON_OBJECT('library_id', 'warehouse'), NOW(), NOW()),
(@project_id, 'CNC-01', 'machine', 280, 80, 144, 96, 0, '#1565C0', JSON_OBJECT('library_id', 'cnc'), NOW(), NOW()),
(@project_id, 'Inspection-01', 'machine', 500, 80, 120, 80, 0, '#006064', JSON_OBJECT('library_id', 'inspection'), NOW(), NOW()),
(@project_id, 'Finished Goods Warehouse', 'storage', 700, 60, 180, 120, 0, '#558B2F', JSON_OBJECT('library_id', 'warehouse'), NOW(), NOW());

INSERT INTO resources (project_id, name, resource_type, quantity, metadata_json, created_at, updated_at)
VALUES
(@project_id, 'CNC-01', 'machine', 1, JSON_OBJECT('layout_element_name', 'CNC-01'), NOW(), NOW()),
(@project_id, 'Inspector', 'operator', 1, JSON_OBJECT(), NOW(), NOW());

INSERT INTO operations
(project_id, operation_code, name, linked_layout_element_id, cycle_time_seconds, setup_time_seconds, batch_size, scrap_rate, rework_rate, metadata_json, created_at, updated_at)
VALUES
(@project_id, 'START', 'Start', NULL, 0, 0, 1, 0, 0, JSON_OBJECT('node_type', 'start'), NOW(), NOW()),
(@project_id, 'OP10', 'CNC Milling', (SELECT id FROM layout_elements WHERE project_id = @project_id AND name = 'CNC-01' LIMIT 1), 270, 0, 1, 1.0, 0, JSON_OBJECT('node_type', 'operation', 'resource_name', 'CNC-01'), NOW(), NOW()),
(@project_id, 'OP20', 'Inspection', (SELECT id FROM layout_elements WHERE project_id = @project_id AND name = 'Inspection-01' LIMIT 1), 90, 0, 1, 0.5, 0, JSON_OBJECT('node_type', 'inspection', 'resource_name', 'Inspector'), NOW(), NOW()),
(@project_id, 'END', 'End', NULL, 0, 0, 1, 0, 0, JSON_OBJECT('node_type', 'end'), NOW(), NOW());

SET @start_id = (SELECT id FROM operations WHERE project_id = @project_id AND operation_code = 'START');
SET @op10_id = (SELECT id FROM operations WHERE project_id = @project_id AND operation_code = 'OP10');
SET @op20_id = (SELECT id FROM operations WHERE project_id = @project_id AND operation_code = 'OP20');
SET @end_id = (SELECT id FROM operations WHERE project_id = @project_id AND operation_code = 'END');

INSERT INTO process_connections (source_operation_id, target_operation_id, connection_type, probability, metadata_json)
VALUES
(@start_id, @op10_id, 'normal', 100, JSON_OBJECT()),
(@op10_id, @op20_id, 'normal', 100, JSON_OBJECT()),
(@op20_id, @end_id, 'normal', 100, JSON_OBJECT());
