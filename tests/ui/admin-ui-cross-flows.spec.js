const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  getGeneralField,
  getBodyTextSizeState,
  selectBaseFontFamilyPreset,
  getBaseFontFamilyState,
  clickRemoveSelectedLocalFont,
  getRootCssVariable,
  getFrontendStylesheetText,
  getFrontendTypographySnapshot,
  toggleGeneralFavorite,
  getFavoriteCard,
  getFieldTooltipText,
  getFavoriteToggleTip,
  selectDesignPreset,
  selectDesignMode,
  addTokenRow,
  removeTokenRow,
  getTokenRowCount,
  fillColorRow,
  setTypographyScaleMaxBase,
  getTypographyPreviewRow,
  setSpacingMaxBase,
  getSpacingPreviewRow,
  switchInterfaceLanguage,
  getLocalFontFamilies,
  importLibraryFontForField,
  getLocalFontRows,
  fillLocalFontRow,
  openChangelogModal,
  closeChangelogModal,
  mockClipboard,
  getCopiedTexts,
  setImportFile,
  submitImport,
  waitForSuccessNotice,
  waitForErrorNotice,
  waitForAutosaveIdle,
  fetchRestSettings,
  waitForRestSetting,
  updateRestSettings,
  ensureUiFlowDefaults,
  getSiteOrigin,
  reorderLayoutGroup,
  getLayoutOrder,
} = require('./helpers/ecf-admin');

function cloneSettings(settings) {
  return JSON.parse(JSON.stringify(settings || {}));
}

function uniqueSuffix() {
  return Date.now().toString().slice(-6);
}

