#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "Running ECF regression checks..."

rg -Fq "autosave_saving" includes/trait-ecf-asset-loading.php
rg -Fq "data-ecf-import-file" includes/trait-ecf-admin-page-sections.php
rg -Fq "Import preview" includes/trait-ecf-admin-page-sections.php
rg -Fq "handle_clear_debug_history" includes/trait-ecf-core-admin.php
rg -Fq "admin_post_ecf_clear_debug_history" includes/trait-ecf-hook-registration.php
rg -Fq "export_payload" includes/trait-ecf-native-elementor-handlers.php
rg -Fq "interface_language" includes/trait-ecf-admin-general.php
rg -Fq "filter_runtime_gettext" includes/trait-ecf-core-admin.php
rg -Fq "rest_admin_meta" includes/trait-ecf-rest-api.php
rg -Fq "data-ecf-refresh-system-info" includes/trait-ecf-admin-page-sections.php
rg -Fq "updateSystemInfoCards" assets/admin.js
rg -Fq "ecf-framework-de_DE.po" scripts/generate-de-language-files.php || true
test -x scripts/e2e-rest-check.sh

test -s languages/ecf-framework.pot
test -s languages/ecf-framework-de_DE.po
test -s languages/ecf-framework-de_DE.mo

echo "ECF regression checks passed."
