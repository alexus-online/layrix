#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -z "${ECF_WP_URL:-}" || -z "${ECF_WP_USER:-}" || -z "${ECF_WP_APP_PASSWORD:-}" ]]; then
  echo "Skipping ECF E2E REST checks."
  echo "Set ECF_WP_URL, ECF_WP_USER and ECF_WP_APP_PASSWORD to run them against a real WordPress site."
  exit 0
fi

SETTINGS_URL="${ECF_WP_URL%/}/wp-json/ecf-framework/v1/settings"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

echo "Running ECF E2E REST checks against ${ECF_WP_URL}..."

curl_json() {
  local method="$1"
  local url="$2"
  local body_file="${3:-}"

  if [[ -n "$body_file" ]]; then
    curl -sS -f -u "${ECF_WP_USER}:${ECF_WP_APP_PASSWORD}" \
      -H "Content-Type: application/json" \
      -X "$method" \
      --data @"$body_file" \
      "$url"
  else
    curl -sS -f -u "${ECF_WP_USER}:${ECF_WP_APP_PASSWORD}" \
      -H "Content-Type: application/json" \
      -X "$method" \
      "$url"
  fi
}

GET_RESPONSE_FILE="$TMP_DIR/get.json"
ORIGINAL_SETTINGS_FILE="$TMP_DIR/original-settings.json"
UPDATED_PAYLOAD_FILE="$TMP_DIR/updated-payload.json"
UPDATED_RESPONSE_FILE="$TMP_DIR/updated-response.json"
RESTORE_PAYLOAD_FILE="$TMP_DIR/restore-payload.json"
RESTORE_RESPONSE_FILE="$TMP_DIR/restore-response.json"

curl_json GET "$SETTINGS_URL" > "$GET_RESPONSE_FILE"

php -r '
$data = json_decode(file_get_contents($argv[1]), true);
if (!is_array($data) || empty($data["success"]) || !is_array($data["settings"])) {
    fwrite(STDERR, "Invalid settings GET response.\n");
    exit(1);
}
if (empty($data["meta"]["elementor_limit_snapshot"]) || empty($data["meta"]["elementor_debug_snapshot"])) {
    fwrite(STDERR, "Missing Elementor meta snapshot in GET response.\n");
    exit(1);
}
file_put_contents($argv[2], json_encode($data["settings"], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
' "$GET_RESPONSE_FILE" "$ORIGINAL_SETTINGS_FILE"

php -r '
$settings = json_decode(file_get_contents($argv[1]), true);
if (!is_array($settings)) {
    fwrite(STDERR, "Original settings are invalid.\n");
    exit(1);
}
$settings["github_update_checks_enabled"] = empty($settings["github_update_checks_enabled"]) ? "1" : "0";
$settings["interface_language"] = (($settings["interface_language"] ?? "en") === "de") ? "en" : "de";
if (!isset($settings["general_setting_favorites"]) || !is_array($settings["general_setting_favorites"])) {
    $settings["general_setting_favorites"] = [];
}
$settings["general_setting_favorites"]["root_font_size"] = empty($settings["general_setting_favorites"]["root_font_size"]) ? "1" : "0";
file_put_contents($argv[2], json_encode(["settings" => $settings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
' "$ORIGINAL_SETTINGS_FILE" "$UPDATED_PAYLOAD_FILE"

curl_json POST "$SETTINGS_URL" "$UPDATED_PAYLOAD_FILE" > "$UPDATED_RESPONSE_FILE"

php -r '
$original = json_decode(file_get_contents($argv[1]), true);
$updatedPayload = json_decode(file_get_contents($argv[2]), true);
$response = json_decode(file_get_contents($argv[3]), true);
if (!is_array($response) || empty($response["success"]) || !is_array($response["settings"])) {
    fwrite(STDERR, "Invalid settings POST response.\n");
    exit(1);
}
$returned = $response["settings"];
$expected = $updatedPayload["settings"];
$checks = [
    "github_update_checks_enabled",
    "interface_language",
];
foreach ($checks as $key) {
    if (($returned[$key] ?? null) !== ($expected[$key] ?? null)) {
        fwrite(STDERR, "Updated setting mismatch for {$key}.\n");
        exit(1);
    }
}
$favorite = $returned["general_setting_favorites"]["root_font_size"] ?? "0";
$expectedFavorite = $expected["general_setting_favorites"]["root_font_size"] ?? "0";
if ((string) $favorite !== (string) $expectedFavorite) {
    fwrite(STDERR, "Updated favorites state mismatch.\n");
    exit(1);
}
if (empty($response["meta"]["elementor_limit_snapshot"]) || empty($response["meta"]["elementor_debug_snapshot"])) {
    fwrite(STDERR, "Missing Elementor meta snapshot in POST response.\n");
    exit(1);
}
' "$ORIGINAL_SETTINGS_FILE" "$UPDATED_PAYLOAD_FILE" "$UPDATED_RESPONSE_FILE"

php -r '
$settings = json_decode(file_get_contents($argv[1]), true);
if (!is_array($settings)) {
    fwrite(STDERR, "Original settings are invalid for restore.\n");
    exit(1);
}
file_put_contents($argv[2], json_encode(["settings" => $settings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
' "$ORIGINAL_SETTINGS_FILE" "$RESTORE_PAYLOAD_FILE"

curl_json POST "$SETTINGS_URL" "$RESTORE_PAYLOAD_FILE" > "$RESTORE_RESPONSE_FILE"

php -r '
$original = json_decode(file_get_contents($argv[1]), true);
$response = json_decode(file_get_contents($argv[2]), true);
if (!is_array($response) || empty($response["success"]) || !is_array($response["settings"])) {
    fwrite(STDERR, "Invalid restore response.\n");
    exit(1);
}
$returned = $response["settings"];
$checks = [
    "github_update_checks_enabled",
    "interface_language",
];
foreach ($checks as $key) {
    if (($returned[$key] ?? null) !== ($original[$key] ?? null)) {
        fwrite(STDERR, "Restore mismatch for {$key}.\n");
        exit(1);
    }
}
$favorite = $returned["general_setting_favorites"]["root_font_size"] ?? "0";
$expectedFavorite = $original["general_setting_favorites"]["root_font_size"] ?? "0";
if ((string) $favorite !== (string) $expectedFavorite) {
    fwrite(STDERR, "Restore favorites state mismatch.\n");
    exit(1);
}
' "$ORIGINAL_SETTINGS_FILE" "$RESTORE_RESPONSE_FILE"

echo "ECF E2E REST checks passed."
