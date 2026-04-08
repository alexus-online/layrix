const { expect } = require('@playwright/test');

const wpUrl = (process.env.ECF_WP_URL || '').replace(/\/$/, '');
const adminUser = process.env.ECF_WP_ADMIN_USER || process.env.ECF_WP_USER || '';
const adminPassword = process.env.ECF_WP_ADMIN_PASSWORD || '';
const loginPath = process.env.ECF_WP_LOGIN_PATH || '/wp-login.php';
const pluginPath = process.env.ECF_WP_ADMIN_PAGE || '/wp-admin/admin.php?page=ecf-framework';
const allowMutation = process.env.ECF_UI_ALLOW_MUTATION === '1';

function requiredEnvMissing() {
  return !wpUrl || !adminUser || !adminPassword;
}

function mutationNotAllowed() {
  return !allowMutation;
}

async function loginToWordPress(page) {
  const adminUrl = `${wpUrl}/wp-admin/`;
  const loginUrl = /^https?:\/\//i.test(loginPath)
    ? loginPath
    : `${wpUrl}/${String(loginPath).replace(/^\/+/, '')}`;

  await page.goto(adminUrl, { waitUntil: 'domcontentloaded' });

  if (page.url().includes('/wp-admin/')) {
    return;
  }

  await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });

  const usernameField = page
    .locator('#user_login, input[name=\"log\"], input[name=\"username\"]')
    .or(page.getByLabel(/Benutzername|Username|E-?Mail/i))
    .or(page.getByPlaceholder(/Benutzername|Username|E-?Mail/i))
    .first();
  const passwordField = page
    .locator('#user_pass, input[name=\"pwd\"], input[name=\"password\"]')
    .or(page.getByLabel(/Passwort|Password/i))
    .or(page.getByPlaceholder(/Passwort|Password/i))
    .first();
  await expect(usernameField).toBeVisible();
  await expect(passwordField).toBeVisible();
  await usernameField.fill(adminUser);
  await passwordField.fill(adminPassword);

  const visibleButton = page.locator('button, [role="button"]').filter({ hasText: /Anmelden|Login|Log in|Sign in/i }).first();
  if (await visibleButton.count()) {
    await visibleButton.click();
  } else {
    await passwordField.press('Enter');
  }

  await page.waitForTimeout(1200);

  if (!/wp-admin/i.test(page.url())) {
    const fallbackTargets = [
      `${wpUrl}${pluginPath}`,
      `${wpUrl}/wp-admin/`,
    ];

    for (const target of fallbackTargets) {
      await page.goto(target, { waitUntil: 'domcontentloaded' });
      if (/wp-admin/i.test(page.url())) {
        break;
      }
    }
  }

  await expect(page).toHaveURL(/wp-admin/i);
}

async function openPluginPage(page) {
  await page.goto(`${wpUrl}${pluginPath}`);
  await expect(page.locator('.ecf-wrap')).toBeVisible();
}

async function openPanel(page, panel) {
  await page.locator(`.ecf-nav-item[data-panel="${panel}"]`).click();
  await expect(page.locator(`.ecf-panel[data-panel="${panel}"]`)).toBeVisible();
}

async function openGeneralTab(page, tab) {
  await openPanel(page, 'components');
  await page.locator(`[data-ecf-general-tab="${tab}"]`).click();
  await expect(page.locator(`[data-ecf-general-section="${tab}"]`)).toBeVisible();
}

async function chooseFormat(field, value) {
  const formatInput = field.locator('[data-ecf-format-input], [data-ecf-size-format-input]').first();
  if ((await formatInput.inputValue()) === value) {
    return;
  }

  await field.locator('[data-ecf-format-trigger]').first().click();
  await field.locator(`[data-ecf-format-option][data-value="${value}"]`).click();
  await expect(formatInput).toHaveValue(value);
}

function getGeneralField(page, name) {
  return page.locator(`[data-ecf-general-section].is-active [data-ecf-general-field="${name}"]`).first();
}

async function setBodyTextSize(page, value, format = 'px') {
  const field = getGeneralField(page, 'base_body_text_size');
  await expect(field).toBeVisible();
  await chooseFormat(field, format);
  const input = field.locator('[data-ecf-size-value-input]').first();
  await input.fill(value);
  await input.blur();
  return field;
}

async function getBodyTextSizeState(page) {
  const field = getGeneralField(page, 'base_body_text_size');
  return {
    field,
    value: await field.locator('[data-ecf-size-value-input]').first().inputValue(),
    format: await field.locator('[data-ecf-format-input]').first().inputValue(),
  };
}

