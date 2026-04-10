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
  getLocalFontFamilies,
  fillLocalFontRow,
  removeLocalFontRow,
  importLibraryFontForField,
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
  waitForRestSetting,
  fetchRestSettings,
  updateRestSettings,
  reorderLayoutGroup,
  getLayoutOrder,
  setLayoutColumns,
  getLayoutColumns,
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

    try {
      await switchInterfaceLanguage(page, target);
      await waitForRestSetting(page, 'interface_language', target);
      await openPluginPage(page);
      await openGeneralTab(page, 'ui');
      await expect(page.locator('[data-ecf-general-field="interface_language"] select').first()).toHaveValue(target);

      if (target === 'de') {
        await expect(page.locator('[data-ecf-general-tab="website"]')).toContainText('Webseite');
      } else {
        await expect(page.locator('[data-ecf-general-tab="website"]')).toBeVisible();
      }
    } finally {
      await openPluginPage(page);
      await openGeneralTab(page, 'ui');
      if ((await page.locator('[data-ecf-general-field="interface_language"] select').first().inputValue()) !== original) {
        await switchInterfaceLanguage(page, original);
        await waitForRestSetting(page, 'interface_language', original);
      }
    }
  });

  test('local font rows can be added, edited, persisted and removed', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const rows = await getLocalFontRows(page);
    const originalCount = await rows.count();
    const originalFamilies = await getLocalFontFamilies(page);
    const targetFamily = ['Manrope', 'Poppins', 'Nunito', 'Merriweather'].find((family) => !originalFamilies.includes(family));
    test.skip(!targetFamily, 'No unused library font is available on this test site.');

    await openGeneralTab(page, 'website');
    await importLibraryFontForField(page, 'base_font_family', targetFamily);
    await waitForSuccessNotice(page);
    await openPanel(page, 'typography');
    await expect(rows).toHaveCount(originalCount + 1);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const reloadedRow = page.locator('[data-local-font-table] .ecf-font-file-row').nth(originalCount);
    await expect(reloadedRow).toContainText(targetFamily);

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
        plugin_version: '0.3.1',
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
    await expect(page.locator('[data-ecf-import-preview-meta]')).toContainText('0.3.1');
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
    expect(payload.meta.plugin).toBe('Layrix');
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
        plugin: 'Layrix',
        plugin_version: '0.3.1',
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
    await reorderLayoutGroup(page, 'components-website', 'website-widths', 'website-type-size');
    await waitForSuccessNotice(page);
    await expect.poll(async () => JSON.stringify(await getLayoutOrder(page, 'components-website'))).not.toBe(JSON.stringify(originalOrder));
    const reordered = await getLayoutOrder(page, 'components-website');

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(reordered);

    await reorderLayoutGroup(page, 'components-website', 'website-type-size', 'website-widths');
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(originalOrder);
  });

  test('website card order persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalOrder = await getLayoutOrder(page, 'components-website');
    const reordered = [
      'website-base-colors',
      ...originalOrder.filter((id) => id !== 'website-base-colors'),
    ];

    await reorderLayoutGroup(page, 'components-website', 'website-base-colors', originalOrder[0]);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(reordered);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(reordered);

    let currentOrder = await getLayoutOrder(page, 'components-website');
    for (let index = 0; index < originalOrder.length; index += 1) {
      const expectedItem = originalOrder[index];
      if (currentOrder[index] === expectedItem) {
        continue;
      }

      await reorderLayoutGroup(page, 'components-website', expectedItem, currentOrder[index]);
      await waitForSuccessNotice(page);
      currentOrder = await getLayoutOrder(page, 'components-website');
    }

    await expect.poll(async () => getLayoutOrder(page, 'components-website')).toEqual(originalOrder);
  });

  test('website cards show visible drag handles for reordering', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const items = page.locator('[data-ecf-layout-group="components-website"] > [data-ecf-layout-item]');
    const itemCount = await items.count();
    expect(itemCount).toBeGreaterThan(1);

    for (let index = 0; index < itemCount; index += 1) {
      const handle = items.nth(index).locator('[data-ecf-layout-handle][data-ecf-layout-handle-for="components-website"]').first();
      await expect(handle).toBeVisible();
    }
  });

  test('website type and size cards can be reordered and columnized', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalOrder = await getLayoutOrder(page, 'components-website-type-size');
    const originalColumns = await getLayoutColumns(page, 'components-website-type-size');
    const targetColumns = originalColumns === 3 ? 2 : 3;
    const reordered = [
      'type-size-body',
      ...originalOrder.filter((id) => id !== 'type-size-body'),
    ];

    await setLayoutColumns(page, 'components-website-type-size', targetColumns);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(targetColumns);

    await reorderLayoutGroup(page, 'components-website-type-size', 'type-size-body', originalOrder[0]);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'components-website-type-size')).toEqual(reordered);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(targetColumns);
    await expect.poll(async () => getLayoutOrder(page, 'components-website-type-size')).toEqual(reordered);

    await setLayoutColumns(page, 'components-website-type-size', originalColumns);
    await waitForSuccessNotice(page);

    let currentOrder = await getLayoutOrder(page, 'components-website-type-size');
    for (let index = 1; index < originalOrder.length; index += 1) {
      const expectedItem = originalOrder[index];
      if (currentOrder[index] === expectedItem) {
        continue;
      }

      await reorderLayoutGroup(page, 'components-website-type-size', expectedItem, originalOrder[index - 1]);
      await waitForSuccessNotice(page);
      currentOrder = await getLayoutOrder(page, 'components-website-type-size');
    }

    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(originalColumns);
    await expect.poll(async () => getLayoutOrder(page, 'components-website-type-size')).toEqual(originalOrder);
  });

  test('website type and size layout shows both icon toggles and highlights the active desktop mode', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const innerHelp = page.locator('[data-ecf-layout-columns-toolbar][data-group="components-website-type-size"] .ecf-layout-columns-toolbar__help').first();
    const toggleButtons = page.locator('[data-ecf-layout-columns-toolbar][data-group="components-website-type-size"] [data-ecf-layout-columns-btn]:visible');
    const oneColumnButtons = page.locator('[data-ecf-layout-columns-toolbar][data-group="components-website-type-size"] [data-ecf-layout-columns-btn][data-ecf-layout-columns="1"]');
    await expect(innerHelp).toContainText(/2 or 3 columns|2 oder 3 Spalten/i);

    const originalInner = await getLayoutColumns(page, 'components-website-type-size');
    await expect(oneColumnButtons).toHaveCount(0);
    await expect(toggleButtons).toHaveCount(2);
    await expect(toggleButtons.nth(0)).toHaveAttribute('aria-pressed', originalInner === 2 ? 'true' : 'false');
    await expect(toggleButtons.nth(1)).toHaveAttribute('aria-pressed', originalInner === 3 ? 'true' : 'false');

    await setLayoutColumns(page, 'components-website-type-size', 3);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(3);
    await expect(toggleButtons).toHaveCount(2);
    await expect(toggleButtons.nth(0)).toHaveAttribute('aria-pressed', 'false');
    await expect(toggleButtons.nth(1)).toHaveAttribute('aria-pressed', 'true');

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(3);

    await setLayoutColumns(page, 'components-website-type-size', originalInner);
    await waitForSuccessNotice(page);
  });

  test('website type and size cards do not overlap in two-column mode and keep font notes out of the block', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalColumns = await getLayoutColumns(page, 'components-website-type-size');

    const changedToTwo = await setLayoutColumns(page, 'components-website-type-size', 2);
    if (changedToTwo) {
      await waitForSuccessNotice(page);
    }
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(2);

    const bodyCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-body"]').first();
    const rootCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-root"]').first();
    const groupNote = page.locator('.ecf-form-grid--website-type-size .ecf-font-family-note--group');

    const bodyBox = await bodyCard.boundingBox();
    const rootBox = await rootCard.boundingBox();

    expect(bodyBox).not.toBeNull();
    expect(rootBox).not.toBeNull();
    const bodyRight = bodyBox.x + bodyBox.width;
    const bodyBottom = bodyBox.y + bodyBox.height;
    const rootRight = rootBox.x + rootBox.width;
    const rootBottom = rootBox.y + rootBox.height;
    const overlaps = !(
      bodyRight <= rootBox.x + 2 ||
      rootRight <= bodyBox.x + 2 ||
      bodyBottom <= rootBox.y + 2 ||
      rootBottom <= bodyBox.y + 2
    );
    expect(overlaps).toBe(false);
    expect(rootBox.x).toBeGreaterThan(bodyBox.x + 40);
    expect(Math.abs(rootBox.y - bodyBox.y)).toBeLessThan(80);
    await expect(groupNote).toHaveCount(0);

    const restoredOriginal = await setLayoutColumns(page, 'components-website-type-size', originalColumns);
    if (restoredOriginal) {
      await waitForSuccessNotice(page);
    }
  });

  test('website type and size uses masonry-like packing in two-column mode', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalColumns = await getLayoutColumns(page, 'components-website-type-size');
    const changedToTwo = await setLayoutColumns(page, 'components-website-type-size', 2);
    if (changedToTwo) {
      await waitForSuccessNotice(page);
    }
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(2);

    const bodyCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-body"]').first();
    const rootCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-root"]').first();
    const baseFontCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-base-font"]').first();
    const headingFontCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-heading-font"]').first();

    const bodyBox = await bodyCard.boundingBox();
    const rootBox = await rootCard.boundingBox();
    const baseFontBox = await baseFontCard.boundingBox();
    const headingFontBox = await headingFontCard.boundingBox();

    expect(bodyBox).not.toBeNull();
    expect(rootBox).not.toBeNull();
    expect(baseFontBox).not.toBeNull();
    expect(headingFontBox).not.toBeNull();

    expect(rootBox.x).toBeGreaterThan(bodyBox.x + 40);
    expect(Math.abs(baseFontBox.x - rootBox.x)).toBeLessThan(80);
    expect(Math.abs(headingFontBox.x - bodyBox.x)).toBeLessThan(80);
    expect(baseFontBox.y).toBeLessThan(headingFontBox.y - 10);
    expect(baseFontBox.y).toBeLessThanOrEqual(bodyBox.y + bodyBox.height + 12);

    const restoredOriginal = await setLayoutColumns(page, 'components-website-type-size', originalColumns);
    if (restoredOriginal) {
      await waitForSuccessNotice(page);
    }
  });

  test('website type and size uses masonry-like packing in three-column mode', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalColumns = await getLayoutColumns(page, 'components-website-type-size');
    const changedToThree = await setLayoutColumns(page, 'components-website-type-size', 3);
    if (changedToThree) {
      await waitForSuccessNotice(page);
    }
    await expect.poll(async () => getLayoutColumns(page, 'components-website-type-size')).toBe(3);

    const bodyCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-body"]').first();
    const rootCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-root"]').first();
    const baseFontCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-base-font"]').first();
    const headingFontCard = page.locator('[data-ecf-layout-group="components-website-type-size"] > [data-ecf-layout-item="type-size-heading-font"]').first();

    const bodyBox = await bodyCard.boundingBox();
    const rootBox = await rootCard.boundingBox();
    const baseFontBox = await baseFontCard.boundingBox();
    const headingFontBox = await headingFontCard.boundingBox();

    expect(bodyBox).not.toBeNull();
    expect(rootBox).not.toBeNull();
    expect(baseFontBox).not.toBeNull();
    expect(headingFontBox).not.toBeNull();

    expect(rootBox.x).toBeGreaterThan(bodyBox.x + 40);
    expect(baseFontBox.x).toBeGreaterThan(rootBox.x + 40);
    expect(Math.abs(headingFontBox.x - rootBox.x)).toBeLessThan(80);
    expect(headingFontBox.y).toBeGreaterThan(rootBox.y + rootBox.height - 8);
    expect(headingFontBox.y).toBeLessThanOrEqual(bodyBox.y + bodyBox.height + 24);

    const restoredOriginal = await setLayoutColumns(page, 'components-website-type-size', originalColumns);
    if (restoredOriginal) {
      await waitForSuccessNotice(page);
    }
  });

  test('help panel dashboard cards use masonry-like packing', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'help');

    const startCard = page.locator('[data-ecf-layout-group="help-main"] > [data-ecf-layout-item="help-start"]').first();
    const quickCard = page.locator('[data-ecf-layout-group="help-main"] > [data-ecf-layout-item="help-quick"]').first();
    const changelogCard = page.locator('[data-ecf-layout-group="help-main"] > [data-ecf-layout-item="help-changelog-link"]').first();
    const diagnosticsCard = page.locator('[data-ecf-layout-group="help-main"] > [data-ecf-layout-item="help-diagnostics"]').first();

    const startBox = await startCard.boundingBox();
    const quickBox = await quickCard.boundingBox();
    const changelogBox = await changelogCard.boundingBox();
    const diagnosticsBox = await diagnosticsCard.boundingBox();

    expect(startBox).not.toBeNull();
    expect(quickBox).not.toBeNull();
    expect(changelogBox).not.toBeNull();
    expect(diagnosticsBox).not.toBeNull();

    const columns = [startBox.x, quickBox.x, changelogBox.x, diagnosticsBox.x]
      .reduce((groups, value) => {
        if (!groups.some((group) => Math.abs(group - value) < 80)) {
          groups.push(value);
        }
        return groups;
      }, []);
    const rows = [startBox.y, quickBox.y, changelogBox.y, diagnosticsBox.y]
      .reduce((groups, value) => {
        if (!groups.some((group) => Math.abs(group - value) < 20)) {
          groups.push(value);
        }
        return groups;
      }, []);

    expect(columns.length).toBeGreaterThanOrEqual(2);
    expect(rows.length).toBeGreaterThanOrEqual(2);
  });
});
