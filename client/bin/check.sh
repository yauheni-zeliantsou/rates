#!/bin/sh
set -e

cd "$(dirname "$0")/.."

echo "==> ESLint"
npx eslint js

echo "==> Prettier"
npx prettier --check .