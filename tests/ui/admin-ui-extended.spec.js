const fs = require('node:fs/promises');
const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  mutationNotAllowed,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  switchInterfaceLanguage,
  addLocalFontRow,
  getLocalFontRows,
  fillLocalFontRow,
  removeLocalFontRow,
  setImportFile,
  submitImport,
  downloadExport,
  searchClasses,
  openUtilityLibrary,
  toggleUtilityClass,
  openCustomStarterTier,
  getCustomStarterRows,
  addCustomStarterRow,
  fillCustomStarterRow,
  removeLastCustomStarterRow,
  generateBemClass,
  searchVariables,
  openFirstEditableVariable,
  openSystemDebugCard,
  clearDebugHistory,
  triggerClassSync,
  triggerNativeSync,
  waitForSuccessNotice,
  fetchRestSettings,
  updateRestSettings,
  reorderLayoutGroup,
  getLayoutOrder,
} = require('./helpers/ecf-admin');

test.describe('ECF extended admin UI flows', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test('language switch reloads and changes visible UI language', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    const select = page.locator('[data-ecf-general-field="interface_language"] select').first();
    const original = await select.inputValue();
    const target = original === 'de' ? 'en' : 'de';

    await switchInterfaceLanguage(page, target);
    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');
    await expect(page.locator('[data-ecf-general-field="interface_language"] select').first()).toHaveValue(target);

    if (target === 'de') {
      await expect(page.locator('[data-ecf-general-tab="website"]')).toContainText('Webseite');
    } else {
      await expect(page.locator('[data-ecf-general-tab="website"]')).toContainText('Website');
    }

    await openGeneralTab(page, 'ui');
    await switchInterfaceLanguage(page, original);
  });

  test('local font rows can be added, edited, persisted and removed', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const rows = await getLocalFontRows(page);
    const originalCount = await rows.count();
    const reusableLocalUrl = originalCount > 0
      ? await rows.nth(0).locator('.ecf-font-file-url').inputValue()
      : '';
    test.skip(!reusableLocalUrl, 'No existing local font upload is available on this test site.');

    await addLocalFontRow(page);
    await waitForSuccessNotice(page);
    await expect(rows).toHaveCount(originalCount + 1);

    const newRow = rows.nth(originalCount);
    await fillLocalFontRow(newRow, {
      key: 'ui-test-font',
      family: 'UITest Sans',
      url: reusableLocalUrl,
      weight: '500',
      style: 'italic',
      display: 'optional',
    });
    await newRow.locator('input').nth(3).blur();
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const reloadedRow = page.locator('[data-local-font-table] .ecf-font-file-row').nth(originalCount);
    await expect(reloadedRow.locator('input').nth(0)).toHaveValue('ui-test-font');
    await expect(reloadedRow.locator('input').nth(1)).toHaveValue('UITest Sans');
    await expect(reloadedRow.locator('.ecf-font-file-url')).toHaveValue(reusableLocalUrl);

    await removeLocalFontRow(reloadedRow);
    await waitForSuccessNotice(page);
    await expect(page.locator('[data-local-font-table] .ecf-font-file-row')).toHaveCount(originalCount);
  });

  test('import preview shows metadata for a valid file', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const fileInput = page.locator('[data-ecf-import-file]').first();
    const payload = {
      meta: {
        plugin_version: '0.2.4',
        schema_version: 1,
        exported_at: '2026-04-07 12:00:00',
      },
      settings: {
        root_font_size: '62.5',
        interface_language: 'de',
      },
    };

    await setImportFile(page, payload, 'ecf-ui-preview.json');

    const preview = page.locator('[data-ecf-import-preview]');
    await expect(preview).toBeVisible();
    await expect(page.locator('[data-ecf-import-preview-title]')).toContainText(/Import/i);
    await expect(page.locator('[data-ecf-import-preview-meta]')).toContainText('ecf-ui-preview.json');
    await expect(page.locator('[data-ecf-import-preview-meta]')).toContainText('0.2.4');
  });

  test('export download returns a valid JSON payload with metadata', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const download = await downloadExport(page);
    const filePath = await download.path();
    const content = await fs.readFile(filePath, 'utf8');
    const payload = JSON.parse(content);

    expect(payload).toHaveProperty('meta');
    expect(payload).toHaveProperty('settings');
    expect(payload.meta.plugin).toBe('ECF Framework');
    expect(typeof payload.meta.plugin_version).toBe('string');
    expect(payload.meta.plugin_version.length).toBeGreaterThan(0);
    expect(typeof payload.settings).toBe('object');
  });

  test('import submit applies settings and can be restored afterwards', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const nextRootSize = String(originalSettings.root_font_size || '') === '62.5' ? '100' : '62.5';
    const importPayload = {
      meta: {
        plugin: 'ECF Framework',
        plugin_version: '0.2.4',
        schema_version: 1,
        exported_at: '2026-04-08T12:00:00Z',
      },
      settings: {
        ...originalSettings,
        root_font_size: nextRootSize,
      },
    };

    try {
      await openPanel(page, 'sync');
      await setImportFile(page, importPayload, 'ecf-ui-import.json');
      await submitImport(page);

      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(
        page.locator('[data-ecf-general-field="root_font_size"] select').first()
      ).toHaveValue(nextRootSize);
    } finally {
      await updateRestSettings(page, originalSettings);
      await page.reload();
      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(
        page.locator('[data-ecf-general-field="root_font_size"] select').first()
      ).toHaveValue(String(originalSettings.root_font_size || '62.5'));
    }
  });

  test('class search filters visible class cards', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');

    const search = await searchClasses(page, 'button');

    const visibleItems = page.locator('[data-ecf-library-section="starter"] .ecf-starter-class-item:visible');
    await expect(visibleItems.first()).toBeVisible();
    await expect(visibleItems.first()).toContainText(/button/i);

    await search.fill('');
  });

  test('utility class toggle persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');

    await openUtilityLibrary(page);
    const item = page.locator('[data-ecf-library-section="utility"] .ecf-utility-class-item:visible').first();
    await expect(item).toBeVisible();

    const className = await item.getAttribute('data-class-name');
    const { originalChecked } = await toggleUtilityClass(page, className);
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openUtilityLibrary(page);

    const reloadedToggle = page.locator(`[data-ecf-library-section="utility"] [data-class-name="${className}"] .ecf-utility-class-toggle`).first();
    if (originalChecked) {
      await expect(reloadedToggle).not.toBeChecked();
    } else {
      await expect(reloadedToggle).toBeChecked();
    }

    await reloadedToggle.setChecked(originalChecked);
    await waitForSuccessNotice(page);
  });

  test('custom starter rows can be added, edited, persisted and removed', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openCustomStarterTier(page);

    const rows = await getCustomStarterRows(page);
    const originalCount = await rows.count();
    const uniqueName = `ecf-ui-flow-${Date.now().toString().slice(-6)}`;

    await addCustomStarterRow(page);
    const newRow = rows.nth(originalCount);
    await expect(newRow).toBeVisible();
    await fillCustomStarterRow(newRow, uniqueName);
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openCustomStarterTier(page);

    const reloadedRow = page.locator('[data-ecf-starter-custom-rows] .ecf-starter-custom-row').nth(originalCount);
    await expect(reloadedRow.locator('.ecf-custom-starter-name')).toHaveValue(uniqueName);

    await removeLastCustomStarterRow(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('[data-ecf-starter-custom-rows] .ecf-starter-custom-row')).toHaveCount(originalCount);
  });

  test('BEM generator can add a generated custom class that persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openCustomStarterTier(page);

    const rows = await getCustomStarterRows(page);
    const originalCount = await rows.count();
    const blockName = `ui-bem-${Date.now().toString().slice(-6)}`;

    await page.locator('[data-ecf-bem-preset]').selectOption('custom');
    await page.locator('[data-ecf-bem-block]').fill(blockName);
    await expect(page.locator('[data-ecf-bem-preview]')).toContainText(`ecf-${blockName}`);
    await generateBemClass(page, blockName);
    await waitForSuccessNotice(page);
    await expect(rows).toHaveCount(originalCount + 1);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openCustomStarterTier(page);
    await expect(page.locator('[data-ecf-starter-custom-rows] .ecf-custom-starter-name').nth(originalCount)).toHaveValue(`ecf-${blockName}`);

    await removeLastCustomStarterRow(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('[data-ecf-starter-custom-rows] .ecf-starter-custom-row')).toHaveCount(originalCount);
  });

  test('variable search can open the edit modal when editable foreign variables exist', async ({ page }, testInfo) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'variables');

    await searchVariables(page, 'text');
    const opened = await openFirstEditableVariable(page);
    test.skip(!opened, 'No editable foreign variables available on this test site.');
    await expect(page.locator('[data-ecf-search-edit-modal]')).toBeVisible();
    await page.locator('[data-ecf-search-edit-close]').first().click();
    await expect(page.locator('[data-ecf-search-edit-modal]')).toBeHidden();
  });

  test('debug history can be cleared when entries exist', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'system');

    const debugCard = await openSystemDebugCard(page);

    const items = page.locator('.ecf-system-debug-card__history-item');
    const count = await items.count();
    test.skip(count === 0, 'No debug history available on this test site.');

    await clearDebugHistory(page);

    await openPluginPage(page);
    await openGeneralTab(page, 'system');
    const refreshedDebugCard = await openSystemDebugCard(page);
    await expect(refreshedDebugCard.locator('.ecf-system-debug-card__history-item')).toHaveCount(0);
  });

  test('class library sync can be triggered on mutation-enabled test sites', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run Elementor-writing UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');

    await triggerClassSync(page);

    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
    await expect(page.locator('.ecf-panel[data-panel="utilities"]')).toBeVisible();
  });

  test('native Elementor sync can be triggered on mutation-enabled test sites', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run Elementor-writing UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    await triggerNativeSync(page);

    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
    await expect(page.locator('.ecf-panel[data-panel="sync"]')).toBeVisible();
  });

  test('layout drag and drop persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalOrder = await getLayoutOrder(page, 'components-website');
    const reordered = [
      'website-widths',
      'website-type-size',
      ...originalOrder.filter((id) => id !== 'website-widths' && id !== 'website-type-size'),
    ];

    await reorderLayoutGroup(page, 'components-website', 'website-widths', 'website-type-size');
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(reordered);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(reordered);

    await reorderLayoutGroup(page, 'components-website', 'website-type-size', 'website-widths');
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(originalOrder);
  });
});
