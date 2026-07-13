# One-shot setup for the Opcion Yo QA case (Windows / PowerShell).
# Usage:  powershell -ExecutionPolicy Bypass -File scripts\setup.ps1
$ErrorActionPreference = 'Stop'

Set-Location (Join-Path $PSScriptRoot '..')

Write-Host '==> Installing PHP dependencies (composer)' -ForegroundColor Cyan
composer install --no-interaction --prefer-dist

if (-not (Test-Path '.env')) {
    Write-Host '==> Creating .env from .env.example' -ForegroundColor Cyan
    Copy-Item '.env.example' '.env'
}

Write-Host '==> Generating APP_KEY' -ForegroundColor Cyan
php artisan key:generate

Write-Host '==> Creating SQLite database file' -ForegroundColor Cyan
if (-not (Test-Path 'database')) { New-Item -ItemType Directory 'database' | Out-Null }
if (-not (Test-Path 'database\database.sqlite')) { New-Item -ItemType File 'database\database.sqlite' | Out-Null }

Write-Host '==> Running migrations + seed' -ForegroundColor Cyan
php artisan migrate --force --seed

Write-Host '==> Running the test suite (excluding opt-in integration tests)' -ForegroundColor Cyan
php artisan test --exclude-group=integration

Write-Host ''
Write-Host 'Setup complete. See RUN.md for individual flows and the E2E suite.' -ForegroundColor Green
