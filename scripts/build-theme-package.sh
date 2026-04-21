#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_DIR_NAME="$(basename "$ROOT_DIR")"
VERSION="$(php -r '$style = file_get_contents($argv[1]); preg_match("/Version:\\s*(.+)/", $style, $m); echo trim($m[1] ?? "");' "$ROOT_DIR/style.css")"
OUTPUT_DIR="$ROOT_DIR/downloads"
ZIP_PATH="$OUTPUT_DIR/${THEME_DIR_NAME}-${VERSION}.zip"
MANIFEST_PATH="$OUTPUT_DIR/theme.json"
MANIFEST_DIR="$OUTPUT_DIR"
DETAILS_URL="https://github.com/blueflamingo-solutions/generatepress-child-versioning"
DOWNLOAD_URL="${DETAILS_URL}/raw/main/${THEME_DIR_NAME}-${VERSION}.zip"
TODAY="$(date +%F)"
VERSIONING_REPO_DIR="${VERSIONING_REPO_DIR:-$HOME/Documents/work/BlueFlamingo/generatepress-child-versioning}"

if [[ -z "$VERSION" ]]; then
  echo "Could not read theme version from style.css"
  exit 1
fi

TMP_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

mkdir -p "$OUTPUT_DIR"
mkdir -p "$MANIFEST_DIR"
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

php -r '
$manifestPath = $argv[1];
$themeDirName = $argv[2];
$version = $argv[3];
$detailsUrl = $argv[4];
$downloadUrl = $argv[5];
$today = $argv[6];

$existing = [];
if (file_exists($manifestPath)) {
    $decoded = json_decode((string) file_get_contents($manifestPath), true);
    if (is_array($decoded)) {
        $existing = $decoded;
    }
}

$releaseNotes = [];
if (! empty($existing["release_notes"]) && is_array($existing["release_notes"])) {
    foreach ($existing["release_notes"] as $note) {
        $note = trim((string) $note);
        if ($note !== "") {
            $releaseNotes[] = $note;
        }
    }
}

$themeSlug = strtolower(preg_replace("/[^a-z0-9]+/", "_", $themeDirName));
$manifest = [
    "theme" => trim($themeSlug, "_"),
    "version" => $version,
    "details_url" => $detailsUrl,
    "download_url" => $downloadUrl,
    "tested" => (string) ($existing["tested"] ?? "6.9"),
    "requires_php" => (string) ($existing["requires_php"] ?? "8.1"),
    "last_updated" => $today,
    "release_notes" => $releaseNotes,
];

file_put_contents(
    $manifestPath,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
' "$MANIFEST_PATH" "$THEME_DIR_NAME" "$VERSION" "$DETAILS_URL" "$DOWNLOAD_URL" "$TODAY"

if [[ -d "$VERSIONING_REPO_DIR/.git" ]]; then
  cp "$ZIP_PATH" "$VERSIONING_REPO_DIR/${THEME_DIR_NAME}-${VERSION}.zip"
  cp "$MANIFEST_PATH" "$VERSIONING_REPO_DIR/theme.json"
  echo "Synced files to $VERSIONING_REPO_DIR"
else
  echo "Versioning repo not found at $VERSIONING_REPO_DIR"
fi

echo "Created $ZIP_PATH"
echo "Updated $MANIFEST_PATH"
