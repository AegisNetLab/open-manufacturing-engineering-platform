# Testing Guide

## Static Checks

Run PHP syntax checks:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Run the repository smoke test:

```bash
php tests/smoke_check.php
```

## Manual MVP Smoke Test

- Project Manager: create, edit, delete, and select a project.
- Layout Designer: drag objects, edit properties, save, reload, export PNG.
- Resource Manager: create machine/operator/tool resources, link layout elements where applicable.
- Process Designer: create Start, Operation and End nodes, connect them, validate, save and reload.
- Simulation: run a scenario and verify KPI values are stored.
- Results Dashboard: refresh results and verify the latest scenario appears.

## Current Test Scope

The MVP currently includes syntax and structure checks. Endpoint-level integration tests should be added once a dedicated test database configuration is introduced.
