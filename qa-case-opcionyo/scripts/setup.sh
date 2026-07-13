#!/usr/bin/env bash
# One-shot setup for the Opcion Yo QA case (macOS / Linux).
# Usage: bash scripts/setup.sh
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Installing PHP dependencies (composer)"
composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
  echo "==> Creating .env from .env.example"
  cp .env.example .env
fi

echo "==> Generating APP_KEY"
php artisan key:generate

echo "==> Creating SQLite database file"
mkdir -p database
touch database/database.sqlite

echo "==> Running migrations + seed"
php artisan migrate --force --seed

echo "==> Running the test suite (excluding opt-in integration tests)"
php artisan test --exclude-group=integration

echo ""
echo "✅ Setup complete. See RUN.md for how to run individual flows and the E2E suite."
