# Demo Project Seeder

OpenMEP includes a deterministic demo project seeder for local evaluation, screenshots, QA smoke testing, and onboarding new contributors.

## Command

```bash
php scripts/seed-demo-project.php
```

The command creates a complete reference project named `Demo: Automotive Assembly Line`.

## Options

```bash
php scripts/seed-demo-project.php --dry-run
php scripts/seed-demo-project.php --force
php scripts/seed-demo-project.php --name="My Demo Project"
```

`--dry-run` prints the planned objects without writing to MySQL.

`--force` replaces an existing project with the same name. Project deletion uses the normal foreign-key cascade rules.

## Seeded Content

The seeded project contains:

- one mixed-production project
- eight layout elements
- five resources
- eight operations
- normalized resource assignments through `operation_resources`
- nine process connections, including explicit rework routes
- one deterministic simulation scenario with random seed `42`

## Workflow Coverage

The seeded data is intentionally end-to-end ready:

1. Open the project from Project Manager.
2. Review the factory layout.
3. Review resources and capacities.
4. Validate the process model.
5. Run the baseline simulation scenario.
6. Review results and generate a report.

## Design Notes

The seeder writes through a dedicated service instead of raw SQL files so that it remains easy to evolve with the application schema. The seed data uses production-like entities while staying small enough for fast local testing.