async function selectBaseFontFamilyPreset(page, presetValue) {
  const field = getGeneralField(page, 'base_font_family');
  const select = field.locator('[data-ecf-base-font-preset]').first();
  await expect(select).toBeVisible();
  await select.selectOption(presetValue);
  return field;
}

async function getRootCssVariable(page, variableName) {
  return page.evaluate((name) => {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }, variableName);
}

async function getBodyComputedFontFamily(page) {
  return page.evaluate(() => getComputedStyle(document.body).fontFamily);
}

async function getFrontendStyles(page) {
  await page.goto(`${wpUrl}/`, { waitUntil: 'domcontentloaded' });
  return page.evaluate(() => ({
    rootFontFamily: getComputedStyle(document.documentElement).getPropertyValue('--ecf-base-font-family').trim(),
    bodyFontFamily: getComputedStyle(document.body).fontFamily,
  }));
}

async function toggleGeneralFavorite(page, fieldName) {
  const field = getGeneralField(page, fieldName);
  const toggle = field.locator('[data-ecf-general-favorite-toggle]').first();
  const label = field.locator('.ecf-favorite-toggle').first();
  const originalChecked = await toggle.isChecked();
  await label.evaluate((element) => element.click());
  if (originalChecked) {
    await expect(toggle).not.toBeChecked();
  } else {
    await expect(toggle).toBeChecked();
  }
  return { field, toggle, originalChecked };
}

async function selectDesignPreset(page, preset) {
  await page.locator('[data-ecf-general-section="ui"]:visible').locator(`[data-ecf-admin-design-option][data-value="${preset}"]`).first().click();
}

async function selectDesignMode(page, mode) {
  await page.locator('[data-ecf-general-section="ui"]:visible').locator(`[data-ecf-admin-design-mode-option][data-value="${mode}"]`).first().click();
}

async function refreshSystemInfo(page) {
  const button = page.locator('[data-ecf-refresh-system-info]').first();
  await expect(button).toBeVisible();
  await button.click();
}

async function addTokenRow(page, group) {
  await page.locator(`.ecf-panel[data-panel="tokens"] .ecf-add-row[data-group="${group}"]`).click();
}

async function removeTokenRow(page, group) {
  await page.locator(`.ecf-panel[data-panel="tokens"] .ecf-remove-last-row[data-group="${group}"]`).click();
}

async function getTokenRowCount(page, group) {
  return page.locator(`.ecf-panel[data-panel="tokens"] .ecf-table[data-group="${group}"] .ecf-row`).count();
}

async function fillColorRow(row, values) {
  if (values.name !== undefined) {
    await row.locator('input[name$="[name]"]').first().fill(values.name);
    await row.locator('input[name$="[name]"]').first().blur();
  }
  if (values.value !== undefined) {
    const hiddenValueInput = row.locator('.ecf-color-value-input').first();
    await hiddenValueInput.evaluate((node, value) => {
      node.value = value;
      node.dispatchEvent(new Event('input', { bubbles: true }));
      node.dispatchEvent(new Event('change', { bubbles: true }));
    }, values.value);
    const displayInput = row.locator('.ecf-color-value-display').first();
    if (await displayInput.count()) {
      await displayInput.fill(values.value);
      await displayInput.blur();
    }
  }
}

async function addTypographyStep(page, direction = 'larger') {
  await page.locator(`.ecf-panel[data-panel="typography"] [data-ecf-add-step="${direction}"]`).click();
}

async function removeTypographyStep(page, direction = 'larger') {
  await page.locator(`.ecf-panel[data-panel="typography"] [data-ecf-remove-step="${direction}"]`).click();
}

async function getTypographyStepCount(page) {
  return page.locator('.ecf-panel[data-panel="typography"] .ecf-scale-step-input').count();
}

async function addSpacingStep(page, direction = 'larger') {
  await page.locator(`.ecf-panel[data-panel="spacing"] [data-ecf-spacing-add="${direction}"]`).click();
}

async function removeSpacingStep(page, direction = 'larger') {
  await page.locator(`.ecf-panel[data-panel="spacing"] [data-ecf-spacing-remove="${direction}"]`).click();
}

async function getSpacingStepCount(page) {
  return page.locator('.ecf-panel[data-panel="spacing"] .ecf-spacing-step-input').count();
}

async function switchInterfaceLanguage(page, language) {
  const select = getGeneralField(page, 'interface_language').locator('select').first();
  await select.selectOption(language);
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.ecf-wrap')).toBeVisible();
  await expect(getGeneralField(page, 'interface_language').locator('select').first()).toHaveValue(language, { timeout: 15000 });
}

