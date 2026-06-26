# Quality Assurance

OpenMEP uses a lightweight quality gate that can run on any standard PHP 8.x and Node.js installation without Composer, npm or Docker.

## Scope

The current quality gate covers:

- PHP syntax checks
- JavaScript syntax checks
- repository smoke checks
- validator unit tests
- GitHub Actions execution on pull requests and pushes to `main`

## Local execution

Run all checks from the repository root:

```bash
./scripts/run-quality-checks.sh
```

Individual checks can also be executed manually:

```bash
php tests/smoke_check.php
php tests/run.php
node scripts/check-js-syntax.mjs
```

## Unit test structure

```text
tests/
├── Support/
│   └── TestCase.php
├── Unit/
│   ├── ProcessValidatorTest.php
│   ├── ResourceValidatorTest.php
│   └── SimulationValidatorTest.php
└── run.php
```

The test runner intentionally avoids external dependencies in the MVP so that SMEs, students and open-source contributors can validate the project on simple shared hosting or local PHP installations.

## Current test focus

The first automated tests focus on validation logic because validation protects the engineering workflow before simulation execution:

- project/process payload integrity
- unique operation codes
- executable process graph constraints
- mandatory resource assignment for executable operations
- decision probability validation
- simulation scenario input validation
- resource type and capacity validation

## Pull request requirement

A pull request should not be merged unless the following command succeeds:

```bash
./scripts/run-quality-checks.sh
```

## Future improvements

Planned quality improvements:

- database-backed integration tests
- simulation KPI regression tests with deterministic seeds
- browser smoke tests for engineering screens
- coding style checks with PHP-CS-Fixer or PHP_CodeSniffer
- static analysis with PHPStan after Composer is introduced
