#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "Running ECF smoke checks..."

required_files=(
  "includes/trait-ecf-admin-general.php"
  "includes/trait-ecf-admin-page-sections.php"
  "includes/trait-ecf-asset-loading.php"
  "includes/trait-ecf-changelog.php"
  "includes/trait-ecf-core-admin.php"
  "includes/trait-ecf-design-math.php"
  "includes/trait-ecf-editor-preview.php"
  "includes/trait-ecf-elementor-status.php"
  "includes/trait-ecf-framework-config.php"
  "includes/trait-ecf-hook-registration.php"
  "includes/trait-ecf-native-elementor-data.php"
  "includes/trait-ecf-native-elementor-handlers.php"
  "includes/trait-ecf-output-css.php"
  "includes/trait-ecf-render-helpers.php"
  "includes/trait-ecf-rest-api.php"
  "includes/trait-ecf-settings-sanitizer.php"
  "includes/trait-ecf-updater.php"
)

extra_required_files=(
  "scripts/generate-pot.php"
  "scripts/generate-de-language-files.php"
  "scripts/e2e-rest-check.sh"
  "scripts/regression-check.sh"
  "languages/ecf-framework.pot"
)

for required_file in "${required_files[@]}"; do
  if [[ ! -f "$required_file" ]]; then
    echo "Missing required file: $required_file" >&2
    exit 1
  fi
done

for required_file in "${extra_required_files[@]}"; do
  if [[ ! -f "$required_file" ]]; then
    echo "Missing required file: $required_file" >&2
    exit 1
  fi
done

required_traits=(
  "ECF_Framework_Admin_General_Trait"
  "ECF_Framework_Admin_Page_Sections_Trait"
  "ECF_Framework_Asset_Loading_Trait"
  "ECF_Framework_Changelog_Trait"
  "ECF_Framework_Core_Admin_Trait"
  "ECF_Framework_Design_Math_Trait"
  "ECF_Framework_Editor_Preview_Trait"
  "ECF_Framework_Elementor_Status_Trait"
  "ECF_Framework_Config_Trait"
  "ECF_Framework_Hook_Registration_Trait"
  "ECF_Framework_Native_Elementor_Data_Trait"
  "ECF_Framework_Native_Elementor_Handlers_Trait"
  "ECF_Framework_Output_CSS_Trait"
  "ECF_Framework_Render_Helpers_Trait"
  "ECF_Framework_REST_API_Trait"
  "ECF_Framework_Settings_Sanitizer_Trait"
  "ECF_Framework_Updater_Trait"
)

for required_file in "${required_files[@]}"; do
  require_pattern="require_once __DIR__ . '/${required_file#./}'"
  if ! rg -Fq "$require_pattern" elementor-core-framework.php; then
    echo "Missing require_once for: $required_file" >&2
    exit 1
  fi
done

for required_trait in "${required_traits[@]}"; do
  if ! rg -Fq "use $required_trait;" elementor-core-framework.php; then
    echo "Missing trait use: $required_trait" >&2
    exit 1
  fi
done

php -l elementor-core-framework.php

while IFS= read -r php_file; do
  php -l "$php_file"
done < <(find includes -type f -name '*.php' | sort)

node --check assets/admin.js
node --check assets/editor.js

bash -n deploy.sh
php -l scripts/generate-pot.php
php -l scripts/generate-de-language-files.php
bash -n scripts/e2e-rest-check.sh
php scripts/generate-pot.php >/dev/null
php scripts/generate-de-language-files.php >/dev/null
bash scripts/regression-check.sh

if [[ -n "${ECF_WP_URL:-}" && -n "${ECF_WP_USER:-}" && -n "${ECF_WP_APP_PASSWORD:-}" ]]; then
  bash scripts/e2e-rest-check.sh
fi

duplicate_methods="$(
  rg -o 'function[[:space:]]+[A-Za-z0-9_]+' elementor-core-framework.php includes/*.php \
    | sed -E 's/.*function[[:space:]]+//' \
    | sort \
    | uniq -d
)"

if [[ -n "$duplicate_methods" ]]; then
  echo "Duplicate method names found:" >&2
  echo "$duplicate_methods" >&2
  exit 1
fi

echo "ECF smoke checks passed."
