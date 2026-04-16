#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_DIR_NAME="$(basename "$ROOT_DIR")"
VERSION="$(php -r '$style = file_get_contents($argv[1]); preg_match("/Version:\\s*(.+)/", $style, $m); echo trim($m[1] ?? "");' "$ROOT_DIR/style.css")"

if [[ -z "$VERSION" ]]; then
  echo "Could not read theme version from style.css"
  exit 1
fi

OUTPUT_DIR="$ROOT_DIR/downloads"
ZIP_PATH="$OUTPUT_DIR/${THEME_DIR_NAME}-${VERSION}.zip"
TMP_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

mkdir -p "$OUTPUT_DIR"
rsync -a \
  --exclude '.git' \
  --exclude '.github' \
  --exclude 'node_modules' \
  --exclude 'downloads' \
  --exclude 'scripts' \
  --exclude '.DS_Store' \
  "$ROOT_DIR/" "$TMP_DIR/$THEME_DIR_NAME/"

cd "$TMP_DIR"
zip -qr "$ZIP_PATH" "$THEME_DIR_NAME"

echo "Created $ZIP_PATH"
