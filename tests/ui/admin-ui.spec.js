const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  openWebsiteTab,
  getGeneralField,
  setGeneralCheckbox,
  setBodyTextSize,
  getBodyTextSizeState,
  selectBaseFontFamilyPreset,
  selectHeadingFontFamilyPreset,
  getFontFamilySavedState,
  restoreFontFamilySavedState,
  getRootCssVariable,
  getBodyComputedFontFamily,
  getFrontendStyles,
  getFrontendTypographySnapshot,
  getLocalFontRows,
  getLocalFontFamilies,
  fillLocalFontRow,
  removeLocalFontRow,
  importLibraryFontForField,
  waitForRestSetting,
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
  ensureUiFlowDefaults,
  fetchRestSettings,
  saveSettingsManually,
  setTopbarAutosaveEnabled,
  setTopbarAutosaveSetting,
  openTopbarAutosaveMenu,
  closeTopbarAutosaveMenu,
} = require('./helpers/ecf-admin');

test.describe('ECF admin UI', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await ensureUiFlowDefaults(page);
  });

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

  test('sidebar uses compact footer help links and the sticky topbar mirrors the active panel', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    await expect(page.locator('.ecf-logo h1')).toHaveText(/Layrix/i);
    await expect(page.locator('.ecf-logo__byline')).toContainText(/Alexander Kaiser/i);
    await expect(page.locator('.ecf-nav-item[data-panel="help"]')).toHaveCount(0);
    await expect(page.locator('.ecf-sidebar-link[data-panel="help"]')).toBeVisible();
    await expect(page.locator('.ecf-sidebar-link[data-ecf-open-changelog-modal]')).toBeVisible();
    await expect(page.locator('.ecf-nav-item.is-active').first()).toHaveCSS('border-left-width', '2px');

    await openPanel(page, 'sync');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Sync & Export/i);
    await openPanel(page, 'help');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Help & Support|Hilfe & Support/i);
    await expect(page.locator('.ecf-sticky-topbar')).toBeVisible();
    await expect(page.locator('.ecf-autosave-pill')).toContainText(/Autosave/i);
  });

  test('general color fields render clickable swatches next to each field', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'colors');

    for (const fieldName of ['base_text_color', 'base_background_color', 'link_color', 'focus_color']) {
      const field = getGeneralField(page, fieldName);
      const swatch = field.locator('.wp-color-result').first();

      await expect(field).toBeVisible();
      await expect(swatch).toBeVisible();
      await expect(swatch).toBeEnabled();
    }

    const focusField = getGeneralField(page, 'focus_color');
    const focusSwatch = focusField.locator('.wp-color-result').first();
    await focusSwatch.click();
    await expect(focusField.locator('.wp-picker-container').first()).toHaveClass(/wp-picker-active/);
  });

  test('wp admin menu uses the bundled layrix svg icon', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const menuItem = page.locator('#adminmenu .toplevel_page_ecf-framework').first();
    await expect(menuItem).toBeVisible();

    const iconState = await menuItem.evaluate((node) => {
      const image = node.querySelector('.wp-menu-image img');
      const imageSrc = image ? image.getAttribute('src') || '' : '';
      const imageStyle = image ? getComputedStyle(image).backgroundImage || '' : '';
      const wrapper = node.querySelector('.wp-menu-image');
      const wrapperStyle = wrapper ? getComputedStyle(wrapper).backgroundImage || '' : '';

      return { imageSrc, imageStyle, wrapperStyle };
    });

    const iconMarkup = [iconState.imageSrc, iconState.imageStyle, iconState.wrapperStyle].join(' ');
    expect(iconMarkup).toMatch(/data:image\/svg\+xml;base64,/i);
    expect(iconMarkup).toContain('PHN2Zy');
  });

  test('switching to the classes panel scrolls the content area back to the top', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const main = page.locator('.ecf-main').first();
    await openPanel(page, 'spacing');
    await main.evaluate((element) => {
      element.scrollTop = 1200;
    });

    await openPanel(page, 'utilities');

    await expect
      .poll(async () => main.evaluate((element) => Math.round(element.scrollTop)))
      .toBeLessThan(10);

    await expect(page.locator('[data-ecf-class-search]').first()).toBeVisible();
  });

  test('sticky topbar sits flush at the top of the content area while scrolling', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const main = page.locator('.ecf-main').first();
    const topbar = page.locator('.ecf-sticky-topbar').first();

    await expect(main).toBeVisible();
    await expect(topbar).toBeVisible();

    await main.evaluate((element) => {
      element.scrollTop = 500;
    });

    const mainBox = await main.boundingBox();
    const topbarBox = await topbar.boundingBox();

    expect(mainBox).not.toBeNull();
    expect(topbarBox).not.toBeNull();
    expect(Math.abs(topbarBox.y - mainBox.y)).toBeLessThan(3);
  });

  test('general settings show an unsaved badge only while changes are pending', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const badge = page.locator('[data-ecf-unsaved-badge]');
    await expect(badge).toBeHidden();

    const state = await getBodyTextSizeState(page);
    const targetValue = state.value === '19' ? '20' : '19';
    await setBodyTextSize(page, targetValue, state.format);
    await expect(badge).toBeVisible();
    await waitForSuccessNotice(page);
    await expect(badge).toBeHidden();

    await setBodyTextSize(page, state.value, state.format);
    await waitForSuccessNotice(page);
  });

  test('general settings tabs use the merged interface tab order', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'components');

    const tabs = page.locator('.ecf-general-tabs [data-ecf-general-tab]');
    await expect(tabs).toHaveCount(4);
    await expect(tabs.nth(0)).toContainText(/Website|Webseite/i);
    await expect(tabs.nth(1)).toContainText(/Interface/i);
    await expect(tabs.nth(2)).toContainText(/System/i);
    await expect(tabs.nth(3)).toContainText(/Favorites|Favoriten/i);
    await expect(tabs.nth(3).locator('.ecf-new-dot')).toHaveCount(0);
  });

  test('website settings are grouped into compact subtabs', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'components');

    const panel = page.locator('.ecf-panel[data-panel="components"]');
    const subtabs = panel.locator('[data-ecf-website-tab]');
    await expect(subtabs).toHaveCount(4);
    await expect(subtabs.nth(0)).toContainText(/Font|Schrift/i);
    await expect(subtabs.nth(1)).toContainText(/Layout/i);
    await expect(subtabs.nth(2)).toContainText(/Colors|Farben/i);
    await expect(subtabs.nth(3)).toContainText(/Advanced|Erweitert/i);
    const typeSection = panel.locator('[data-ecf-website-section="type"]');
    const layoutSection = panel.locator('[data-ecf-website-section="layout"]');
    const colorsSection = panel.locator('[data-ecf-website-section="colors"]');
    await expect(typeSection).toBeVisible();
    await expect(panel.locator('[data-ecf-website-section="layout"]')).toBeHidden();
    await expect(typeSection.locator('[data-ecf-token-copy="--ecf-base-body-text-size"]')).toBeVisible();
    await expect(typeSection.locator('[data-ecf-token-copy="--ecf-base-body-font-weight"]')).toBeVisible();
    await expect(typeSection.locator('[data-ecf-token-copy="--ecf-base-font-family"]')).toBeVisible();
    await expect(typeSection.locator('[data-ecf-token-copy="--ecf-heading-font-family"]')).toBeVisible();

    await subtabs.nth(1).click();
    await expect(layoutSection).toBeVisible();
    await expect(typeSection).toBeHidden();
    await expect(layoutSection.locator('[data-ecf-token-copy="--ecf-content-max-width"]')).toBeVisible();
    await expect(layoutSection.locator('[data-ecf-token-copy="--ecf-container-boxed"]')).toBeVisible();
    await expect(layoutSection.locator('[data-ecf-token-copy=".ecf-container-boxed"]')).toBeVisible();

    await subtabs.nth(2).click();
    await expect(colorsSection).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-base-text-color"]')).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-base-background-color"]')).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-link-color"]')).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-focus-color"]')).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-focus-outline-width"]')).toBeVisible();
    await expect(colorsSection.locator('[data-ecf-token-copy="--ecf-focus-outline-offset"]')).toBeVisible();
  });

  test('help panel keeps changelog access without duplicating visible changelog entries', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'help');

    const helpPanel = page.locator('.ecf-panel[data-panel="help"]');
    const startCard = helpPanel.locator('[data-ecf-layout-item="help-start"]').first();
    const changelogCard = helpPanel.locator('[data-ecf-layout-item="help-changelog-link"]').first();

    await expect(startCard).toBeVisible();
    await expect(startCard).toContainText(/Getting started|Erste Schritte/i);
    await expect(changelogCard).toBeVisible();
    await expect(helpPanel.locator('.ecf-changelog-entry:visible')).toHaveCount(0);

    await changelogCard.locator('[data-ecf-open-changelog-modal]').click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeVisible();
    await expect(page.locator('[data-ecf-changelog-modal] .ecf-changelog-entry')).not.toHaveCount(0);

    await page.locator('button[data-ecf-close-changelog-modal]').first().click();
    await expect(page.locator('[data-ecf-changelog-modal]')).toBeHidden();
  });

  test('website width fields stay side by side in the widths section on desktop', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openWebsiteTab(page, 'layout');

    const contentField = getGeneralField(page, 'content_max_width');
    const boxedField = getGeneralField(page, 'elementor_boxed_width');

    await expect(contentField).toBeVisible();
    await expect(boxedField).toBeVisible();

    const contentBox = await contentField.boundingBox();
    const boxedBox = await boxedField.boundingBox();

    expect(contentBox).not.toBeNull();
    expect(boxedBox).not.toBeNull();
    expect(Math.abs(contentBox.y - boxedBox.y)).toBeLessThan(24);
  });

  test('sync panel shows a warning before mutation actions', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const warning = page.locator('.ecf-panel[data-panel="sync"] .ecf-notice--warning').first();
    await expect(warning).toBeVisible();
    await expect(warning).toContainText(/Backup|Elementor/i);
  });

  test('shadow rows render a visual preview box for each token', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'shadows');

    const header = page.locator('.ecf-panel[data-panel="shadows"] .ecf-table[data-group="shadows"] .ecf-head').first();
    await expect(header).toContainText(/Class Name|Klassenname/i);
    await expect(header).toContainText(/Value|Wert/i);

    const rows = page.locator('.ecf-panel[data-panel="shadows"] .ecf-table[data-group="shadows"] .ecf-row');
    const previews = page.locator('.ecf-panel[data-panel="shadows"] .ecf-table[data-group="shadows"] .ecf-shadow-preview');
    await expect(rows).not.toHaveCount(0);
    await expect(previews).toHaveCount(await rows.count());
  });

  test('sticky topbar uses normalized panel titles', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    await openPanel(page, 'shadows');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Shadows|Schatten/i);

    await openPanel(page, 'variables');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Elementor Variables|Elementor-Variablen/i);

    await openPanel(page, 'utilities');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Elementor Classes|Elementor-Klassen/i);

    await openPanel(page, 'components');
    await expect(page.locator('[data-ecf-active-panel-title]')).toHaveText(/Base Settings|Basiseinstellungen/i);
  });

  test('spacing preview uses the green plugin palette', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'spacing');

    const baseBar = page.locator('.ecf-panel[data-panel="spacing"] .ecf-space-row.is-base .ecf-space-row__bar-fill').first();
    const otherBar = page.locator('.ecf-panel[data-panel="spacing"] .ecf-space-row:not(.is-base) .ecf-space-row__bar-fill').first();

    await expect(baseBar).toBeVisible();
    await expect(otherBar).toBeVisible();

    const baseBg = await baseBar.evaluate((el) => getComputedStyle(el).backgroundColor);
    const otherBg = await otherBar.evaluate((el) => getComputedStyle(el).backgroundColor);

    expect(baseBg).toMatch(/34,\s*197,\s*94/);
    expect(otherBg).toMatch(/34,\s*197,\s*94/);
  });

  test('spacing preview keeps minimum values below or equal to maximum values for small steps', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'spacing');

    const row = page.locator('.ecf-panel[data-panel="spacing"] .ecf-space-row[data-ecf-space-step="3xs"]').first();
    await expect(row).toBeVisible();

    const values = await row.locator('.ecf-space-row__metric strong').allTextContents();
    expect(values).toHaveLength(2);

    const min = parseFloat(String(values[0]).replace(/[^\d.,-]/g, '').replace(',', '.'));
    const max = parseFloat(String(values[1]).replace(/[^\d.,-]/g, '').replace(',', '.'));

    expect(Number.isFinite(min)).toBe(true);
    expect(Number.isFinite(max)).toBe(true);
    expect(min).toBeLessThanOrEqual(max);
  });

  test('spacing preview keeps long token names on a single truncated line', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'spacing');

    const token = page.locator('.ecf-panel[data-panel="spacing"] [data-ecf-space-step="3xs"] .ecf-space-row__token-text').first();
    await expect(token).toBeVisible();

    const styles = await token.evaluate((el) => {
      const computed = getComputedStyle(el);
      return {
        whiteSpace: computed.whiteSpace,
        overflow: computed.overflow,
        textOverflow: computed.textOverflow,
      };
    });

    expect(styles.whiteSpace).toBe('nowrap');
    expect(styles.overflow).toBe('hidden');
    expect(styles.textOverflow).toBe('ellipsis');
  });

  test('typography preview renders sample words instead of repeating minimum and maximum as preview text', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const focusMin = page.locator('.ecf-panel[data-panel="typography"] [data-ecf-focus-min-line]').first();
    const focusMax = page.locator('.ecf-panel[data-panel="typography"] [data-ecf-focus-max-line]').first();
    const sampleMin = page.locator('.ecf-panel[data-panel="typography"] .ecf-type-row__sample-line strong').first();
    const sampleMax = page.locator('.ecf-panel[data-panel="typography"] .ecf-type-row__sample-line--max strong').first();

    await expect(focusMin).toBeVisible();
    await expect(focusMax).toBeVisible();
    await expect(sampleMin).toBeVisible();
    await expect(sampleMax).toBeVisible();

    await expect(focusMin).not.toHaveText(/^(Minimum|Maximum)$/i);
    await expect(focusMax).not.toHaveText(/^(Minimum|Maximum)$/i);
    await expect(sampleMin).not.toHaveText(/^(Minimum|Maximum)$/i);
    await expect(sampleMax).not.toHaveText(/^(Minimum|Maximum)$/i);
    await expect(sampleMin).not.toBeEmpty();
    await expect(sampleMax).not.toBeEmpty();
  });

  test('class sync action is styled as a secondary button instead of a solid primary action', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');

    const button = page.locator('.ecf-panel[data-panel="utilities"] [data-ecf-class-sync-button]').first();
    await expect(button).toBeVisible();

    const styles = await button.evaluate((el) => {
      const computed = getComputedStyle(el);
      return {
        backgroundColor: computed.backgroundColor,
        borderColor: computed.borderColor,
        color: computed.color,
      };
    });

    expect(styles.backgroundColor).toMatch(/34,\s*197,\s*94/);
    expect(styles.borderColor).toMatch(/34,\s*197,\s*94/);
    expect(styles.color).toMatch(/34,\s*197,\s*94/);
  });

  test('type and size fields stack vertically in website settings', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const rootField = getGeneralField(page, 'root_font_size');
    const bodySizeField = getGeneralField(page, 'base_body_text_size');
    const baseFontField = getGeneralField(page, 'base_font_family');

    const rootBox = await rootField.boundingBox();
    const bodyBox = await bodySizeField.boundingBox();
    const baseFontBox = await baseFontField.boundingBox();

    expect(rootBox).not.toBeNull();
    expect(bodyBox).not.toBeNull();
    expect(baseFontBox).not.toBeNull();
    expect(bodyBox.y + (bodyBox.height / 2)).toBeGreaterThan(rootBox.y + (rootBox.height / 2));
    expect(baseFontBox.y + (baseFontBox.height / 2)).toBeGreaterThan(bodyBox.y + (bodyBox.height / 2));
  });

  test('website type and size fields stay constrained instead of spanning the full content width', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const bodyField = getGeneralField(page, 'base_body_text_size');
    const baseFontField = getGeneralField(page, 'base_font_family');

    const bodyBox = await bodyField.boundingBox();
    const baseFontBox = await baseFontField.boundingBox();
    const searchBox = await baseFontField.locator('[data-ecf-font-family-search]').first().boundingBox();

    expect(bodyBox).not.toBeNull();
    expect(baseFontBox).not.toBeNull();
    expect(searchBox).not.toBeNull();

    expect(bodyBox.width).toBeLessThan(620);
    expect(baseFontBox.width).toBeLessThan(540);
    expect(searchBox.width).toBeLessThan(420);
  });

  test('inline size format pickers stay compact next to the value input', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const bodySizeField = getGeneralField(page, 'base_body_text_size');
    await expect(bodySizeField).toBeVisible();

    const valueInput = bodySizeField.locator('[data-ecf-size-value-input]').first();
    const formatTrigger = bodySizeField.locator('[data-ecf-format-trigger]').first();

    const inputBox = await valueInput.boundingBox();
    const triggerBox = await formatTrigger.boundingBox();

    expect(inputBox).not.toBeNull();
    expect(triggerBox).not.toBeNull();
    expect(triggerBox.width).toBeLessThan(84);
    expect(triggerBox.width).toBeLessThan(inputBox.width * 0.65);

    await formatTrigger.click();

    const menuBox = await bodySizeField.locator('[data-ecf-format-menu]').first().boundingBox();
    expect(menuBox).not.toBeNull();
    expect(menuBox.width).toBeLessThan(140);
  });

  test('site font assignment sits above the type preview and starts collapsed', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const panel = page.locator('.ecf-panel[data-panel="typography"]');
    const assignmentCard = panel.locator('.ecf-typography-font-grid').first();
    const previewCard = panel.locator('.ecf-typography-preview-card');
    const accordionItems = assignmentCard.locator('.ecf-typography-font-card');

    await expect(accordionItems).toHaveCount(2);
    await expect(accordionItems.nth(0)).not.toHaveAttribute('open', '');
    await expect(accordionItems.nth(1)).not.toHaveAttribute('open', '');
    await expect(accordionItems.nth(0)).toContainText(/Body Font|Body-Schrift/i);

    const assignmentBox = await assignmentCard.boundingBox();
    const previewBox = await previewCard.boundingBox();

    expect(assignmentBox).not.toBeNull();
    expect(previewBox).not.toBeNull();
    expect(assignmentBox.y).toBeLessThan(previewBox.y);
  });

  test('site font assignment keeps font library actions compact when expanded', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const fontAssignmentCard = page.locator('.ecf-panel[data-panel="typography"] .ecf-typography-font-grid').first();
    await expect(fontAssignmentCard.locator('.ecf-font-family-note')).toHaveCount(0);

    const firstField = fontAssignmentCard.locator('[data-ecf-general-field="base_font_family"]').first();
    await fontAssignmentCard.locator('.ecf-typography-font-card').first().evaluate((element) => {
      if (!element.open) {
        element.open = true;
        element.dispatchEvent(new Event('toggle'));
      }
    });
    const searchBox = await firstField.locator('[data-ecf-font-family-search]').boundingBox();
    await firstField.locator('[data-ecf-font-family-search]').first().click();
    const selectBox = await firstField.locator('[data-ecf-font-picker-panel]').boundingBox();

    expect(searchBox).not.toBeNull();
    expect(selectBox).not.toBeNull();
    expect(searchBox.width).toBeLessThan(420);
    expect(selectBox.width).toBeLessThan(420);
  });

  test('typography secondary management cards use compact accordions with the first one open', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const detailCards = page.locator('[data-ecf-layout-group="typography-secondary"] .ecf-card--details');
    await expect(detailCards).toHaveCount(3);
    await expect(detailCards.nth(0)).toHaveAttribute('open', '');
    await expect(detailCards.nth(1)).not.toHaveAttribute('open', '');
    await expect(detailCards.nth(2)).not.toHaveAttribute('open', '');
  });

  test('typography detail tokens use stacked accordions with only the first section open', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const details = page.locator('.ecf-grid[data-ecf-layout-group="typography-secondary"] .ecf-card--details');
    await expect(details).toHaveCount(3);
    await expect(details.nth(0)).toHaveAttribute('open', '');
    await expect(details.nth(1)).not.toHaveAttribute('open', '');
    await expect(details.nth(2)).not.toHaveAttribute('open', '');
  });

  test('spacing container widths are grouped in an accordion that starts open', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'spacing');

    const containerCard = page.locator('.ecf-panel[data-panel="spacing"] .ecf-card--details').first();
    await expect(containerCard).toHaveAttribute('open', '');
    await expect(containerCard.locator('input[name="ecf_framework_v50[container][sm]"]')).toBeVisible();
  });

  test('persists an autosaved body text size change after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await setTopbarAutosaveEnabled(page, true);
    await waitForRestSetting(page, 'autosave_enabled', '1');
    await openGeneralTab(page, 'website');

    const { value: originalValue, format: originalFormat } = await getBodyTextSizeState(page);
    const targetValue = originalValue === '19' ? '20' : '19';

    await setBodyTextSize(page, targetValue, 'px');
    await waitForRestSetting(page, 'base_body_text_size', `${targetValue}px`);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const reloadedState = await getBodyTextSizeState(page);
    await expect(reloadedState.field.locator('[data-ecf-size-value-input]').first()).toHaveValue(targetValue);
    await expect(reloadedState.field.locator('[data-ecf-format-input]').first()).toHaveValue('px');

    await setBodyTextSize(page, originalValue, originalFormat);
    const restoredValue = originalFormat === 'px' ? `${originalValue}px` : `${originalValue}${originalFormat}`;
    await waitForRestSetting(page, 'base_body_text_size', restoredValue);
  });

  test('persists a body text size change after manual save when autosave is disabled', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await setTopbarAutosaveEnabled(page, false);
    await waitForRestSetting(page, 'autosave_enabled', '0');
    await expect(page.locator('.ecf-autosave-pill')).toContainText(/Autosave off|Autosave aus/i);
    await openGeneralTab(page, 'website');

    const { value: originalValue, format: originalFormat } = await getBodyTextSizeState(page);
    const targetValue = originalValue === '18' ? '17' : '18';

    await setBodyTextSize(page, targetValue, 'px');

    await saveSettingsManually(page);
    await openPluginPage(page);
    await waitForRestSetting(page, 'base_body_text_size', `${targetValue}px`);
    await openGeneralTab(page, 'website');

    const reloadedState = await getBodyTextSizeState(page);
    await expect(reloadedState.field.locator('[data-ecf-size-value-input]').first()).toHaveValue(targetValue);
    await expect(reloadedState.field.locator('[data-ecf-format-input]').first()).toHaveValue('px');

    await setBodyTextSize(page, originalValue, originalFormat);
    await saveSettingsManually(page);
    await openPluginPage(page);
    const restoredValue = originalFormat === 'px' ? `${originalValue}px` : `${originalValue}${originalFormat}`;
    await waitForRestSetting(page, 'base_body_text_size', restoredValue);
    await setTopbarAutosaveEnabled(page, true);
    await waitForRestSetting(page, 'autosave_enabled', '1');
  });

  test('topbar autosave dropdown persists elementor sync flags after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);

    const originalSettings = await fetchRestSettings(page);
    const originalEnabled = String(originalSettings.elementor_auto_sync_enabled || '') === '1';
    const originalVariables = String(originalSettings.elementor_auto_sync_variables || '') === '1';
    const originalClasses = String(originalSettings.elementor_auto_sync_classes || '') === '1';

    await openTopbarAutosaveMenu(page);
    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_enabled', true);
    await waitForRestSetting(page, 'elementor_auto_sync_enabled', '1');
    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_variables', false);
    await waitForRestSetting(page, 'elementor_auto_sync_variables', '0');
    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_classes', true);
    await waitForRestSetting(page, 'elementor_auto_sync_classes', '1');
    await closeTopbarAutosaveMenu(page);

    await page.reload();
    await openPluginPage(page);
    await openTopbarAutosaveMenu(page);
    await expect(page.locator('[data-ecf-topbar-setting="elementor_auto_sync_enabled"]').first()).toBeChecked();
    await expect(page.locator('[data-ecf-topbar-setting="elementor_auto_sync_variables"]').first()).not.toBeChecked();
    await expect(page.locator('[data-ecf-topbar-setting="elementor_auto_sync_classes"]').first()).toBeChecked();

    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_enabled', originalEnabled);
    await waitForRestSetting(page, 'elementor_auto_sync_enabled', originalEnabled ? '1' : '0');
    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_variables', originalVariables);
    await waitForRestSetting(page, 'elementor_auto_sync_variables', originalVariables ? '1' : '0');
    await setTopbarAutosaveSetting(page, 'elementor_auto_sync_classes', originalClasses);
    await waitForRestSetting(page, 'elementor_auto_sync_classes', originalClasses ? '1' : '0');
    await closeTopbarAutosaveMenu(page);
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

    const originalState = await getFontFamilySavedState(page, 'base_font_family');
    const originalPreset = originalState.preset;

    if (originalPreset !== 'var(--ecf-font-secondary)') {
      await selectBaseFontFamilyPreset(page, 'var(--ecf-font-secondary)');
      await waitForSuccessNotice(page);
    }

    const frontendStyles = await getFrontendStyles(page);
    expect(frontendStyles.rootFontFamily).toContain('Georgia');
    expect(frontendStyles.bodyFontFamily).toContain('Georgia');

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    if (originalPreset !== 'var(--ecf-font-secondary)') {
      const changed = await restoreFontFamilySavedState(page, 'base_font_family', originalPreset, originalState.custom);
      if (changed) {
        await waitForSuccessNotice(page);
      }
    }
  });

  test('local font imported from the base font family flow becomes the active body font and token', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const originalFamilies = await getLocalFontFamilies(page);
    const hadFontAlready = originalFamilies.includes('Manrope');

    await openGeneralTab(page, 'website');
    const originalState = await getFontFamilySavedState(page, 'base_font_family');
    const originalPreset = originalState.preset;
    const originalCustom = originalState.custom;

    await importLibraryFontForField(page, 'base_font_family', 'Manrope');
    await waitForSuccessNotice(page);

    await openGeneralTab(page, 'website');
    await expect(getGeneralField(page, 'base_font_family').locator('[data-ecf-font-family-preset]').first()).toHaveValue("'Manrope'");

    await openPanel(page, 'typography');
    await expect(page.locator('[data-ecf-typography-body-current]').first()).toContainText(/Manrope/);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const reloadedSelect = getGeneralField(page, 'base_font_family').locator('[data-ecf-base-font-preset]').first();
    await expect(reloadedSelect).toHaveValue("'Manrope'");

    await openPanel(page, 'typography');
    await expect(page.locator('[data-ecf-typography-body-current]').first()).toContainText(/Manrope/);

    const frontendStyles = await getFrontendStyles(page);
    expect(frontendStyles.rootBodyFontFamily).toContain('Manrope');
    expect(frontendStyles.rootFontFamily).toContain('Manrope');
    expect(frontendStyles.bodyFontFamily).toContain('Manrope');

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    const changed = await restoreFontFamilySavedState(page, 'base_font_family', originalPreset, originalCustom);
    if (changed) {
      await waitForSuccessNotice(page);
    }

    if (!hadFontAlready) {
      await openPanel(page, 'typography');
      const familiesAfter = await getLocalFontFamilies(page);
      const manropeIndex = familiesAfter.indexOf('Manrope');
      if (manropeIndex !== -1) {
        const rowsAfter = await getLocalFontRows(page);
        await removeLocalFontRow(rowsAfter.nth(manropeIndex));
        await waitForSuccessNotice(page);
      }
    }
  });

  test('library fonts can be imported locally, assigned to body and headings, and apply the body text size on the frontend', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const originalFamilies = await getLocalFontFamilies(page);
    const hadBodyFontAlready = originalFamilies.includes('Manrope');
    const hadHeadingFontAlready = originalFamilies.includes('Merriweather');

    await openGeneralTab(page, 'website');
    const bodyField = getGeneralField(page, 'base_font_family');
    const headingField = getGeneralField(page, 'heading_font_family');
    const bodySizeField = getGeneralField(page, 'base_body_text_size');
    const originalBodyState = await getFontFamilySavedState(page, 'base_font_family');
    const originalBodyPreset = originalBodyState.preset;
    const originalBodyCustom = originalBodyState.custom;
    const originalHeadingState = await getFontFamilySavedState(page, 'heading_font_family');
    const originalHeadingPreset = originalHeadingState.preset;
    const originalHeadingCustom = originalHeadingState.custom;
    const originalBodySizeValue = await bodySizeField.locator('[data-ecf-size-value-input]').first().inputValue();
    const originalBodySizeFormat = await bodySizeField.locator('[data-ecf-format-input]').first().inputValue();
    const targetBodySizeValue = originalBodySizeValue === '18' && originalBodySizeFormat === 'px' ? '19' : '18';
    const targetBodyFont = 'Poppins';
    const targetHeadingFont = 'Merriweather';
    const targetBodyPreset = "'" + targetBodyFont + "'";
    const targetHeadingPreset = "'" + targetHeadingFont + "'";

    await importLibraryFontForField(page, 'base_font_family', targetBodyFont);
    await waitForSuccessNotice(page);
    await importLibraryFontForField(page, 'heading_font_family', targetHeadingFont);
    await waitForSuccessNotice(page);
    await setBodyTextSize(page, targetBodySizeValue, 'px');
    await waitForSuccessNotice(page);
    await waitForRestSetting(page, 'base_body_text_size', targetBodySizeValue + 'px');

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    await expect(getGeneralField(page, 'base_font_family').locator('[data-ecf-font-family-preset]').first()).toHaveValue(targetBodyPreset);
    await expect(getGeneralField(page, 'heading_font_family').locator('[data-ecf-font-family-preset]').first()).toHaveValue(targetHeadingPreset);
    await expect(getGeneralField(page, 'base_body_text_size').locator('[data-ecf-size-value-input]').first()).toHaveValue(targetBodySizeValue);
    await expect(getGeneralField(page, 'base_body_text_size').locator('[data-ecf-format-input]').first()).toHaveValue('px');

    const frontendTypography = await getFrontendTypographySnapshot(page);
    expect(frontendTypography.rootBodyFontFamily).toContain(targetBodyFont);
    expect(frontendTypography.bodyFontFamily).toContain(targetBodyFont);
    expect(frontendTypography.rootHeadingFontFamily).toContain(targetHeadingFont);
    expect(frontendTypography.headingFontFamily).toContain(targetHeadingFont);
    expect(frontendTypography.headingFontFamily).not.toContain(targetBodyFont);
    expect(frontendTypography.rootBodyTextSize).toBe(targetBodySizeValue + 'px');
    expect(frontendTypography.bodyFontSize).toBe(targetBodySizeValue + 'px');

    await openPluginPage(page);
    await openGeneralTab(page, 'website');
    const bodyChanged = await restoreFontFamilySavedState(page, 'base_font_family', originalBodyPreset, originalBodyCustom);
    if (bodyChanged) {
      await waitForSuccessNotice(page);
    }

    const headingChanged = await restoreFontFamilySavedState(page, 'heading_font_family', originalHeadingPreset, originalHeadingCustom);
    if (headingChanged) {
      await waitForSuccessNotice(page);
    }

    await setBodyTextSize(page, originalBodySizeValue, originalBodySizeFormat);
    await waitForSuccessNotice(page);

    if (!hadBodyFontAlready || !hadHeadingFontAlready) {
      await openPanel(page, 'typography');
      let familiesAfter = await getLocalFontFamilies(page);
      let rowsAfter = await getLocalFontRows(page);

      if (!hadBodyFontAlready) {
        const bodyFontIndex = familiesAfter.indexOf(targetBodyFont);
        if (bodyFontIndex !== -1) {
          await removeLocalFontRow(rowsAfter.nth(bodyFontIndex));
          await waitForSuccessNotice(page);
          familiesAfter = await getLocalFontFamilies(page);
          rowsAfter = await getLocalFontRows(page);
        }
      }

      if (!hadHeadingFontAlready) {
        const headingFontIndex = familiesAfter.indexOf(targetHeadingFont);
        if (headingFontIndex !== -1) {
          await removeLocalFontRow(rowsAfter.nth(headingFontIndex));
          await waitForSuccessNotice(page);
        }
      }
    }
  });

  test('typography panel mirrors the font library workflow without manual upload controls', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'typography');

    const panel = page.locator('.ecf-panel[data-panel="typography"]');
    const bodyAccordion = panel.locator('.ecf-typography-font-card').first();
    await expect(bodyAccordion).toContainText(/Body Font|Body-Schrift/i);
    await bodyAccordion.evaluate((element) => {
      if (!element.open) {
        element.open = true;
        element.dispatchEvent(new Event('toggle'));
      }
    });
    await expect(panel.locator('[data-ecf-general-field="base_font_family"] [data-ecf-font-family-search]').first()).toBeVisible();
    const headingAccordion = panel.locator('.ecf-typography-font-card').nth(1);
    await headingAccordion.evaluate((element) => {
      if (!element.open) {
        element.open = true;
        element.dispatchEvent(new Event('toggle'));
      }
    });
    await expect(panel.locator('[data-ecf-general-field="heading_font_family"] [data-ecf-font-family-search]').first()).toBeVisible();
    await expect(panel.getByText(/Imported Local Fonts|Importierte lokale Schriften/i)).toBeVisible();
  });

  test('font family search can find Google library fonts beyond the starter list and import them locally', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const field = getGeneralField(page, 'base_font_family');
    const search = field.locator('[data-ecf-font-family-search]').first();
    const select = field.locator('[data-ecf-font-family-preset]').first();
    const panel = field.locator('[data-ecf-font-picker-panel]').first();
    const originalPreset = await select.inputValue();
    const originalCustom = await field.locator('[data-ecf-font-family-custom]').first().inputValue().catch(() => '');
    const targetFamily = 'Nunito';

    await expect(search).toBeVisible();
    await search.click();
    await expect(panel).toBeVisible();
    await expect(select).toBeVisible();
    await expect(select.locator('optgroup').first()).toHaveAttribute('label', /Local fonts|Lokale Schriften/i);
    await expect(select.locator('optgroup').last()).toHaveAttribute('label', /Google Fonts library|Google-Fonts/i);

    await search.fill(targetFamily);
    await expect
      .poll(async () => {
        return await select.locator(`option[value="__library__|${targetFamily}"]`).count();
      })
      .toBeGreaterThan(0);
    await select.selectOption(`__library__|${targetFamily}`);
    await waitForSuccessNotice(page);
    await expect(select).toHaveValue(`'${targetFamily}'`);

    await selectBaseFontFamilyPreset(page, originalPreset);
    if (originalPreset === '__custom__') {
      const customField = getGeneralField(page, 'base_font_family').locator('[data-ecf-font-family-custom]').first();
      await customField.fill(originalCustom);
      await customField.blur();
    }
    await waitForSuccessNotice(page);
  });

  test('font family picker exposes the full bundled Google Fonts list in the visible selection', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const field = getGeneralField(page, 'base_font_family');
    const select = field.locator('[data-ecf-font-family-preset]').first();
    const panel = field.locator('[data-ecf-font-picker-panel]').first();
    const targetFamily = 'Zen Tokyo Zoo';

    await field.locator('[data-ecf-font-family-search]').first().click();
    await expect(panel).toBeVisible();
    await expect(select).toBeVisible();
    await expect(select.locator('optgroup').last()).toHaveAttribute('label', /Google Fonts library|Google-Fonts/i);

    const libraryOptionCount = await select
      .locator('optgroup')
      .last()
      .locator('option')
      .evaluateAll((nodes) => nodes.length);

    expect(libraryOptionCount).toBeGreaterThan(1500);
    await expect(select.locator(`option[value="__library__|${targetFamily}"]`)).toHaveCount(1);
  });

  test('font family picker list can be scrolled to deep Google font entries', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const field = getGeneralField(page, 'base_font_family');
    const search = field.locator('[data-ecf-font-family-search]').first();
    const select = field.locator('[data-ecf-font-family-preset]').first();
    const panel = field.locator('[data-ecf-font-picker-panel]').first();
    const targetFamily = 'Zen Tokyo Zoo';

    await search.click();
    await expect(panel).toBeVisible();
    await expect(select).toBeVisible();
    await search.fill(targetFamily);
    await expect(select.locator(`option[value="__library__|${targetFamily}"]`)).toHaveCount(1);

    const scrollTop = await select.evaluate((node, family) => {
      const element = node;
      const option = element.querySelector(`option[value="__library__|${family}"]`);
      if (!option) {
        return -1;
      }
      option.scrollIntoView({ block: 'nearest' });
      return element.scrollTop;
    }, targetFamily);

    expect(scrollTop).toBeGreaterThan(0);
  });

  test('font family picker starts closed and shows the current selection before opening', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const field = getGeneralField(page, 'base_font_family');
    const current = field.locator('[data-ecf-font-current-value]').first();
    const search = field.locator('[data-ecf-font-family-search]').first();
    const panel = field.locator('[data-ecf-font-picker-panel]').first();

    await expect(current).toBeVisible();
    await expect(current).not.toHaveText('');
    await expect(panel).toBeHidden();

    await search.click();
    await expect(panel).toBeVisible();
  });

  test('favorite font family cards keep their picker closed until the user opens it', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'website');

    const toggle = getGeneralField(page, 'base_font_family').locator('[data-ecf-general-favorite-toggle]').first();
    const originallyChecked = await toggle.isChecked();

    if (!originallyChecked) {
      await getGeneralField(page, 'base_font_family').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
      await waitForSuccessNotice(page);
    }

    try {
      await openGeneralTab(page, 'favorites');

      const card = page.locator('[data-ecf-favorite-card="base_font_family"]').first();
      const current = card.locator('[data-ecf-font-current-value]').first();
      const panel = card.locator('[data-ecf-font-picker-panel]').first();
      const search = card.locator('[data-ecf-font-family-search]').first();

      await expect(card).toBeVisible();
      await expect(current).toBeVisible();
      await expect(panel).toBeHidden();

      await search.click();
      await expect(panel).toBeVisible();
    } finally {
      if (!originallyChecked) {
        await openPluginPage(page);
        await openGeneralTab(page, 'website');
        await getGeneralField(page, 'base_font_family').locator('.ecf-favorite-toggle').first().evaluate((element) => element.click());
        await waitForSuccessNotice(page);
      }
    }
  });

  test('ui skin persists after reload', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    const presetInput = page.locator('[data-ecf-admin-design-preset]').first();
    const modeInput = page.locator('[data-ecf-admin-design-mode]').first();
    const originalPreset = await presetInput.inputValue();
    const nextPreset = originalPreset === 'v3' ? 'current' : 'v3';

    await selectDesignPreset(page, nextPreset);
    await waitForSuccessNotice(page);

    await page.reload();
    await openPluginPage(page);
    await openGeneralTab(page, 'ui');

    await expect(page.locator('[data-ecf-admin-design-preset]').first()).toHaveValue(nextPreset);
    await expect(page.locator('[data-ecf-admin-design-mode]').first()).toHaveValue('dark');

    await selectDesignPreset(page, originalPreset);
    await waitForSuccessNotice(page);
    await expect(modeInput).toHaveValue('dark');
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