test.describe('ECF cross-panel UI flows', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await ensureUiFlowDefaults(page);
  });

  test('base body text size follows the active type scale maximum for the base step', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const seededSettings = cloneSettings(originalSettings);
    seededSettings.root_font_size = '100';
    seededSettings.base_body_text_size = '16px';
    seededSettings.typography.scale.base_index = 'm';

    try {
      await updateRestSettings(page, seededSettings);
      await openPluginPage(page);
      await openPanel(page, 'typography');
      await waitForAutosaveIdle(page);

      await setTypographyScaleMaxBase(page, '21');
      await waitForRestSetting(page, 'base_body_text_size', '21px');

      await openGeneralTab(page, 'website');
      const state = await getBodyTextSizeState(page);
      await expect(state.field.locator('[data-ecf-size-value-input]').first()).toHaveValue('21');
      await expect(state.field.locator('[data-ecf-format-input]').first()).toHaveValue('px');
      await expect(state.field.locator('.ecf-muted-copy')).toContainText('--ecf-text-m');

      const snapshot = await getFrontendTypographySnapshot(page);
      expect(snapshot.rootBodyTextSize).toContain('21px');
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('legacy settings without enabled layout component still emit boxed layout CSS', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const legacySettings = cloneSettings(originalSettings);
    legacySettings.enabled_components = {
      buttons: '1',
      cards: '1',
    };

    try {
      await updateRestSettings(page, legacySettings);
      await openPluginPage(page);

      await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
      const emittedCss = await page.locator('style#ecf-framework-v010').textContent();
      expect(emittedCss || '').toContain('--ecf-container-boxed:');
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('removing the active local body font falls back to the primary font preset', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const originalSettings = await fetchRestSettings(page);
    const seededSettings = cloneSettings(originalSettings);
    const rows = await getLocalFontRows(page);
    const originalCount = await rows.count();
    const reusableLocalUrl = originalCount > 0
      ? await rows.nth(0).locator('.ecf-font-file-url').inputValue()
      : '';
    test.skip(!reusableLocalUrl, 'No existing local font upload is available on this test site.');

    const familyName = `UITest Fallback ${uniqueSuffix()}`;
    seededSettings.typography = seededSettings.typography || {};
    seededSettings.typography.local_fonts = Array.isArray(seededSettings.typography.local_fonts)
      ? [...seededSettings.typography.local_fonts]
      : [];
    seededSettings.typography.local_fonts.push({
      name: `ui-fallback-${uniqueSuffix()}`,
      family: familyName,
      src: reusableLocalUrl,
      weight: '400',
      style: 'normal',
      display: 'swap',
    });
    seededSettings.base_font_family = `'${familyName}'`;

    try {
      await updateRestSettings(page, seededSettings);
      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(getGeneralField(page, 'base_font_family').locator('[data-ecf-font-current-value]').first()).toContainText(familyName);

      await clickRemoveSelectedLocalFont(page);
      await waitForSuccessNotice(page);

      await page.reload();
      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(getGeneralField(page, 'base_font_family').locator('[data-ecf-base-font-preset]').first()).toHaveValue('var(--ecf-font-primary)');

      const snapshot = await getFrontendTypographySnapshot(page);
      expect(snapshot.rootBodyFontFamily).not.toContain(familyName);
      expect(snapshot.bodyFontFamily).not.toContain(familyName);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('design preset and mode also persist as active admin design attributes after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    const wrap = page.locator('.ecf-wrap').first();
    const presetInput = page.locator('[data-ecf-admin-design-preset]').first();
    const modeInput = page.locator('[data-ecf-admin-design-mode]').first();
    const originalPreset = await presetInput.inputValue();
    const originalMode = await modeInput.inputValue();
    const nextPreset = originalPreset === 'minimal' ? 'hero' : 'minimal';
    const nextMode = originalMode === 'dark' ? 'light' : 'dark';

    await selectDesignPreset(page, nextPreset);
    await waitForSuccessNotice(page);
    await selectDesignMode(page, nextMode);
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');
    await expect(wrap).toHaveAttribute('data-ecf-admin-design', nextPreset);
    await expect(wrap).toHaveAttribute('data-ecf-admin-mode', nextMode);

    await selectDesignPreset(page, originalPreset);
    await waitForSuccessNotice(page);
    await selectDesignMode(page, originalMode);
    await waitForSuccessNotice(page);
  });

  test('added color tokens persist and are emitted as frontend CSS variables', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'tokens');

    const originalCount = await getTokenRowCount(page, 'colors');
    const colorName = `ui-accent-${uniqueSuffix()}`;

    await addTokenRow(page, 'colors');
    const newRow = page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row').nth(originalCount);
    await fillColorRow(newRow, {
      name: colorName,
      value: '#123456',
    });
    await waitForSuccessNotice(page);

    await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
    expect(await getRootCssVariable(page, `--ecf-color-${colorName}`)).toBe('#123456');

    await openPluginPage(page);
    await openPanel(page, 'tokens');
    await removeTokenRow(page, 'colors');
    await waitForSuccessNotice(page);
  });

  test('color generator detail flow exposes copied shade and tint variables', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const seededSettings = cloneSettings(originalSettings);
    seededSettings.interface_language = 'de';
    seededSettings.colors = Array.isArray(seededSettings.colors)
      ? [...seededSettings.colors]
      : [];
    seededSettings.colors[0] = {
      ...(seededSettings.colors[0] || {}),
      name: 'ui-generator',
      value: '#8b6f3a',
      format: 'hex',
      generate_shades: '1',
      shade_count: 4,
      generate_tints: '0',
      tint_count: 4,
    };

    try {
      await updateRestSettings(page, seededSettings);
      await openPluginPage(page);
      await openPanel(page, 'tokens');

      const row = page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row--color').first();
      await expect(row).toBeVisible();

      await row.locator('.ecf-color-detail-toggle').click();
      const detail = row.locator('.ecf-color-detail').first();
      const colorGeneratorLabels = await page.evaluate(() => ({
        shades: window.ecfAdmin.i18n.color_generator_generate_shades,
        tints: window.ecfAdmin.i18n.color_generator_generate_tints,
      }));
      await expect(detail).toBeVisible();
      await expect(detail).toContainText(colorGeneratorLabels.shades);
      await expect(detail).toContainText(colorGeneratorLabels.tints);
      await expect(detail.locator('[data-ecf-color-count="shades"]')).toHaveValue('4');

      await detail.locator('[data-ecf-color-count-plus="shades"]').click();
      await expect(detail.locator('[data-ecf-color-count="shades"]')).toHaveValue('5');
      await expect(detail.locator('.ecf-color-token-copy[data-ecf-copy-text="--ecf-color-ui-generator-shade-5"]')).toBeVisible();

      await detail.locator('[data-ecf-color-generate="tints"]').evaluate((element) => element.click());
      await expect(detail.locator('[data-ecf-color-generate="tints"]')).toBeChecked();
      await expect(detail.locator('.ecf-color-token-copy[data-ecf-copy-text="--ecf-color-ui-generator-tint-1"]')).toBeVisible();

      await mockClipboard(page);
      await detail.locator('.ecf-color-token-copy[data-ecf-copy-text="--ecf-color-ui-generator-shade-5"]').click();
      expect(await getCopiedTexts(page)).toContain('--ecf-color-ui-generator-shade-5');
      await waitForAutosaveIdle(page);

      await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
      expect(await getRootCssVariable(page, '--ecf-color-ui-generator-shade-5')).not.toBe('');
      expect(await getRootCssVariable(page, '--ecf-color-ui-generator-tint-1')).not.toBe('');
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('spacing changes update the preview row and the emitted spacing token', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const seededSettings = cloneSettings(originalSettings);
    seededSettings.root_font_size = '100';

    try {
      await updateRestSettings(page, seededSettings);
      await openPluginPage(page);
      await openPanel(page, 'spacing');

      await setSpacingMaxBase(page, '20');
      await waitForSuccessNotice(page);

      const previewRow = await getSpacingPreviewRow(page, 'm');
      await expect(previewRow).toContainText('20px');

      const emittedCss = await getFrontendStylesheetText(page);
      expect(emittedCss).toMatch(/--ecf-space-m:\s*clamp\([^;]*,\s*1\.25rem\);/i);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('type scale copy interactions match the emitted token output', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const seededSettings = cloneSettings(originalSettings);
    seededSettings.root_font_size = '100';
    seededSettings.typography.scale.base_index = 'm';

    try {
      await updateRestSettings(page, seededSettings);
      await openPluginPage(page);
      await openPanel(page, 'typography');

      await setTypographyScaleMaxBase(page, '22');
      await waitForSuccessNotice(page);
      await mockClipboard(page);

      const previewRow = await getTypographyPreviewRow(page, 'm');
      await expect(previewRow).toContainText('22px');
      await previewRow.locator('.ecf-copy-pill').click();

      await expect(previewRow.locator('.ecf-copy-pill')).toContainText(/Copied|Kopiert/i);
      expect(await getCopiedTexts(page)).toContain('--ecf-text-m');

      const emittedCss = await getFrontendStylesheetText(page);
      expect(emittedCss).toMatch(/--ecf-text-m:\s*clamp\([^;]*,\s*1\.38rem\);/i);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('website favorites appear inside the favorites tab and remain editable there', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const toggle = getGeneralField(page, 'base_font_family').locator('[data-ecf-general-favorite-toggle]').first();
    const originalChecked = await toggle.isChecked();

    if (!originalChecked) {
      await getGeneralField(page, 'base_font_family').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
      await waitForSuccessNotice(page);
    }

    try {
      await openGeneralTab(page, 'favorites');
      const favoriteCard = getFavoriteCard(page, 'base_font_family');
      await expect(favoriteCard).toBeVisible();
      await expect(favoriteCard).toContainText(/Base Font Family|Basis-Schriftfamilie/i);

      await favoriteCard.locator('[data-ecf-favorite-remove]').click();
      await waitForSuccessNotice(page);
      await expect(favoriteCard).toBeHidden();
    } finally {
      if (originalChecked) {
        await openPluginPage(page);
        await openGeneralTab(page, 'website');
        const currentToggle = getGeneralField(page, 'base_font_family').locator('[data-ecf-general-favorite-toggle]').first();
        if (!(await currentToggle.isChecked())) {
          await getGeneralField(page, 'base_font_family').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
          await waitForSuccessNotice(page);
        }
      }
    }
  });

  test('help card layout order persists after reload in a second draggable card group', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'help');

    const originalOrder = await getLayoutOrder(page, 'help-main');
    test.skip(originalOrder.length < 2, 'Not enough help cards available for drag-and-drop verification.');

    const first = originalOrder[0];
    const second = originalOrder[1];
    const reordered = [second, first, ...originalOrder.slice(2)];

    await reorderLayoutGroup(page, 'help-main', second, first);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'help-main')).toEqual(reordered);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'help');
    await expect.poll(async () => getLayoutOrder(page, 'help-main')).toEqual(reordered);

    await reorderLayoutGroup(page, 'help-main', first, second);
    await waitForSuccessNotice(page);
    await expect.poll(async () => getLayoutOrder(page, 'help-main')).toEqual(originalOrder);
  });

  test('german interface language renders tooltips, help copy and changelog modal text in german', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    const originalSettings = await fetchRestSettings(page);
    const originalLanguage = originalSettings.interface_language || 'de';

    try {
      if (originalLanguage !== 'de') {
        const germanSettings = cloneSettings(originalSettings);
        germanSettings.interface_language = 'de';
        await updateRestSettings(page, germanSettings);
      }

      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(page.locator('[data-ecf-general-tab="website"]')).toContainText('Webseite');
      expect(await getFieldTooltipText(page, 'base_font_family')).toContain('Fließtext');
      expect(await getFavoriteToggleTip(page, 'base_font_family')).toContain('Favoriten');

      await openGeneralTab(page, 'interface');
      const interfaceSection = page.locator('[data-ecf-general-section="interface"]:visible').first();
      await expect(interfaceSection.locator('[data-ecf-general-field="interface_language"]').first()).toContainText('Plugin-Sprache');
      expect(await getFavoriteToggleTip(page, 'interface_language')).toContain('Favoriten');
      await expect(interfaceSection).toContainText('Design');

      await openPanel(page, 'help');
      await expect(page.locator('.ecf-card h2').filter({ hasText: 'Schnellhilfe' }).first()).toBeVisible();
      await expect(page.locator('.ecf-system-help-card__item strong').filter({ hasText: 'Was sind Variablen?' }).first()).toBeVisible();
      await expect(page.locator('.ecf-system-help-card__item strong').filter({ hasText: 'Was sind Klassen?' }).first()).toBeVisible();
      await expect(page.locator('.ecf-card h2').filter({ hasText: 'Erste Schritte' }).first()).toBeVisible();
      await expect(page.locator('.ecf-card h2').filter({ hasText: 'Diagnose' }).first()).toBeVisible();
      await expect(page.locator('.ecf-muted-copy').filter({ hasText: 'Technischer Status' }).first()).toBeVisible();
      await expect(page.locator('.ecf-changelog-header [data-ecf-open-changelog-modal]').first()).toContainText('Changelog öffnen');
      await openChangelogModal(page);
      await expect(page.locator('#ecf-changelog-modal-title')).toContainText('Versions-Changelog');
      await closeChangelogModal(page);
    } finally {
      if (originalLanguage !== 'de') {
        await updateRestSettings(page, originalSettings);
      }
    }
  });

  test('imported settings update multiple UI areas after submit', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const importedSettings = cloneSettings(originalSettings);
    importedSettings.root_font_size = String(originalSettings.root_font_size || '') === '62.5' ? '100' : '62.5';
    importedSettings.interface_language = originalSettings.interface_language === 'de' ? 'en' : 'de';
    importedSettings.admin_design_preset = originalSettings.admin_design_preset === 'hero' ? 'next' : 'hero';
    importedSettings.admin_design_mode = originalSettings.admin_design_mode === 'dark' ? 'light' : 'dark';
    importedSettings.base_body_text_size = '21px';

    const payload = {
      meta: {
        plugin: 'Layrix',
        plugin_version: '0.3.3',
        schema_version: 1,
        exported_at: '2026-04-08T12:00:00Z',
      },
      settings: importedSettings,
    };

    try {
      await openPanel(page, 'sync');
      await setImportFile(page, payload, 'ecf-ui-complete-import.json');
      await submitImport(page);

      await openPluginPage(page);
      await openGeneralTab(page, 'website');
      await expect(getGeneralField(page, 'root_font_size').locator('select').first()).toHaveValue(importedSettings.root_font_size);
      await expect(getGeneralField(page, 'base_body_text_size').locator('[data-ecf-size-value-input]').first()).toHaveValue('21');
      await expect(getGeneralField(page, 'base_body_text_size').locator('[data-ecf-format-input]').first()).toHaveValue('px');

      await openGeneralTab(page, 'ui');
      await expect(getGeneralField(page, 'interface_language').locator('select').first()).toHaveValue(importedSettings.interface_language);
      await expect(page.locator('[data-ecf-admin-design-preset]').first()).toHaveValue(importedSettings.admin_design_preset);
      await expect(page.locator('[data-ecf-admin-design-mode]').first()).toHaveValue(importedSettings.admin_design_mode);
    } finally {
      await openPluginPage(page);
      await updateRestSettings(page, originalSettings);
    }
  });

  test('invalid body size blocks autosave while language changes still persist', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalLanguage = await fetchRestSettings(page).then((settings) => settings.interface_language || 'de');
    const targetLanguage = originalLanguage === 'de' ? 'en' : 'de';

    const bodyField = getGeneralField(page, 'base_body_text_size');
    await bodyField.locator('[data-ecf-size-value-input]').first().fill('');
    await bodyField.locator('[data-ecf-size-value-input]').first().blur();
    await waitForErrorNotice(page);
    await expect(bodyField.locator('[data-ecf-body-size-warning]')).toBeVisible();

    try {
      await openGeneralTab(page, 'ui');
      await switchInterfaceLanguage(page, targetLanguage);

      await openGeneralTab(page, 'ui');
      await expect(getGeneralField(page, 'interface_language').locator('select').first()).toHaveValue(targetLanguage);
    } finally {
      await openGeneralTab(page, 'ui');
      if ((await getGeneralField(page, 'interface_language').locator('select').first().inputValue()) !== originalLanguage) {
        await switchInterfaceLanguage(page, originalLanguage);
      }
    }
  });

  test('a local body font does not replace the separate heading font family', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const originalFamilies = await getLocalFontFamilies(page);
    const targetFamily = ['Manrope', 'Poppins', 'Nunito', 'Merriweather'].find((family) => !originalFamilies.includes(family));
    test.skip(!targetFamily, 'No unused library font is available on this test site.');

    await openGeneralTab(page, 'website');
    const originalBaseFont = await getBaseFontFamilyState(page);
    await importLibraryFontForField(page, 'base_font_family', targetFamily);
    await waitForSuccessNotice(page);

    const snapshot = await getFrontendTypographySnapshot(page);
    expect(snapshot.rootBodyFontFamily).toContain(targetFamily);
    expect(snapshot.bodyFontFamily).toContain(targetFamily);
    expect(snapshot.headingFontFamily).not.toContain(targetFamily);
    expect(snapshot.rootPrimaryFontFamily.length).toBeGreaterThan(0);

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await clickRemoveSelectedLocalFont(page);
    await waitForSuccessNotice(page);
    const restoredBaseFont = await getBaseFontFamilyState(page);
    if (restoredBaseFont.preset !== originalBaseFont.preset || (originalBaseFont.preset === '__custom__' && restoredBaseFont.custom !== originalBaseFont.custom)) {
      await selectBaseFontFamilyPreset(page, originalBaseFont.preset);
      if (originalBaseFont.preset === '__custom__') {
        const customField = getGeneralField(page, 'base_font_family').locator('[data-ecf-base-font-custom]').first();
        await customField.fill(originalBaseFont.custom);
        await customField.blur();
      }
      await waitForSuccessNotice(page);
    }
  });
});
