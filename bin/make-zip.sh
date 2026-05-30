#!/usr/bin/env bash
# Build and package tradequote-ai into a distributable zip.
# Usage: bash bin/make-zip.sh [version]
# Requires: node/npm, composer, zip, rsync

set -euo pipefail

VERSION="${1:-$(grep "Version:" tradequote-ai/tradequote-ai.php | head -1 | awk '{print $NF}')}"
ZIP_NAME="tradequote-ai-${VERSION}.zip"

echo "==> Building TradeQuote AI v${VERSION}"

# 1. JS build
echo "--> npm install & build"
(cd tradequote-ai && npm ci && npm run build)

# 2. PHP deps (prod only)
echo "--> composer install (no-dev)"
(cd tradequote-ai && composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || echo "    composer not installed — vendor/ will be empty")

# 3. Assemble
echo "--> assembling dist/"
rm -rf dist
mkdir -p dist/tradequote-ai

rsync -a tradequote-ai/ dist/tradequote-ai/ \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='src' \
  --exclude='.github' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='package.json' \
  --exclude='package-lock.json' \
  --exclude='*.map' \
  --exclude='phpcs.xml' \
  --exclude='tests' \
  --exclude='.DS_Store'

# 4. Zip
echo "--> zipping to ${ZIP_NAME}"
(cd dist && zip -r "../${ZIP_NAME}" tradequote-ai/ -x "*.DS_Store")
rm -rf dist

echo "==> Done: ${ZIP_NAME}"
ls -lh "${ZIP_NAME}"
