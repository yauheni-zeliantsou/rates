#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "==> PHP-CS-Fixer"
vendor/bin/php-cs-fixer fix --dry-run --diff

echo "==> PHPStan"
vendor/bin/phpstan analyse --memory-limit=512M
