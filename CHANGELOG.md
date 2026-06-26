
## API Smoke Tests v1

- Added `php scripts/api-smoke-test.php` for HTTP-level deployment checks.
- Added JSON output for CI integration.
- Added documentation in `docs/API_SMOKE_TESTS.md`.
- Added unit coverage for smoke test URL construction and default checks.



## Dashboard v1

- Added default Dashboard route.
- Added `/api/dashboard/summary.php`.
- Added application metrics, recent projects, latest results and readiness overview.
- Updated keyboard shortcuts and settings startup route.

## Project Pagination v1

- Added server-side Project Manager pagination.
- Added page size selector and pagination controls to the Project Manager UI.
- Added pagination metadata to `/api/projects/list.php`.
- Added repository-level pagination unit coverage.


## Unreleased

### Added
- Project Manager search, production type filtering, and configurable API sorting.
- Documentation for project search and filtering.


## Unreleased

### Added
- Added project duplication API and Project Manager UI action.
- Added transactional model copy with ID remapping for layout, resources, process graph, resource assignments, and scenarios.
- Added project duplication documentation.

## Unreleased

### Added
- Added lightweight API rate limiting with standard rate limit headers and HTTP 429 handling.
- Added rate limiting documentation and unit tests.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Structured JSON application logging with request IDs.
- `php scripts/tail-log.php` CLI log viewer.
- Observability documentation.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Maintenance utilities for backup retention and cleanup.
- `scripts/cleanup-backups.php` with dry-run support.
- Maintenance documentation.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Audit log table, service and API for traceable engineering changes.
- Project and Resource Manager audit events.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Added Project Diagnostics API and CLI readiness checker.
- Added `docs/PROJECT_DIAGNOSTICS.md`.



## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Normalized Process Designer resource assignment through `operation_resources`.
- Required resource quantity support for simulation resource slot allocation.
- Resource assignment documentation.

### Changed
- Process save/load now preserves `resource_id`, `resource_name`, `required_quantity`, and assignment metadata.
- Simulation model loading now prefers normalized operation-resource links over legacy metadata.


### Added
- Added Settings screen for local UI preferences.
- Added theme, startup route, reduced motion and compact table preferences.
- Added `Alt + 7` keyboard shortcut for Settings.
- Added Settings documentation.

## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Scenario Manager API and UI for reusable simulation configurations.
- Scenario validation tests and documentation.




## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Added system health endpoint at `/api/system/health.php`.
- Added CLI installation diagnostic script `php scripts/install-check.php`.
- Added health check documentation.


### Added
- Added simulation core primitives for event queues, clocks, jobs, FIFO queues, resource pools, statistics, KPIs and bottleneck analysis.
- Added unit tests for the simulation core.
- Added `docs/SIMULATION_CORE.md`.

### Changed
- Refactored `SimulationService` to use the new `App\Simulation\EventQueue` abstraction instead of depending directly on `SplPriorityQueue`.
- Extended the application autoloader for the `App\Simulation` namespace.

- Added lightweight PHP database migration runner.
- Added initial core schema migration.
- Added migration documentation and quality gate dry-run check.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Lightweight PHP unit test runner without external dependencies.
- Unit tests for process, resource and simulation validators.
- Local quality gate script for smoke checks, unit tests and JavaScript syntax checks.
- Quality assurance documentation and CI unit test execution.
- Case-sensitive autoloader directory mapping for Linux/macOS environments.
- Removed the runtime dependency on `mbstring` from resource validation.



## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Project package JSON export and import.
- Results Dashboard CSV export.
- Import/export documentation.


## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Replaced the initial simulation loop with an event-queue based Simulation Engine v2.
- Added FIFO operation queues, resource slot allocation, queue waiting time metrics and queue length summaries.
- Added simulation engine documentation.



## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Added process resource assignment in the Process Designer.
- Added stricter process validation for required resources, connection completeness and graph reachability.
- Added validation documentation.

### Changed
- Simulation validation now blocks executable operations without assigned resources.


### Added
- Shared ES2022 UI framework with event bus, toast manager, global status bar, confirmation dialog, validation helpers and DOM utilities.
- API lifecycle events for consistent loading, success and error handling.
- GitHub Actions quality workflow for PHP syntax checks, JavaScript syntax checks and smoke testing.
- Shared UI architecture documentation.

### Changed
- Application shell now includes a reusable global status bar and Bootstrap toast container.

# Changelog

## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added

- Project Manager CRUD foundation.
- Integrated Layout Designer v1 with MySQL persistence.
- Integrated Resource Manager v1.
- Integrated Process Designer v1 with validation and persistence.
- Integrated Simulation Engine v1 and Results Dashboard.
- Repository documentation, contribution guide, security policy, smoke test, and demo seed data.

### Improved

- Simulation resource allocation now respects resource quantity/capacity when assigning jobs to resources.


## API Hardening v1

- Added global API exception handling.
- Added HTTP method guards to endpoint scripts.
- Added malformed JSON request handling.
- Added machine-readable API error codes.
- Added OpenAPI 3.0 reference document.
- Added `docs/API_HARDENING.md`.
## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Reporting v1 with printable HTML simulation reports.
- Results Dashboard report actions for latest and selected simulation runs.


### Added
- Added lightweight release archive builder with SHA-256 checksum generation.
- Added release process documentation and unit test coverage.

## Accessibility v1

- Added skip link and semantic navigation landmarks.
- Added ARIA route announcements and active navigation state.
- Added keyboard shortcuts for top-level modules.
- Added focus-visible and reduced-motion CSS support.
- Added accessibility documentation.

## Backup & Restore v1

- Added SQL database backup script.
- Added destructive restore script with explicit confirmation.
- Added backup documentation.

## Unreleased

### Added
- Security Hardening v1 with baseline security headers and CSRF protection for unsafe API requests.

### Added
- Demo Project Seeder for creating an end-to-end sample automotive assembly project.
