const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  openWebsiteTab,
  chooseFormat,
  getGeneralField,
  toggleGeneralFavorite,
  getFavoriteCard,
  getRootCssVariable,
  getFrontendColorSnapshot,
  setGeneralCheckbox,
  setGeneralColorValue,
  getRootFontImpactSnapshot,
  getVariablesStatusCard,
  getSyncStatusCard,
  getGithubStatus,
  waitForSuccessNotice,
  saveSettingsManually,
  waitForRestSetting,
  fetchRestSettings,
  ensureUiFlowDefaults,
  getSiteOrigin,
} = require('./helpers/ecf-admin');

test.describe('ECF additional linked UI flows', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await ensureUiFlowDefaults(page);
  });

  test('root font size stays in sync across Website, Typography, Spacing and the frontend root size', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'type');

    const websiteSelect = getGeneralField(page, 'root_font_size').locator('select').first();
    const originalValue = await websiteSelect.inputValue();
    const targetValue = originalValue === '62.5' ? '100' : '62.5';
    const expectedInline = targetValue === '62.5' ? '10px = 1rem' : '16px = 1rem';
    const expectedRootSize = targetValue === '62.5' ? '62.5%' : '100%';

    await websiteSelect.selectOption(targetValue);
    await saveSettingsManually(page);
    await waitForRestSetting(page, 'root_font_size', targetValue);
    await expect(getGeneralField(page, 'root_font_size').locator('[data-ecf-root-font-inline]').first()).toContainText(expectedInline);

    await openPanel(page, 'typography');
    await expect(page.locator('[data-ecf-general-field="root_font_size"] [data-ecf-root-font-inline]').first()).toContainText(expectedInline);

    await openPanel(page, 'spacing');
    await expect(page.locator('[data-ecf-general-field="root_font_size"] [data-ecf-root-font-inline]').first()).toContainText(expectedInline);

    await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
    await expect
      .poll(() => page.evaluate(() => getComputedStyle(document.documentElement).fontSize))
      .toBe(targetValue === '62.5' ? '10px' : '16px');
    expect(await getRootCssVariable(page, '--ecf-base-body-text-size')).not.toBe('');
    await expect
      .poll(() => page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('font-size') || ''))
      .not.toBe(expectedRootSize);

    await openPluginPage(page);
    await openWebsiteTab(page, 'type');
    await getGeneralField(page, 'root_font_size').locator('select').first().selectOption(originalValue);
    await saveSettingsManually(page);
    await waitForRestSetting(page, 'root_font_size', originalValue);
  });

  test('removing a favorite inside Favorites updates the source toggle in Website', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const { originalChecked } = await toggleGeneralFavorite(page, 'content_max_width');
    await waitForSuccessNotice(page);

    await openGeneralTab(page, 'favorites');
    const favoriteCard = getFavoriteCard(page, 'content_max_width');
    await expect(favoriteCard).toBeVisible();

    await favoriteCard.locator('[data-ecf-favorite-remove]').click();
    await waitForSuccessNotice(page);

    await openWebsiteTab(page, 'layout');
    await expect(getGeneralField(page, 'content_max_width').locator('[data-ecf-general-favorite-toggle]').first()).not.toBeChecked();

    if (originalChecked) {
      await getGeneralField(page, 'content_max_width').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
      await waitForSuccessNotice(page);
    }
  });

  test('content max width updates the frontend token after changing value and format', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'layout');

    const field = getGeneralField(page, 'content_max_width');
    const valueInput = field.locator('input[name="ecf_framework_v50[content_max_width_value]"]').first();
    const formatInput = field.locator('[data-ecf-format-input]').first();
    const originalValue = await valueInput.inputValue();
    const originalFormat = await formatInput.inputValue();

    await chooseFormat(field, 'ch');
    await valueInput.fill('66');
    await valueInput.blur();
    await waitForSuccessNotice(page);

    await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
    expect(await getRootCssVariable(page, '--ecf-content-max-width')).toBe('66ch');

    await openPluginPage(page);
    await openWebsiteTab(page, 'layout');
    await chooseFormat(field, originalFormat);
    await valueInput.fill(originalValue);
    await valueInput.blur();
    await waitForSuccessNotice(page);
  });

  test('elementor boxed width updates its frontend container token after changing value and format', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'layout');

    const field = getGeneralField(page, 'elementor_boxed_width');
    const valueInput = field.locator('input[name="ecf_framework_v50[elementor_boxed_width_value]"]').first();
    const formatInput = field.locator('[data-ecf-format-input]').first();
    const originalValue = await valueInput.inputValue();
    const originalFormat = await formatInput.inputValue();

    await chooseFormat(field, 'px');
    await valueInput.fill('1180');
    await valueInput.blur();
    await waitForSuccessNotice(page);

    await page.goto(`${await getSiteOrigin(page)}/`, { waitUntil: 'domcontentloaded' });
    expect(await getRootCssVariable(page, '--ecf-container-boxed')).toBe('1180px');

    await openPluginPage(page);
    await openWebsiteTab(page, 'layout');
    await chooseFormat(field, originalFormat);
    await valueInput.fill(originalValue);
    await valueInput.blur();
    await waitForSuccessNotice(page);
  });

  test('ecf-container-boxed centers itself on the frontend with auto margins', async ({ page }) => {
    await loginToWordPress(page);
    const siteOrigin = new URL(page.url()).origin;
    await page.goto(`${siteOrigin}/`, { waitUntil: 'domcontentloaded' });

    const boxedCss = await page.evaluate(() => {
      const styleTag = document.querySelector('#ecf-framework-v010');
      return styleTag ? styleTag.textContent || '' : '';
    });

    expect(boxedCss).toContain('.ecf-container-boxed');
    expect(boxedCss).toMatch(/\.ecf-container-boxed[^}]*margin-left:auto!important/i);
    expect(boxedCss).toMatch(/\.ecf-container-boxed[^}]*margin-right:auto!important/i);
    expect(boxedCss).toMatch(/\.ecf-container-boxed[^}]*max-width:min\(calc\(100% - 2rem\), var\(--ecf-container-boxed\)\)!important/i);
  });

  test('show status cards toggle updates the Variables and Sync status cards after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'editor');

    const checkbox = getGeneralField(page, 'show_elementor_status_cards').locator('input[type="checkbox"]').first();
    const originalChecked = await checkbox.isChecked();
    const firstTarget = !originalChecked;

    const field = await setGeneralCheckbox(page, 'show_elementor_status_cards', firstTarget);
    await waitForSuccessNotice(page);
    await expect(field.locator('input[type="checkbox"]').first()).toHaveJSProperty('checked', firstTarget);
    await expect.poll(async () => (await fetchRestSettings(page)).show_elementor_status_cards).toBe(firstTarget ? '1' : '0');

    await page.reload();
    await openPluginPage(page);
    if (firstTarget) {
      await openPanel(page, 'variables');
      await expect(getVariablesStatusCard(page)).toBeVisible();

      await openPanel(page, 'sync');
      await expect(getSyncStatusCard(page)).toBeVisible();
    } else {
      await openPanel(page, 'variables');
      await expect(getVariablesStatusCard(page)).toHaveCount(0);

      await openPanel(page, 'sync');
      await expect(getSyncStatusCard(page)).toHaveCount(0);
    }

    await openPluginPage(page);
    await openGeneralTab(page, 'editor');
    await setGeneralCheckbox(page, 'show_elementor_status_cards', originalChecked);
    await waitForSuccessNotice(page);
    await expect.poll(async () => (await fetchRestSettings(page)).show_elementor_status_cards).toBe(originalChecked ? '1' : '0');

    await page.reload();
    await openPluginPage(page);
    if (originalChecked) {
      await openPanel(page, 'variables');
      await expect(getVariablesStatusCard(page)).toBeVisible();

      await openPanel(page, 'sync');
      await expect(getSyncStatusCard(page)).toBeVisible();
    } else {
      await openPanel(page, 'variables');
      await expect(getVariablesStatusCard(page)).toHaveCount(0);

      await openPanel(page, 'sync');
      await expect(getSyncStatusCard(page)).toHaveCount(0);
    }
  });

  test('github update checks toggle updates the visible system status and persists', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'system');

    const originalChecked = await getGeneralField(page, 'github_update_checks_enabled').locator('input[type="checkbox"]').first().isChecked();
    const targetChecked = !originalChecked;
    const expectedStatus = targetChecked ? /Enabled|Aktiviert|Aktiv/i : /Disabled|Deaktiviert|Inaktiv/i;
    const originalStatus = originalChecked ? /Enabled|Aktiviert|Aktiv/i : /Disabled|Deaktiviert|Inaktiv/i;

    await setGeneralCheckbox(page, 'github_update_checks_enabled', targetChecked);
    await waitForSuccessNotice(page);
    await expect.poll(async () => (await fetchRestSettings(page)).github_update_checks_enabled).toBe(targetChecked ? '1' : '0');
    await expect(getGithubStatus(page)).toContainText(expectedStatus);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'system');
    await expect(getGeneralField(page, 'github_update_checks_enabled').locator('input[type="checkbox"]').first()).toHaveJSProperty('checked', targetChecked);
    await expect(getGithubStatus(page)).toContainText(expectedStatus);

    await setGeneralCheckbox(page, 'github_update_checks_enabled', originalChecked);
    await waitForSuccessNotice(page);
    await expect.poll(async () => (await fetchRestSettings(page)).github_update_checks_enabled).toBe(originalChecked ? '1' : '0');
    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'system');
    await expect(getGithubStatus(page)).toContainText(originalStatus);
  });

  test('base colors update the emitted frontend CSS variables after autosave', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const originalValues = {
      base_text_color: await getGeneralField(page, 'base_text_color').locator('input.ecf-color-input').first().inputValue(),
      base_background_color: await getGeneralField(page, 'base_background_color').locator('input.ecf-color-input').first().inputValue(),
      link_color: await getGeneralField(page, 'link_color').locator('input.ecf-color-input').first().inputValue(),
      focus_color: await getGeneralField(page, 'focus_color').locator('input.ecf-color-input').first().inputValue(),
    };

    const targetValues = {
      base_text_color: '#203040',
      base_background_color: '#f7f3ee',
      link_color: '#0f62fe',
      focus_color: '#ff6a3d',
    };

    await setGeneralColorValue(page, 'base_text_color', targetValues.base_text_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'base_background_color', targetValues.base_background_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'link_color', targetValues.link_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'focus_color', targetValues.focus_color);
    await waitForSuccessNotice(page);
    await expect.poll(async () => {
      const settings = await fetchRestSettings(page);
      return JSON.stringify({
        baseText: (settings.base_text_color || '').toLowerCase(),
        baseBackground: (settings.base_background_color || '').toLowerCase(),
        link: (settings.link_color || '').toLowerCase(),
        focus: (settings.focus_color || '').toLowerCase(),
      });
    }).toBe(JSON.stringify({
      baseText: targetValues.base_text_color,
      baseBackground: targetValues.base_background_color,
      link: targetValues.link_color,
      focus: targetValues.focus_color,
    }));

    const snapshot = await getFrontendColorSnapshot(page);
    expect(snapshot.baseText.toLowerCase()).toBe(targetValues.base_text_color);
    expect(snapshot.baseBackground.toLowerCase()).toBe(targetValues.base_background_color);
    expect(snapshot.link.toLowerCase()).toBe(targetValues.link_color);
    expect(snapshot.focus.toLowerCase()).toBe(targetValues.focus_color);

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    await setGeneralColorValue(page, 'base_text_color', originalValues.base_text_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'base_background_color', originalValues.base_background_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'link_color', originalValues.link_color);
    await waitForSuccessNotice(page);
    await setGeneralColorValue(page, 'focus_color', originalValues.focus_color);
    await waitForSuccessNotice(page);
  });

  test('scale impact reacts when the root font size changes', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'advanced');

    const details = page.locator('.ecf-panel[data-panel="components"] [data-ecf-layout-item="website-scale-impact"]').first();
    await details.evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });

    await openWebsiteTab(page, 'type');
    const select = getGeneralField(page, 'root_font_size').locator('select').first();
    const originalValue = await select.inputValue();
    const targetValue = originalValue === '62.5' ? '100' : '62.5';

    await openWebsiteTab(page, 'advanced');
    await details.evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });
    const before = await getRootFontImpactSnapshot(page);

    await openWebsiteTab(page, 'type');
    await select.selectOption(targetValue);
    await saveSettingsManually(page);
    await waitForRestSetting(page, 'root_font_size', targetValue);

    await openPluginPage(page);
    await openWebsiteTab(page, 'advanced');
    await details.evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });
    const after = await getRootFontImpactSnapshot(page);

    expect(after.currentBase).not.toBe(before.currentBase);
    expect(after.typeCopy).not.toBe(before.typeCopy);
    expect(after.spacingCopy).not.toBe(before.spacingCopy);
    expect(after.radiusCopy).not.toBe(before.radiusCopy);

    await openPluginPage(page);
    await openWebsiteTab(page, 'type');
    await getGeneralField(page, 'root_font_size').locator('select').first().selectOption(originalValue);
    await saveSettingsManually(page);
    await waitForRestSetting(page, 'root_font_size', originalValue);
  });

  test('variable type filter scopes persist after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'editor');

    const filterField = getGeneralField(page, 'elementor_variable_type_filter');
    const filterCheckbox = filterField.locator('input[type="checkbox"]').first();
    const originalEnabled = await filterCheckbox.isChecked();
    const scopeSelectors = {
      color: 'input[name="ecf_framework_v50[elementor_variable_type_filter_scopes][color]"]',
      text: 'input[name="ecf_framework_v50[elementor_variable_type_filter_scopes][text]"]',
    };
    const originalScopes = {
      color: await page.locator(scopeSelectors.color).first().isChecked(),
      text: await page.locator(scopeSelectors.text).first().isChecked(),
    };

    const targetEnabled = !originalEnabled;
    await setGeneralCheckbox(page, 'elementor_variable_type_filter', targetEnabled);
    await waitForSuccessNotice(page);
    await expect.poll(async () => (await fetchRestSettings(page)).elementor_variable_type_filter).toBe(targetEnabled ? '1' : '0');

    const details = page.locator('.ecf-filter-scope-box').first();
    await details.evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });

    if (originalScopes.color === originalScopes.text) {
      await page.locator(scopeSelectors.color).first().check({ force: true });
      await waitForSuccessNotice(page);
      await page.locator(scopeSelectors.text).first().uncheck({ force: true });
      await waitForSuccessNotice(page);
    } else {
      if (originalScopes.color) {
        await page.locator(scopeSelectors.color).first().uncheck({ force: true });
      } else {
        await page.locator(scopeSelectors.color).first().check({ force: true });
      }
      await waitForSuccessNotice(page);
      if (originalScopes.text) {
        await page.locator(scopeSelectors.text).first().uncheck({ force: true });
      } else {
        await page.locator(scopeSelectors.text).first().check({ force: true });
      }
      await waitForSuccessNotice(page);
    }

    const changedScopes = {
      color: await page.locator(scopeSelectors.color).first().isChecked(),
      text: await page.locator(scopeSelectors.text).first().isChecked(),
    };
    await expect.poll(async () => {
      const settings = await fetchRestSettings(page);
      return JSON.stringify({
        color: Boolean(settings.elementor_variable_type_filter_scopes?.color && settings.elementor_variable_type_filter_scopes.color !== '0'),
        text: Boolean(settings.elementor_variable_type_filter_scopes?.text && settings.elementor_variable_type_filter_scopes.text !== '0'),
      });
    }).toBe(JSON.stringify(changedScopes));

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'editor');
    await expect(filterCheckbox).toHaveJSProperty('checked', targetEnabled);
    await page.locator('.ecf-filter-scope-box').first().evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });
    await expect(page.locator(scopeSelectors.color).first()).toHaveJSProperty('checked', changedScopes.color);
    await expect(page.locator(scopeSelectors.text).first()).toHaveJSProperty('checked', changedScopes.text);

    await setGeneralCheckbox(page, 'elementor_variable_type_filter', originalEnabled);
    await waitForSuccessNotice(page);
    if (originalScopes.color) {
      await page.locator(scopeSelectors.color).first().check({ force: true });
    } else {
      await page.locator(scopeSelectors.color).first().uncheck({ force: true });
    }
    await waitForSuccessNotice(page);
    if (originalScopes.text) {
      await page.locator(scopeSelectors.text).first().check({ force: true });
    } else {
      await page.locator(scopeSelectors.text).first().uncheck({ force: true });
    }
    await waitForSuccessNotice(page);
  });
});
