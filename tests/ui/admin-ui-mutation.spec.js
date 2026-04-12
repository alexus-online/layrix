const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  mutationNotAllowed,
  loginToWordPress,
  openPluginPage,
  openPanel,
  setImportFileFromPath,
  getImportPreview,
  submitImport,
  downloadExport,
  waitForSuccessNotice,
  triggerNativeSync,
  triggerClassSync,
  triggerClassCleanup,
  fetchRestSettings,
  updateRestSettings,
  ensureUiFlowDefaults,
  waitForVariableList,
  selectVariableRow,
  bulkDeleteSelected,
  searchVariables,
  deleteSearchResult,
} = require('./helpers/ecf-admin');

function cloneSettings(settings) {
  return JSON.parse(JSON.stringify(settings || {}));
}

function buildUniqueColorName() {
  return `ecf-ui-flow-${Date.now().toString().slice(-6)}`;
}

function appendUniqueColor(settings, name) {
  const next = cloneSettings(settings);
  next.colors = Array.isArray(next.colors) ? next.colors.slice() : [];
  next.colors.push({
    name,
    value: '#123456',
    format: 'hex',
  });
  return next;
}

function appendUniqueCustomStarter(settings, name) {
  const next = cloneSettings(settings);
  next.starter_classes = next.starter_classes || {};
  next.starter_classes.custom = Array.isArray(next.starter_classes.custom)
    ? next.starter_classes.custom.slice()
    : [];

  next.starter_classes.custom.push({
    enabled: true,
    name,
    category: 'custom',
  });

  return next;
}

test.describe('ECF mutation and roundtrip UI flows', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await ensureUiFlowDefaults(page);
  });

  test('exported settings file can be previewed and re-imported', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const download = await downloadExport(page);
    const filePath = await download.path();

    await setImportFileFromPath(page, filePath);

    const preview = await getImportPreview(page);
    await expect(preview.root).toBeVisible();
    await expect(preview.meta).not.toBeEmpty();

    await submitImport(page);
    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
    await expect(page.locator('.ecf-wrap')).toBeVisible();
  });

  test('seeded ECF variable stays available in Layrix after bulk delete because settings remain the source of truth', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run Elementor-writing UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const uniqueName = buildUniqueColorName();
    const seededSettings = appendUniqueColor(originalSettings, uniqueName);

    try {
      await updateRestSettings(page, seededSettings);
      await openPanel(page, 'sync');
      await triggerNativeSync(page);
      await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();

      await openPanel(page, 'variables');
      await waitForVariableList(page, 'ecf');
      await expect(page.locator('#ecf-varlist-ecf .ecf-var-row').filter({ hasText: uniqueName })).toHaveCount(1);

      await selectVariableRow(page, 'ecf', uniqueName);
      await bulkDeleteSelected(page, 'ecf');
      await expect(page.locator('#ecf-varlist-ecf .ecf-var-row').filter({ hasText: uniqueName })).toHaveCount(1);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('seeded ECF variable stays searchable after global delete because Layrix settings still define it', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run Elementor-writing UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const uniqueName = buildUniqueColorName();
    const seededSettings = appendUniqueColor(originalSettings, uniqueName);

    try {
      await updateRestSettings(page, seededSettings);
      await openPanel(page, 'sync');
      await triggerNativeSync(page);
      await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();

      await openPluginPage(page);
      await openPanel(page, 'variables');
      await searchVariables(page, uniqueName);
      await expect(page.locator('#ecf-global-search-results')).toContainText(uniqueName);

      await deleteSearchResult(page, uniqueName);
      await searchVariables(page, uniqueName);
      await expect(page.locator('#ecf-global-search-results')).toContainText(uniqueName);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('seeded ECF class can be cleaned up and settings can be restored', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run Elementor-writing UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const uniqueName = `ecf-ui-cleanup-${Date.now().toString().slice(-6)}`;
    const seededSettings = appendUniqueCustomStarter(originalSettings, uniqueName);

    try {
      await updateRestSettings(page, seededSettings);

      await openPanel(page, 'utilities');
      await triggerClassSync(page);
      await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();

      await openPanel(page, 'sync');
      const cleanupButton = page.locator('form:has(input[name="action"][value="ecf_class_cleanup"]) button[type="submit"]').first();
      await expect(cleanupButton).toBeVisible();
      await expect(cleanupButton).toBeEnabled();

      await triggerClassCleanup(page);
      await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });
});