async function addLocalFontRow(page) {
  await page.locator('.ecf-panel[data-panel="typography"] .ecf-add-local-font:visible').first().click();
}

async function getLocalFontRows(page) {
  return page.locator('[data-local-font-table] .ecf-font-file-row');
}

async function fillLocalFontRow(row, values) {
  if (values.key !== undefined) {
    await row.locator('input').nth(0).fill(values.key);
  }
  if (values.family !== undefined) {
    await row.locator('input').nth(1).fill(values.family);
  }
  if (values.url !== undefined) {
    await row.locator('.ecf-font-file-url').evaluate((element, nextValue) => {
      element.value = nextValue;
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));
    }, values.url);
  }
  if (values.weight !== undefined) {
    await row.locator('input').nth(3).fill(values.weight);
  }
  if (values.style !== undefined) {
    await row.locator('select').nth(0).selectOption(values.style);
  }
  if (values.display !== undefined) {
    await row.locator('select').nth(1).selectOption(values.display);
  }
}

async function removeLocalFontRow(row) {
  await row.locator('.ecf-remove-row').click();
}

async function setImportFile(page, payload, name = 'ecf-ui-import.json') {
  await page.locator('[data-ecf-import-file]').first().setInputFiles({
    name,
    mimeType: 'application/json',
    buffer: Buffer.from(JSON.stringify(payload)),
  });
}

async function setImportFileFromPath(page, filePath) {
  await page.locator('[data-ecf-import-file]').first().setInputFiles(filePath);
}

async function getImportPreview(page) {
  return {
    root: page.locator('[data-ecf-import-preview]').first(),
    title: page.locator('[data-ecf-import-preview-title]').first(),
    meta: page.locator('[data-ecf-import-preview-meta]').first(),
    warning: page.locator('[data-ecf-import-preview-warning]').first(),
  };
}

async function submitImport(page) {
  await Promise.all([
    page.waitForURL(/page=ecf-framework/i),
    page.locator('form.ecf-import-form button[type="submit"]').first().click(),
  ]);
}

async function downloadExport(page) {
  const downloadPromise = page.waitForEvent('download');
  await page.locator('form:has(input[name="action"][value="ecf_export"]) button[type="submit"]').first().click();
  return downloadPromise;
}

async function searchClasses(page, value) {
  const search = page.locator('[data-ecf-library-section="starter"] [data-ecf-class-search]').first();
  await search.fill(value);
  return search;
}

async function openUtilityLibrary(page) {
  const tab = page.locator('.ecf-panel[data-panel="utilities"] [data-ecf-library-tab="utility"]').first();
  await tab.evaluate((element) => element.click());
}

function getUtilityLibrarySection(page) {
  return page.locator('.ecf-panel[data-panel="utilities"] [data-ecf-library-section="utility"]:visible').first();
}

async function toggleUtilityClass(page, className) {
  const toggle = page.locator(`[data-ecf-library-section="utility"] [data-class-name="${className}"] .ecf-utility-class-toggle`).first();
  const originalChecked = await toggle.isChecked();
  await toggle.setChecked(!originalChecked);
  return { toggle, originalChecked };
}

async function openCustomStarterTier(page) {
  await page.locator('[data-ecf-class-tier="custom"]').click();
}

async function getCustomStarterRows(page) {
  return page.locator('[data-ecf-starter-custom-rows] .ecf-starter-custom-row');
}

async function addCustomStarterRow(page) {
  await page.locator('[data-ecf-starter-custom-add]').click();
}

async function fillCustomStarterRow(row, name) {
  await row.locator('.ecf-custom-starter-name').fill(name);
  await row.locator('.ecf-custom-starter-name').blur();
}

async function removeLastCustomStarterRow(page) {
  await page.locator('[data-ecf-starter-custom-remove]').click();
}

async function generateBemClass(page, blockName, preset = 'custom') {
  await page.locator('[data-ecf-bem-preset]').selectOption(preset);
  await page.locator('[data-ecf-bem-block]').fill(blockName);
  await page.locator('[data-ecf-bem-add]').click();
}

async function searchVariables(page, value) {
  const search = page.locator('#ecf-global-search-input');
  await search.fill(value);
  await page.waitForTimeout(600);
  return search;
}

async function waitForVariableList(page, group = 'ecf') {
  const list = page.locator(`#ecf-varlist-${group}`);
  await expect(list).toBeVisible();
  await expect(list.locator('.ecf-loading')).toHaveCount(0);
  return list;
}

