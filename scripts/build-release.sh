#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BUILD_DIR="$ROOT_DIR/dist"
STAGE_DIR="$BUILD_DIR/release/layrix"

VERSION="$(
  sed -n "s/^ \* Version: \(.*\)$/\1/p" "$ROOT_DIR/layrix.php" | head -n 1
)"

if [[ -z "$VERSION" ]]; then
  echo "Could not determine plugin version from layrix.php" >&2
  exit 1
fi

ZIP_NAME="layrix-${VERSION}-transition.zip"
ZIP_PATH="$BUILD_DIR/$ZIP_NAME"
SHA_PATH="$ZIP_PATH.sha256"

EXCLUDES=(
  ".env"
  "dist"
  ".git"
  ".github"
  ".gitignore"
  ".claude"
  ".vscode"
  ".DS_Store"
  "tmp"
  "node_modules"
  "tests"
  "scripts"
  "playwright.config.js"
  "package.json"
  "package-lock.json"
  "test-results"
  "website-quality-check"
)

rm -rf "$BUILD_DIR"
mkdir -p "$STAGE_DIR"

RSYNC_EXCLUDES=()
for item in "${EXCLUDES[@]}"; do
  RSYNC_EXCLUDES+=(--exclude "$item")
done

rsync -a "${RSYNC_EXCLUDES[@]}" "$ROOT_DIR/" "$STAGE_DIR/"

(
  cd "$BUILD_DIR/release"
  zip -rq "$ZIP_PATH" layrix
)

shasum -a 256 "$ZIP_PATH" > "$SHA_PATH"

echo "Built $ZIP_PATH"
echo "Checksum $SHA_PATH"
