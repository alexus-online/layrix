const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  getGeneralField,
  setBodyTextSize,
  getBodyTextSizeState,
  selectBaseFontFamilyPreset,
  getRootCssVariable,
  getBodyComputedFontFamily,
  getFrontendStyles,
  toggleGeneralFavorite,
  selectDesignPreset,
  selectDesignMode,
  refreshSystemInfo,
  addTokenRow,
  removeTokenRow,
  getTokenRowCount,
  fillColorRow,
  addTypographyStep,
  removeTypographyStep,
  getTypographyStepCount,
  addSpacingStep,
  removeSpacingStep,
  getSpacingStepCount,
  waitForSuccessNotice,
  waitForErrorNotice,
} = require('./helpers/ecf-admin');

test.describe('ECF admin UI', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test('loads the plugin shell, switches panels and opens the changelog modal', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    await openPanel(page, 'tokens');
    await openPanel(page, 'typography');
    await openPanel(page, 'spacing');
    await openPanel(page, 'sync');
    await openPanel(page, 'help');

    await page.locator('[data-ecf-open-changelog-modal]').first().click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeVisible();
    await page.locator('button[data-ecf-close-changelog-modal]').first().click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeHidden();
  });

  test('help panel keeps changelog access without duplicating visible changelog entries', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'help');

    const helpPanel = page.locator('.ecf-panel[data-panel="help"]');
    const changelogCard = helpPanel.locator('[data-ecf-layout-item="help-changelog-link"]').first();

    await expect(changelogCard).toBeVisible();
    await expect(helpPanel.locator('.ecf-changelog-entry:visible')).toHaveCount(0);

    await changelogCard.locator('[data-ecf-open-changelog-modal]').click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeVisible();
    await expect(page.locator('[data-ecf-changelog-modal] .ecf-changelog-entry')).not.toHaveCount(0);

    await page.locator('button[data-ecf-close-changelog-modal]').first().click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeHidden();
  });

  test('persists an autosaved body text size change after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const { value: originalValue, format: originalFormat } = await getBodyTextSizeState(page);
    const targetValue = originalValue === '19' ? '20' : '19';

    await setBodyTextSize(page, targetValue, 'px');
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const reloadedState = await getBodyTextSizeState(page);
    await expect(reloadedState.field.locator('[data-ecf-size-value-input]').first()).toHaveValue(targetValue);
    await expect(reloadedState.field.locator('[data-ecf-format-input]').first()).toHaveValue('px');

    await setBodyTextSize(page, originalValue, originalFormat);
    await waitForSuccessNotice(page);
  });

  test('favorite toggle persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const { originalChecked } = await toggleGeneralFavorite(page, 'root_font_size');
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const reloadedToggle = getGeneralField(page, 'root_font_size').locator('[data-ecf-general-favorite-toggle]').first();
    if (originalChecked) {
      await expect(reloadedToggle).not.toBeChecked();
    } else {
      await expect(reloadedToggle).toBeChecked();
    }

    await getGeneralField(page, 'root_font_size').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
    await waitForSuccessNotice(page);
  });

  test('base font family resolves to a real body font stack', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const field = getGeneralField(page, 'base_font_family');
    const select = field.locator('[data-ecf-base-font-preset]').first();
    const originalPreset = await select.inputValue();

    await selectBaseFontFamilyPreset(page, 'var(--ecf-font-secondary)');
    await waitForSuccessNotice(page);

    const frontendStyles = await getFrontendStyles(page);
    expect(frontendStyles.rootFontFamily).toContain('Georgia');
    expect(frontendStyles.bodyFontFamily).toContain('Georgia');

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await selectBaseFontFamilyPreset(page, originalPreset);
    await waitForSuccessNotice(page);
  });

  test('design preset and mode persist after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    const presetInput = page.locator('[data-ecf-admin-design-preset]').first();
    const modeInput = page.locator('[data-ecf-admin-design-mode]').first();
    const originalPreset = await presetInput.inputValue();
    const originalMode = await modeInput.inputValue();
    const nextPreset = originalPreset === 'next' ? 'hero' : 'next';
    const nextMode = originalMode === 'dark' ? 'light' : 'dark';

    await selectDesignPreset(page, nextPreset);
    await waitForSuccessNotice(page);
    await selectDesignMode(page, nextMode);
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    await expect(page.locator('[data-ecf-admin-design-preset]').first()).toHaveValue(nextPreset);
    await expect(page.locator('[data-ecf-admin-design-mode]').first()).toHaveValue(nextMode);

    await selectDesignPreset(page, originalPreset);
    await waitForSuccessNotice(page);
    await selectDesignMode(page, originalMode);
    await waitForSuccessNotice(page);
  });

  test('system refresh works without reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'system');

    await refreshSystemInfo(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('[data-ecf-classes-limit]').first()).not.toBeEmpty();
    await expect(page.locator('[data-ecf-variables-limit]').first()).not.toBeEmpty();
  });

  test('color rows can be added and removed with persistence after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'tokens');

    const originalCount = await getTokenRowCount(page, 'colors');

    await addTokenRow(page, 'colors');
    const newRow = page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row').nth(originalCount);
    await fillColorRow(newRow, {
      name: `ui-color-${Date.now().toString().slice(-6)}`,
      value: '#123456',
    });
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row')).toHaveCount(originalCount + 1);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'tokens');
    await expect(page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row')).toHaveCount(originalCount + 1);

    await removeTokenRow(page, 'colors');
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row')).toHaveCount(originalCount);
  });

  test('new token names are normalized without spaces and persist after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'tokens');

    const originalCount = await getTokenRowCount(page, 'colors');
    await addTokenRow(page, 'colors');

    const newRow = page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row').nth(originalCount);
    await fillColorRow(newRow, {
      name: 'Primary Brand',
      value: '#654321',
    });
    await expect(newRow.locator('input[name$="[name]"]').first()).toHaveValue('primary-brand');
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'tokens');

    const reloadedRow = page.locator('.ecf-panel[data-panel="tokens"] .ecf-table[data-group="colors"] .ecf-row').nth(originalCount);
    await expect(reloadedRow.locator('input[name$="[name]"]').first()).toHaveValue('primary-brand');

    await removeTokenRow(page, 'colors');
    await waitForSuccessNotice(page);
  });

  test('typography scale steps can be added and removed with persistence after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const originalCount = await getTypographyStepCount(page);

    await addTypographyStep(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="typography"] .ecf-scale-step-input')).toHaveCount(originalCount + 1);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'typography');
    await expect(page.locator('.ecf-panel[data-panel="typography"] .ecf-scale-step-input')).toHaveCount(originalCount + 1);

    await removeTypographyStep(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="typography"] .ecf-scale-step-input')).toHaveCount(originalCount);
  });

  test('spacing steps can be added and removed with persistence after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'spacing');

    const originalCount = await getSpacingStepCount(page);

    await addSpacingStep(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="spacing"] .ecf-spacing-step-input')).toHaveCount(originalCount + 1);

    await page.reload();
    await openPluginPage(page);
    await openPanel(page, 'spacing');
    await expect(page.locator('.ecf-panel[data-panel="spacing"] .ecf-spacing-step-input')).toHaveCount(originalCount + 1);

    await removeSpacingStep(page);
    await waitForSuccessNotice(page);
    await expect(page.locator('.ecf-panel[data-panel="spacing"] .ecf-spacing-step-input')).toHaveCount(originalCount);
  });

  test('invalid empty body text size is blocked in the UI', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const { field: bodyField, value: originalValue } = await getBodyTextSizeState(page);
    const input = bodyField.locator('[data-ecf-size-value-input]').first();

    await input.fill('');
    await input.blur();
    await waitForErrorNotice(page);
    await expect(bodyField.locator('.ecf-inline-size-input').first()).toHaveClass(/is-invalid/);

    await input.fill(originalValue);
    await input.blur();
    await waitForSuccessNotice(page);
  });
});
