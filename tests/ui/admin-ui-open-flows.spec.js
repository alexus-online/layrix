const { test } = require('@playwright/test');
const {
  expect,
  requiredEnvMissing,
  mutationNotAllowed,
  loginToWordPress,
  openPluginPage,
  openPanel,
  openGeneralTab,
  setImportFile,
  getImportPreview,
  searchVariables,
  openFirstEditableVariable,
  getSearchEditModal,
  saveSearchEditModal,
  openUtilityLibrary,
  selectAllVisibleClasses,
  getVisibleUtilityToggles,
  getVisibleUtilityToggleStates,
  restoreVisibleUtilityToggleStates,
  waitForSuccessNotice,
  triggerClassCleanup,
  triggerNativeCleanup,
  triggerClassSync,
  triggerNativeSync,
} = require('./helpers/ecf-admin');

function normalizeVariableLabel(label) {
  return String(label || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9\-_ ]+/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

test.describe('ECF open UI flows', () => {
  test.skip(requiredEnvMissing, 'ECF_WP_URL, ECF_WP_ADMIN_USER/ECF_WP_USER and ECF_WP_ADMIN_PASSWORD are required for browser UI checks.');

  test('import preview shows a warning when plugin versions differ', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    await setImportFile(page, {
      meta: {
        plugin: 'ECF Framework',
        plugin_version: '9.9.9',
        schema_version: 1,
        exported_at: '2026-04-08T12:00:00Z',
      },
      settings: {
        root_font_size: '62.5',
      },
    }, 'ecf-ui-warning.json');

    const preview = await getImportPreview(page);
    await expect(preview.root).toBeVisible();
    await expect(preview.warning).toBeVisible();
    await expect(preview.warning).not.toBeEmpty();
  });

  test('editable foreign variable can be updated and restored through the modal', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'variables');

    let opened = false;
    for (const query of ['text-2xl', 'test', 'text', '']) {
      await searchVariables(page, query);
      opened = await openFirstEditableVariable(page);
      if (opened) {
        break;
      }
    }
    test.skip(!opened, 'No editable foreign variables available on this test site.');

    const modal = await getSearchEditModal(page);
    await expect(modal.modal).toBeVisible();

    const originalLabel = await modal.label.inputValue();
    const originalValue = await modal.value.inputValue();
    const originalType = await modal.type.inputValue();
    const originalFormat = await modal.format.inputValue();
    const persistedLabel = normalizeVariableLabel(originalLabel) || originalLabel;
    test.skip(originalType !== 'global-size-variable', 'No editable foreign size variable available on this test site.');
    const nextValue = '18';

    try {
      await modal.label.fill(persistedLabel);
      if ((await modal.format.count()) && (await modal.format.inputValue()) !== 'px') {
        await modal.format.selectOption('px');
      }
      await modal.value.fill(nextValue);
      await saveSearchEditModal(page);

      await searchVariables(page, persistedLabel);
      const reopened = await openFirstEditableVariable(page);
      test.skip(!reopened, 'Edited foreign variable could not be reopened for verification.');
      const verifyModal = await getSearchEditModal(page);
      await expect(verifyModal.value).toHaveValue(nextValue);
      if ((await verifyModal.format.count())) {
        await expect(verifyModal.format).toHaveValue('px');
      }
    } finally {
      const restoreModal = await getSearchEditModal(page);
      let restoreReady = await restoreModal.modal.isVisible();
      if (!restoreReady) {
        await searchVariables(page, persistedLabel);
        restoreReady = await openFirstEditableVariable(page);
      }

      if (restoreReady) {
        const activeRestoreModal = await getSearchEditModal(page);
        await activeRestoreModal.label.fill(persistedLabel);
        if ((await activeRestoreModal.type.inputValue()) !== originalType) {
          await activeRestoreModal.type.selectOption(originalType);
        }
        await activeRestoreModal.value.fill(originalValue);
        if (originalFormat && (await activeRestoreModal.format.count()) && (await activeRestoreModal.format.inputValue()) !== originalFormat) {
          await activeRestoreModal.format.selectOption(originalFormat);
        }
        await saveSearchEditModal(page);
      }
    }
  });

  test('utility select-all toggles visible classes and can be restored', async ({ page }) => {
    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'utilities');
    await openUtilityLibrary(page);

    const visibleToggles = await getVisibleUtilityToggles(page);
    const count = await visibleToggles.count();
    test.skip(count === 0, 'No visible utility class toggles available.');

    const originalStates = await getVisibleUtilityToggleStates(page);
    const allInitiallyChecked = originalStates.every((item) => item.checked);

    await selectAllVisibleClasses(page);
    await waitForSuccessNotice(page);
    const firstPassStates = await getVisibleUtilityToggleStates(page);
    expect(
      allInitiallyChecked
        ? firstPassStates.every((item) => !item.checked)
        : firstPassStates.every((item) => item.checked)
    ).toBeTruthy();

    if (allInitiallyChecked) {
      await selectAllVisibleClasses(page);
      await waitForSuccessNotice(page);
      const selectedStates = await getVisibleUtilityToggleStates(page);
      expect(selectedStates.every((item) => item.checked)).toBeTruthy();
    } else {
      await selectAllVisibleClasses(page);
      await waitForSuccessNotice(page);
      const clearedStates = await getVisibleUtilityToggleStates(page);
      expect(clearedStates.every((item) => !item.checked)).toBeTruthy();
      await selectAllVisibleClasses(page);
      await waitForSuccessNotice(page);
    }

    await restoreVisibleUtilityToggleStates(page, originalStates);
    await waitForSuccessNotice(page);
  });

  test('class cleanup action can be triggered on mutation-enabled sites', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run cleanup UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const button = page.locator('form:has(input[name="action"][value="ecf_class_cleanup"]) button[type="submit"]').first();
    test.skip(await button.isDisabled(), 'No ECF classes available for cleanup on this test site.');

    await triggerClassCleanup(page);
    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();

    await openPanel(page, 'utilities');
    await triggerClassSync(page);
    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
  });

  test('native cleanup action can be triggered on mutation-enabled sites', async ({ page }) => {
    test.skip(mutationNotAllowed(), 'Set ECF_UI_ALLOW_MUTATION=1 to run cleanup UI flows.');

    await loginToWordPress(page);
    await openPluginPage(page);
    await openPanel(page, 'sync');

    const button = page.locator('form:has(input[name="action"][value="ecf_native_cleanup"]) button[type="submit"]').first();
    test.skip(await button.isDisabled(), 'No native ECF variables/classes available for cleanup on this test site.');

    await triggerNativeCleanup(page);
    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();

    await triggerNativeSync(page);
    await expect(page.locator('.notice, .updated, .notice-success').first()).toBeVisible();
  });
});
