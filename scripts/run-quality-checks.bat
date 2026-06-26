@echo off
setlocal enabledelayedexpansion

echo OpenMEP quality checks
echo ======================

echo.
echo [1/4] PHP syntax check
for /r %%f in (*.php) do (
    php -l "%%f" >nul
    if errorlevel 1 (
        echo PHP syntax check failed: %%f
        exit /b 1
    )
)

echo.
echo [2/4] JavaScript syntax check
node scripts/check-js-syntax.mjs
if errorlevel 1 exit /b 1

echo.
echo [3/4] Smoke check
php tests/smoke_check.php
if errorlevel 1 exit /b 1

echo.
echo [4/4] Unit tests
php tests/run.php
if errorlevel 1 exit /b 1

echo.
echo All quality checks passed.
exit /b 0