function getVariableRowByLabel(page, group, label) {
  return page.locator(`#ecf-varlist-${group} .ecf-var-row`).filter({
    has: page.locator('.ecf-var-label', { hasText: label }),
  }).first();
}

async function selectVariableRow(page, group, label) {
  const row = getVariableRowByLabel(page, group, label);
  await expect(row).toBeVisible();
  const checkbox = row.locator('.ecf-var-check').first();
  if (!(await checkbox.isChecked())) {
    await checkbox.check();
  }
  return row;
}

async function bulkDeleteSelected(page, group) {
  page.once('dialog', (dialog) => dialog.accept());
  await page.locator(`.ecf-delete-selected[data-group="${group}"]`).first().click();
}

async function deleteSearchResult(page, label) {
  const row = page.locator('.ecf-global-search__item').filter({ hasText: label }).first();
  const item = row.locator('[data-ecf-search-delete]').first();
  await expect(item).toBeVisible();
  page.once('dialog', (dialog) => dialog.accept());
  await item.click();
}

async function openFirstEditableVariable(page) {
  const editButtons = page.locator('[data-ecf-search-edit]');
  const count = await editButtons.count();
  if (!count) {
    return false;
  }
  await editButtons.first().click();
  return true;
}

async function getSearchEditModal(page) {
  const modal = page.locator('[data-ecf-search-edit-modal]').first();
  return {
    modal,
    id: modal.locator('[data-ecf-search-edit-id]').first(),
    label: modal.locator('[data-ecf-search-edit-label]').first(),
    type: modal.locator('[data-ecf-search-edit-type]').first(),
    value: modal.locator('[data-ecf-search-edit-value]').first(),
    format: modal.locator('[data-ecf-search-edit-format]').first(),
    note: modal.locator('[data-ecf-search-edit-note]').first(),
    save: modal.locator('[data-ecf-search-edit-save]').first(),
    close: modal.locator('[data-ecf-search-edit-close]').first(),
  };
}

async function saveSearchEditModal(page) {
  const modal = await getSearchEditModal(page);
  await modal.save.click();
  try {
    await expect(modal.modal).toBeHidden({ timeout: 10000 });
  } catch (error) {
    const noteText = await modal.note.textContent().catch(() => '');
    throw new Error((noteText || 'Search edit modal did not close after save.').trim());
  }
}

async function selectAllVisibleClasses(page) {
  const button = getUtilityLibrarySection(page).locator('[data-ecf-class-select-all]').first();
  await button.evaluate((element) => element.click());
}

async function getVisibleUtilityToggles(page) {
  return getUtilityLibrarySection(page).locator('.ecf-utility-class-item:visible .ecf-utility-class-toggle');
}

async function getVisibleUtilityToggleStates(page) {
  return getUtilityLibrarySection(page)
    .locator('.ecf-utility-class-item:visible')
    .evaluateAll((nodes) =>
      nodes.map((node) => {
        const input = node.querySelector('.ecf-utility-class-toggle');
        return {
          className: node.getAttribute('data-class-name') || '',
          checked: Boolean(input && input.checked),
        };
      })
    );
}

async function restoreVisibleUtilityToggleStates(page, originalStates) {
  const section = getUtilityLibrarySection(page);
  for (const item of originalStates) {
    const toggle = section.locator(`[data-class-name="${item.className}"] .ecf-utility-class-toggle`).first();
    if ((await toggle.isChecked()) !== item.checked) {
      await toggle.setChecked(item.checked);
    }
  }
}

async function triggerClassCleanup(page) {
  page.once('dialog', (dialog) => dialog.accept());
  await Promise.all([
    page.waitForURL(/page=ecf-framework/i),
    page.locator('form:has(input[name="action"][value="ecf_class_cleanup"]) button[type="submit"]').first().click(),
  ]);
}

async function triggerNativeCleanup(page) {
  page.once('dialog', (dialog) => dialog.accept());
  await Promise.all([
    page.waitForURL(/page=ecf-framework/i),
    page.locator('form:has(input[name="action"][value="ecf_native_cleanup"]) button[type="submit"]').first().click(),
  ]);
}

async function openSystemDebugCard(page) {
  const debugCard = page.locator('[data-ecf-layout-item="system-debug"]').first();
  await expect(debugCard).toBeVisible();
  if (!(await debugCard.getAttribute('open'))) {
    await debugCard.evaluate((element) => {
      element.open = true;
      element.dispatchEvent(new Event('toggle'));
    });
  }
  await expect(debugCard).toHaveAttribute('open', '');
  await expect(debugCard.getByRole('button', { name: /Clear|Leeren/i }).first()).toBeVisible();
  return debugCard;
}

