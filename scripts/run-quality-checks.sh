#!/usr/bin/env sh
set -eu

php tests/smoke_check.php
php tests/run.php
php scripts/migrate.php --dry-run
node scripts/check-js-syntax.mjs