async function clearDebugHistory(page) {
  page.once('dialog', (dialog) => dialog.accept());
  const debugCard = await openSystemDebugCard(page);
  const clearButton = debugCard.getByRole('button', { name: /Clear|Leeren/i }).first();
  await expect(clearButton).toBeVisible();
  await Promise.all([
    page.waitForURL(/page=ecf-framework.*ecf_sync=ok.*ecf_message=/i),
    clearButton.click(),
  ]);
  await expect(page.locator('.notice-success, .updated, .notice').filter({ hasText: /Debug history cleared/i }).first()).toBeVisible();
}

async function triggerClassSync(page) {
  await Promise.all([
    page.waitForURL(/page=ecf-framework/i),
    page.locator('[data-ecf-class-sync-button]:visible').first().click(),
  ]);
}

async function triggerNativeSync(page) {
  await Promise.all([
    page.waitForURL(/page=ecf-framework/i),
    page.locator('form:has(input[name="action"][value="ecf_native_sync"]) button[type="submit"]').first().click(),
  ]);
}

async function waitForSuccessNotice(page) {
  const notice = page.locator('.ecf-autosave-notice');
  await expect(notice).toBeVisible();
  await expect(notice).toHaveClass(/ecf-panel-notice--success/);
}

async function waitForErrorNotice(page) {
  const notice = page.locator('.ecf-autosave-notice');
  await expect(notice).toBeVisible();
  await expect(notice).toHaveClass(/ecf-panel-notice--error/);
}

async function fetchRestSettings(page) {
  return page.evaluate(async () => {
    const response = await fetch(window.ecfAdmin.restUrl, {
      method: 'GET',
      headers: {
        'X-WP-Nonce': window.ecfAdmin.restNonce,
      },
      credentials: 'same-origin',
    });

    if (!response.ok) {
      throw new Error(`Could not fetch REST settings (${response.status}).`);
    }

    const payload = await response.json();
    return payload.settings || {};
  });
}

async function updateRestSettings(page, settings) {
  return page.evaluate(async (nextSettings) => {
    const response = await fetch(window.ecfAdmin.restUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.ecfAdmin.restNonce,
      },
      credentials: 'same-origin',
      body: JSON.stringify({ settings: nextSettings }),
    });

    if (!response.ok) {
      throw new Error(`Could not update REST settings (${response.status}).`);
    }

    return response.json();
  }, settings);
}

async function reorderLayoutGroup(page, groupName, sourceItemId, targetItemId) {
  const sourceHandle = page.locator(`[data-ecf-layout-group="${groupName}"] [data-ecf-layout-item="${sourceItemId}"] [data-ecf-layout-handle]`).first();
  const targetHandle = page.locator(`[data-ecf-layout-group="${groupName}"] [data-ecf-layout-item="${targetItemId}"] [data-ecf-layout-handle]`).first();
  await sourceHandle.dragTo(targetHandle);
}

async function getLayoutOrder(page, groupName) {
  return page.locator(`[data-ecf-layout-group="${groupName}"] > [data-ecf-layout-item]`).evaluateAll((nodes) =>
    nodes.map((node) => node.getAttribute('data-ecf-layout-item'))
  );
}

module.exports = {
  expect,
  requiredEnvMissing,
  mutationNotAllowed,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  chooseFormat,
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
  switchInterfaceLanguage,
  addLocalFontRow,
  getLocalFontRows,
  fillLocalFontRow,
  removeLocalFontRow,
  setImportFile,
  setImportFileFromPath,
  getImportPreview,
  submitImport,
  downloadExport,
  searchClasses,
  openUtilityLibrary,
  selectAllVisibleClasses,
  getVisibleUtilityToggles,
  getVisibleUtilityToggleStates,
  restoreVisibleUtilityToggleStates,
  toggleUtilityClass,
  openCustomStarterTier,
  getCustomStarterRows,
  addCustomStarterRow,
  fillCustomStarterRow,
  removeLastCustomStarterRow,
  generateBemClass,
  searchVariables,
  waitForVariableList,
  getVariableRowByLabel,
  selectVariableRow,
  bulkDeleteSelected,
  deleteSearchResult,
  openFirstEditableVariable,
  getSearchEditModal,
  saveSearchEditModal,
  openSystemDebugCard,
  clearDebugHistory,
  triggerClassSync,
  triggerNativeSync,
  triggerClassCleanup,
  triggerNativeCleanup,
  waitForSuccessNotice,
  waitForErrorNotice,
  fetchRestSettings,
  updateRestSettings,
  reorderLayoutGroup,
  getLayoutOrder,
};
