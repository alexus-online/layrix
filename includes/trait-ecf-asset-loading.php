<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Asset_Loading_Trait {
    private function type_preview_texts_language_suffix($settings = null) {
        return $this->selected_interface_language($settings) === 'de' ? 'de' : 'en';
    }

    private function load_type_preview_texts($settings = null) {
        static $cache = [];

        $suffix = $this->type_preview_texts_language_suffix($settings);

        if (isset($cache[$suffix])) {
            return $cache[$suffix];
        }

        $base_path = dirname(__DIR__) . '/assets/data/type-preview-texts.';
        $path = $base_path . $suffix . '.json';
        $fallback_path = $base_path . 'en.json';
        $json = '';

        if (file_exists($path)) {
            $json = (string) file_get_contents($path);
        } elseif (file_exists($fallback_path)) {
            $json = (string) file_get_contents($fallback_path);
        }

        $decoded = json_decode($json, true);
        $cache[$suffix] = is_array($decoded) ? $decoded : [];

        return $cache[$suffix];
    }

    private function preview_text_key_for_step($step) {
        $normalized = strtolower(trim(str_replace('--ecf-text-', '', (string) $step)));

        if (in_array($normalized, ['xs', 's'], true)) {
            return 'xs';
        }

        if (in_array($normalized, ['3xl', '4xl'], true)) {
            return 'display';
        }

        if (in_array($normalized, ['m', 'l', 'xl', '2xl'], true)) {
            return $normalized;
        }

        return 'default';
    }

    private function type_preview_text_for_step($step, $settings = null) {
        $texts = $this->load_type_preview_texts($settings);
        $key = $this->preview_text_key_for_step($step);

        if (isset($texts[$key]) && is_string($texts[$key]) && $texts[$key] !== '') {
            return $texts[$key];
        }

        return isset($texts['default']) && is_string($texts['default']) ? $texts['default'] : '';
    }

    private function build_editor_preview_maps($settings) {
        $root_base_px = $this->get_root_font_base_px($settings);
        $spacing_preview_map = [];
        $type_preview_map = [];
        $radius_preview_map = [];

        foreach ($this->build_spacing_scale_preview($settings['spacing'], $root_base_px) as $item) {
            $token_key = strtolower(ltrim((string) ($item['token'] ?? ''), '-'));
            if ($token_key === '') {
                continue;
            }
            $spacing_preview_map[$token_key] = [
                'minPx'    => $item['min_px'] ?? '',
                'maxPx'    => $item['max_px'] ?? '',
                'cssValue' => $item['css_value'] ?? '',
            ];
        }

        foreach ($this->build_type_scale_preview($settings['typography']['scale'], $root_base_px) as $item) {
            $token_key = strtolower(ltrim((string) ($item['token'] ?? ''), '-'));
            if ($token_key === '') {
                continue;
            }
            $type_preview_map[$token_key] = [
                'minPx'    => $item['min_px'] ?? '',
                'maxPx'    => $item['max_px'] ?? '',
                'cssValue' => $item['css_value'] ?? '',
            ];
        }

        foreach (($settings['radius'] ?? []) as $row) {
            $name = sanitize_key($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $token_key = 'ecf-radius-' . $name;
            $radius_preview_map[$token_key] = [
                'minPx'    => $this->format_preview_number((float) ($row['min'] ?? 0), 3),
                'maxPx'    => $this->format_preview_number((float) ($row['max'] ?? ($row['min'] ?? 0)), 3),
                'cssValue' => $this->radius_css_value($row, 375, 1280, $root_base_px),
            ];
        }

        return [
            'spacingPreview' => $spacing_preview_map,
            'typePreview' => $type_preview_map,
            'radiusPreview' => $radius_preview_map,
        ];
    }

    private function asset_version($relative_path, $fallback) {
        $full_path = plugin_dir_path(__FILE__) . '../' . ltrim($relative_path, '/');
        if (!file_exists($full_path)) {
            return $fallback;
        }

        $hash = @md5_file($full_path);
        if (is_string($hash) && $hash !== '') {
            return substr($hash, 0, 12);
        }

        return filemtime($full_path) ?: $fallback;
    }

    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_ecf-framework') {
            return;
        }

        wp_enqueue_media();

        $settings = $this->get_settings();
        $preview_maps = $this->build_editor_preview_maps($settings);
        $admin_css_ver = [
            'base'         => $this->asset_version('assets/admin-base.css', '0.1.5'),
            'layout'       => $this->asset_version('assets/admin-layout.css', '0.1.5'),
            'forms'        => $this->asset_version('assets/admin-forms.css', '0.1.5'),
            'panels'       => $this->asset_version('assets/admin-panels.css', '0.1.5'),
            'ui'           => $this->asset_version('assets/admin-ui.css', '0.1.5'),
            'responsive'   => $this->asset_version('assets/admin-responsive.css', '0.1.5'),
            'v2'           => $this->asset_version('assets/admin-v2.css', '0.1.0'),
        ];
        $admin_js_ver  = $this->asset_version('assets/admin.js', '0.1.5');

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('ecf-admin-base',       plugins_url('assets/admin-base.css',       ECF_FRAMEWORK_FILE), [],                $admin_css_ver['base']);
        wp_enqueue_style('ecf-admin-layout',     plugins_url('assets/admin-layout.css',     ECF_FRAMEWORK_FILE), ['ecf-admin-base'],     $admin_css_ver['layout']);
        wp_enqueue_style('ecf-admin-forms',      plugins_url('assets/admin-forms.css',      ECF_FRAMEWORK_FILE), ['ecf-admin-layout'],   $admin_css_ver['forms']);
        wp_enqueue_style('ecf-admin-panels',     plugins_url('assets/admin-panels.css',     ECF_FRAMEWORK_FILE), ['ecf-admin-forms'],    $admin_css_ver['panels']);
        wp_enqueue_style('ecf-admin-ui',         plugins_url('assets/admin-ui.css',         ECF_FRAMEWORK_FILE), ['ecf-admin-panels'],   $admin_css_ver['ui']);
        wp_enqueue_style('ecf-admin-responsive', plugins_url('assets/admin-responsive.css', ECF_FRAMEWORK_FILE), ['ecf-admin-ui'],       $admin_css_ver['responsive']);
        wp_enqueue_style('ecf-admin-v2',         plugins_url('assets/admin-v2.css',         ECF_FRAMEWORK_FILE), ['ecf-admin-responsive'], $admin_css_ver['v2']);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('ecf-admin', plugins_url('assets/admin.js', ECF_FRAMEWORK_FILE), ['jquery', 'jquery-ui-sortable', 'wp-color-picker'], $admin_js_ver, false);
        $admin_v2_js_ver = $this->asset_version('assets/admin-v2.js', '0.1.0');
        wp_enqueue_script('ecf-admin-v2', plugins_url('assets/admin-v2.js', ECF_FRAMEWORK_FILE), [], $admin_v2_js_ver, true);
        wp_localize_script('ecf-admin', 'ecfAdmin', [
            'elementorAutoSync' => !empty($settings['elementor_auto_sync_enabled']),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_variables'),
            'restUrl' => esc_url_raw(rest_url('ecf-framework/v1/settings')),
            'syncRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/sync')),
            'fontImportRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/fonts/import')),
            'fontSearchRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/fonts/search')),
            'layoutRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/layout')),
            'elementorValuesRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/elementor-values')),
            'customPresetsRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/custom-presets')),
            'resetDefaultsUrl'    => esc_url_raw(rest_url('ecf-framework/v1/reset-defaults')),
'restNonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'loading'        => __('Loading…', 'ecf-framework'),
                'none'           => __('No entries found.', 'ecf-framework'),
                'select_all'     => __('Select all', 'ecf-framework'),
                'deselect_all'   => __('Deselect all', 'ecf-framework'),
                'none_selected'  => __('No entries selected.', 'ecf-framework'),
                'confirm_delete' => __(' entry/entries delete?', 'ecf-framework'),
                'deleting'       => __('Deleting…', 'ecf-framework'),
                'delete_sel'     => __('Delete selected', 'ecf-framework'),
                'error'          => __('Error: ', 'ecf-framework'),
                'type_color'     => __('Color', 'ecf-framework'),
                'type_size'      => __('Size', 'ecf-framework'),
                'type_string'    => __('String', 'ecf-framework'),
                'type_all'       => __('All', 'ecf-framework'),
                'type_other'     => __('Other', 'ecf-framework'),
                'type_spacing'   => __('Spacing', 'ecf-framework'),
                'type_typography'=> __('Typography', 'ecf-framework'),
                'type_layout'    => __('Layout', 'ecf-framework'),
                'type_radius'    => __('Radius', 'ecf-framework'),
                'type_shadow'    => __('Shadow', 'ecf-framework'),
                'type_class'     => __('Global Class', 'ecf-framework'),
                'col_name'       => __('Class Name', 'ecf-framework'),
                'col_variable_name' => __('Variable name', 'ecf-framework'),
                'col_type'       => __('Type', 'ecf-framework'),
                'col_value'      => __('Value', 'ecf-framework'),
                'choose_font'    => __('Choose font file', 'ecf-framework'),
                'use_font'       => __('Use this file', 'ecf-framework'),
                'select_file'    => __('Select file', 'ecf-framework'),
                'copy'           => __('Copy', 'ecf-framework'),
                'copied'         => __('Copied!', 'ecf-framework'),
                'color_generator_copy' => __('ecf_color_generator_copy', 'ecf-framework'),
                'color_generator_copied' => __('ecf_color_generator_copied', 'ecf-framework'),
                'color_generator_generate_shades' => __('ecf_color_generator_generate_shades', 'ecf-framework'),
                'color_generator_generate_tints' => __('ecf_color_generator_generate_tints', 'ecf-framework'),
                'edit'           => __('Edit', 'ecf-framework'),
                'delete'         => __('Delete', 'ecf-framework'),
                'save'           => __('Save', 'ecf-framework'),
                'cancel'         => __('Cancel', 'ecf-framework'),
                'autosave_saving'  => __('Saving…', 'ecf-framework'),
                'autosave_saved'   => __('Settings saved automatically.', 'ecf-framework'),
                'autosave_unsaved' => __('Changes pending', 'ecf-framework'),
                'autosave_failed'  => __('Could not save settings automatically.', 'ecf-framework'),
                'network_error'    => __('Network error', 'ecf-framework'),
                'discard_confirm'        => __('Discard unsaved changes? Saved data will be kept.', 'ecf-framework'),
                'reset_defaults_btn'     => __('Reset to defaults', 'ecf-framework'),
                'reset_defaults_confirm' => __('Reset ALL settings to plugin defaults? This cannot be undone.', 'ecf-framework'),
                'reset_defaults_running' => __('Resetting…', 'ecf-framework'),
                'reset_modal_title'      => __('Reset to defaults', 'ecf-framework'),
                'reset_modal_desc'       => __('Choose which areas you want to reset to plugin defaults:', 'ecf-framework'),
                'reset_modal_confirm'    => __('Reset selected', 'ecf-framework'),
                'reset_modal_none'       => __('Please select at least one area.', 'ecf-framework'),
                'reset_section_colors'   => __('Colors', 'ecf-framework'),
                'reset_section_radius'   => __('Radius', 'ecf-framework'),
                'reset_section_typography' => __('Typography', 'ecf-framework'),
                'reset_section_spacing'  => __('Spacing', 'ecf-framework'),
                'reset_section_shadows'  => __('Shadows', 'ecf-framework'),
                'reset_section_general'  => __('General settings', 'ecf-framework'),
                'reset_section_classes'  => __('Utility classes', 'ecf-framework'),
                'autosave_invalid' => __('Please fix the highlighted field before saving.', 'ecf-framework'),
                'autosave_active' => __('Autosave: on', 'ecf-framework'),
                'autosave_off' => __('Autosave: off', 'ecf-framework'),
                'autosync_active' => __('Auto-Sync: on', 'ecf-framework'),
                'autosync_off' => __('Auto-Sync: off', 'ecf-framework'),
                'palette_applied'       => __('Palette applied.', 'ecf-framework'),
                'classes_added'         => __('Classes added', 'ecf-framework'),
                'min_steps_required'    => __('At least 2 steps required', 'ecf-framework'),
                'no_more_steps'         => __('No more steps available', 'ecf-framework'),
                'select_font_first'     => __('Select a font first', 'ecf-framework'),
                'close'                 => __('Close', 'ecf-framework'),
                'classic_view'          => __('Classic view', 'ecf-framework'),
                'classic_view_title'    => __('Switch to classic admin interface (Layrix v1)', 'ecf-framework'),
                'history_empty'         => __('No entries yet', 'ecf-framework'),
                'history_hint'          => __('Every time you save, a snapshot is created. You can restore up to 8 versions.', 'ecf-framework'),
                'selected'              => __('Selected', 'ecf-framework'),
                'select'                => __('Select', 'ecf-framework'),
                'restore'               => __('Restore', 'ecf-framework'),
                'wiz_title_welcome'     => __('Welcome to Layrix', 'ecf-framework'),
                'wiz_body_welcome'      => __('Layrix manages your design tokens centrally and syncs them to Elementor in one click. This short guide shows all areas.', 'ecf-framework'),
                'wiz_next_start'        => __('Get started', 'ecf-framework'),
                'wiz_title_colors'      => __('Colors & Radius', 'ecf-framework'),
                'wiz_body_colors'       => __('Define your brand colors and border radii. Apply a <strong>style preset</strong> to get started quickly.', 'ecf-framework'),
                'wiz_next'              => __('Next', 'ecf-framework'),
                'wiz_title_typo'        => __('Typography', 'ecf-framework'),
                'wiz_body_typo'         => __('Font families, sizes and the type scale. Set which fonts are used on the website.', 'ecf-framework'),
                'wiz_title_spacing'     => __('Spacing', 'ecf-framework'),
                'wiz_body_spacing'      => __('Define your spacing system — base distances and rhythm between elements. Layrix generates a complete scale.', 'ecf-framework'),
                'wiz_title_shadows'     => __('Shadows', 'ecf-framework'),
                'wiz_body_shadows'      => __('Create reusable shadow tokens. From subtle elevation effects to prominent drop shadows.', 'ecf-framework'),
                'wiz_title_vars'        => __('Variables', 'ecf-framework'),
                'wiz_body_vars'         => __('Custom CSS variables beyond Elementor — e.g. animation durations, z-index levels or other design values.', 'ecf-framework'),
                'wiz_title_classes'     => __('Classes', 'ecf-framework'),
                'wiz_body_classes'      => __('Global Elementor classes for recurring styles. Pick from the library or create custom classes available in every widget.', 'ecf-framework'),
                'wiz_next_sync'         => __('Next: Sync', 'ecf-framework'),
                'wiz_title_sync'        => __('Sync with Elementor', 'ecf-framework'),
                'wiz_body_sync'         => __('When all tokens are set up, click <strong>Sync with Elementor</strong>. All tokens will be available as CSS variables in the Elementor editor.', 'ecf-framework'),
                'wiz_next_done'         => __('Done', 'ecf-framework'),
                'wiz_title_ready'       => __('You\'re all set!', 'ecf-framework'),
                'wiz_body_ready'        => __('All areas explored, Elementor synced — you\'re good to go.', 'ecf-framework'),
                'harmony_sync_hint' => __('Auto-Sync is off. Enable it in Sync settings or sync manually.', 'ecf-framework'),
                'autosync_prompt_msg' => __('Auto-Sync is disabled! Enable now?', 'ecf-framework'),
                'autosync_enabled_success' => __('Auto-Sync enabled successfully.', 'ecf-framework'),
                'autosave_disabled' => __('Autosave is disabled. Use the save button to store changes.', 'ecf-framework'),
                'elementor_syncing' => __('Elementor sync is running…', 'ecf-framework'),
                'elementor_synced' => __('Elementor sync completed.', 'ecf-framework'),
                'elementor_sync_failed' => __('Elementor sync failed.', 'ecf-framework'),
                'size_value_required' => __('This field cannot be empty.', 'ecf-framework'),
                'size_value_positive' => __('Only values greater than 0 can be saved here.', 'ecf-framework'),
                'system_refreshed' => __('Elementor status refreshed.', 'ecf-framework'),
                'layout_saved' => __('Card layout saved.', 'ecf-framework'),
                'layout_failed' => __('Could not save card layout.', 'ecf-framework'),
                'layout_reset' => __('Layout reset.', 'ecf-framework'),
                'layout_reset_failed' => __('Could not reset saved layout.', 'ecf-framework'),
                'layout_reset_confirm' => __('Reset the saved admin layout to the default view?', 'ecf-framework'),
                'layout_columns_label' => __('Columns', 'ecf-framework'),
                'topbar_colors_radius' => __('Colors & Radius', 'ecf-framework'),
                'topbar_typography' => __('Typography', 'ecf-framework'),
                'topbar_spacing' => __('Spacing', 'ecf-framework'),
                'topbar_shadows' => __('Shadows', 'ecf-framework'),
                'topbar_elementor_variables' => __('Elementor Variables', 'ecf-framework'),
                'topbar_elementor_classes' => __('Elementor Classes', 'ecf-framework'),
                'topbar_sync_export' => __('Sync & Export', 'ecf-framework'),
                'topbar_general_settings' => __('Base Settings', 'ecf-framework'),
                'start_banner_storage_key' => 'ecfStartBannerDismissed',
                'topbar_help_support' => __('Help & Support', 'ecf-framework'),
                'font_import_missing' => __('Please choose a font from the library list first.', 'ecf-framework'),
                'font_import_running' => __('Font is being imported locally…', 'ecf-framework'),
                'font_import_success' => __('Font imported locally and activated.', 'ecf-framework'),
                'font_import_failed' => __('The font could not be imported locally.', 'ecf-framework'),
                'font_pairing_running' => __('Applying the font pairing…', 'ecf-framework'),
                'font_pairing_success' => __('Font pairing applied.', 'ecf-framework'),
                'font_pairing_failed' => __('The font pairing could not be applied.', 'ecf-framework'),
                'style_preset_running' => __('Applying the style preset…', 'ecf-framework'),
                'style_preset_success' => __('Style preset applied.', 'ecf-framework'),
                'style_preset_failed' => __('The style preset could not be applied.', 'ecf-framework'),
                'smart_recommendation_running' => __('Applying the recommendation…', 'ecf-framework'),
                'smart_recommendation_success' => __('Recommendation applied.', 'ecf-framework'),
                'smart_recommendation_failed' => __('The recommendation could not be applied.', 'ecf-framework'),
                'font_search_running' => __('Searching the font library…', 'ecf-framework'),
                'font_search_empty' => __('No matching fonts found.', 'ecf-framework'),
                'font_search_error' => __('The font library search could not be loaded right now.', 'ecf-framework'),
                'font_option_primary' => __('Primary', 'ecf-framework'),
                'font_option_secondary' => __('Secondary', 'ecf-framework'),
                'font_option_uploaded' => __('Uploaded font', 'ecf-framework'),
                'font_option_custom_stack' => __('Custom stack', 'ecf-framework'),
                'font_search_placeholder' => __('Search local and Google fonts', 'ecf-framework'),
                'current_prefix' => __('Current:', 'ecf-framework'),
                'font_size_prefix' => __('Font size:', 'ecf-framework'),
                'yes' => __('Yes', 'ecf-framework'),
                'no' => __('No', 'ecf-framework'),
                'version' => __('Version %s', 'ecf-framework'),
                'source_limits' => __('Source: classes via %1$s, variables via %2$s', 'ecf-framework'),
                'limit_summary' => __('%1$s classes / %2$s variables', 'ecf-framework'),
                'enabled' => __('Enabled', 'ecf-framework'),
                'disabled' => __('Disabled', 'ecf-framework'),
                'import_preview_title' => __('Import preview', 'ecf-framework'),
                'import_preview_file' => __('File: %s', 'ecf-framework'),
                'import_preview_version' => __('Exported from plugin version: %s', 'ecf-framework'),
                'import_preview_date' => __('Exported at: %s', 'ecf-framework'),
                'import_preview_schema' => __('Schema version: %s', 'ecf-framework'),
                'import_preview_sections' => __('Detected settings groups: %s', 'ecf-framework'),
                'import_preview_legacy' => __('Legacy export without metadata. Import is still possible.', 'ecf-framework'),
                'import_preview_incompatible' => __('This file was exported from another plugin version. Please review General Settings, Sync, and editor-related options after import.', 'ecf-framework'),
                'import_preview_invalid' => __('The selected file is not a valid ECF JSON export.', 'ecf-framework'),
                'search_delete_confirm' => __('Do you really want to delete "%s"?', 'ecf-framework'),
                'search_edit_generated' => __('This ECF variable is generated from the framework settings. Please change it in the matching ECF section instead.', 'ecf-framework'),
                'search_edit_class'     => __('Global Classes should be managed in Elementor or through the ECF sync, not directly in the search results.', 'ecf-framework'),
                'search_updated'        => __('Variable updated.', 'ecf-framework'),
                'search_deleted'        => __('Entry deleted.', 'ecf-framework'),
                'class_in_use'          => __('In use', 'ecf-framework'),
                'class_not_in_use'      => __('Not in use', 'ecf-framework'),
                'class_usage_count'     => __('Used on %d Elementor documents', 'ecf-framework'),
                'class_delete_used_confirm' => __("These classes are still in use on Elementor elements:\n%s\n\nDo you really want to delete them?", 'ecf-framework'),
                'class_delete_used_error' => __('One or more selected classes are still used on Elementor elements.', 'ecf-framework'),
                'class_delete_modal_title' => __('Used classes', 'ecf-framework'),
                'class_delete_modal_subtitle' => __('Some selected classes are still used on Elementor elements.', 'ecf-framework'),
                'class_delete_modal_message' => __('Choose whether you want to delete all selected classes anyway or only the ones that are not currently used.', 'ecf-framework'),
                'class_delete_unused_only' => __('Delete unused only', 'ecf-framework'),
                'class_delete_all_anyway' => __('Delete all anyway', 'ecf-framework'),
                'class_delete_none_unused' => __('All selected classes are currently in use.', 'ecf-framework'),
                'class_sync_prompt_title' => __('Sync to Elementor?', 'ecf-framework'),
                'class_sync_prompt_subtitle' => __('Your changes were saved automatically.', 'ecf-framework'),
                'class_sync_prompt_message' => __('Some updated Layrix data is not yet available in Elementor. Do you want to sync it now?', 'ecf-framework'),
                'class_sync_prompt_yes' => __('Yes, sync now', 'ecf-framework'),
                'class_sync_prompt_no' => __('No, maybe later', 'ecf-framework'),
                'elementor_sync_prompt_variables_subtitle' => __('Your variable changes were saved automatically.', 'ecf-framework'),
                'elementor_sync_prompt_variables_message' => __('Your updated Layrix variables are not yet available in Elementor. Do you want to sync them now?', 'ecf-framework'),
                'elementor_sync_prompt_classes_subtitle' => __('Your class changes were saved automatically.', 'ecf-framework'),
                'elementor_sync_prompt_classes_message' => __('Your updated Layrix classes are not yet available in Elementor. Do you want to sync them now?', 'ecf-framework'),
                'elementor_sync_prompt_both_subtitle' => __('Your variable and class changes were saved automatically.', 'ecf-framework'),
                'elementor_sync_prompt_both_message' => __('Your updated Layrix variables and classes are not yet available in Elementor. Do you want to sync them now?', 'ecf-framework'),
                'active_class_hint_default' => __('These lists show which selected Layrix classes are currently ready for sync and which classes only remain in Elementor.', 'ecf-framework'),
                'active_class_hint_helper' => __('These lists include the automatic helper class ecf-container-boxed because a boxed Elementor width is currently active.', 'ecf-framework'),
                'body_size_warn_large_unit' => __('This value looks unusually large for rem/em body text. Did you mean px or the value from your active type scale token?', 'ecf-framework'),
                'body_size_warn_unusual' => __('This body text size looks unusual for normal reading text. Please double-check the unit and value.', 'ecf-framework'),
                'loading_elementor_classes' => __('Loading Elementor classes…', 'ecf-framework'),
                'existing_foreign_legacy_heading' => __('Old Layrix classes', 'ecf-framework'),
                'existing_foreign_legacy_hint' => __('These look like older Layrix or synced framework classes that still exist in Elementor.', 'ecf-framework'),
                'existing_foreign_manual_heading' => __('Manual or foreign Elementor classes', 'ecf-framework'),
                'existing_foreign_manual_hint' => __('These classes exist in Elementor, but do not look like current Layrix classes.', 'ecf-framework'),
                'local_font_name_placeholder' => __('primary-regular', 'ecf-framework'),
                'local_font_name_required'    => __('Please enter a font name.', 'ecf-framework'),
                'rest_unavailable'            => __('REST API not available', 'ecf-framework'),
                'bem_copied'                  => __('BEM copied', 'ecf-framework'),
                'bem_fill_block'              => __('← Fill block', 'ecf-framework'),
                'local_font_family_placeholder' => __('Primary', 'ecf-framework'),
                'local_font_upload_placeholder' => __('Select a local upload', 'ecf-framework'),
                'font_style_normal' => __('normal', 'ecf-framework'),
                'font_style_italic' => __('italic', 'ecf-framework'),
                'font_style_oblique' => __('oblique', 'ecf-framework'),
                'font_display_swap' => __('swap', 'ecf-framework'),
                'font_display_fallback' => __('fallback', 'ecf-framework'),
                'font_display_optional' => __('optional', 'ecf-framework'),
                'font_display_block' => __('block', 'ecf-framework'),
                'font_display_auto' => __('auto', 'ecf-framework'),
                'font_group_local' => __('Local fonts', 'ecf-framework'),
                'font_group_core' => __('Core font tokens', 'ecf-framework'),
                'font_group_popular'   => __('Popular', 'ecf-framework'),
                'font_group_favorites' => __('My Favorites', 'ecf-framework'),
                'font_fav_add'         => __('Add to favorites', 'ecf-framework'),
                'font_fav_remove'      => __('Remove from favorites', 'ecf-framework'),
                'font_group_library' => __('Google Fonts library', 'ecf-framework'),
                'clamp_label' => __('Clamp', 'ecf-framework'),
                'search_type_help_color' => __('Color is for values like #3b82f6, rgb(...) or hsl(...).', 'ecf-framework'),
                'search_type_help_size' => __('Size is for values like 24px, 1.5rem or clamp(...). Tokens like text-2xl also belong here.', 'ecf-framework'),
                'search_type_help_string' => __('Text is only meant for real strings, not for text sizes like text-2xl.', 'ecf-framework'),
                'search_label_invalid' => __('Please use a valid variable label. Spaces are converted to hyphens automatically.', 'ecf-framework'),
                'search_clamp_number_error' => __('Please enter minimum and maximum as numbers.', 'ecf-framework'),
                'group_ecf_variables' => __('Layrix Variablen', 'ecf-framework'),
                'group_foreign_variables' => __('Foreign Variables', 'ecf-framework'),
                'group_ecf_classes' => __('ECF Classes', 'ecf-framework'),
                'group_foreign_classes' => __('Foreign Classes', 'ecf-framework'),
                'preview_min' => __('Min', 'ecf-framework'),
                'preview_max' => __('Max', 'ecf-framework'),
                'search_preview_text' => __('Text', 'ecf-framework'),
                'search_preview_class' => __('Class', 'ecf-framework'),
                'search_preview_text_sample' => __('Text', 'ecf-framework'),
                'bem_preview_empty_custom' => __('Enter a custom block name and the preview will appear here.', 'ecf-framework'),
                'bem_preview_empty_preset' => __('Choose an area and the preview will appear here.', 'ecf-framework'),
                'bem_feedback_added_one' => __('%d class added.', 'ecf-framework'),
                'bem_feedback_added_many' => __('%d classes added.', 'ecf-framework'),
                'bem_feedback_exists' => __('All generated classes already exist.', 'ecf-framework'),
                'fontPreviewConsent'       => __('Font previews require a connection to Google Fonts.', 'ecf-framework'),
                'fontPreviewEnable'        => __('Enable previews', 'ecf-framework'),
                'local_font_delete_confirm' => __('Delete font "%s"? This removes it from local fonts.', 'ecf-framework'),
                'font_upload_success'      => __('Font added successfully.', 'ecf-framework'),
                'font_role_primary'        => __('primary font', 'ecf-framework'),
                'font_role_secondary'      => __('secondary font', 'ecf-framework'),
                'font_saved_as'            => __('saved as', 'ecf-framework'),
                'font_saved_suffix'        => __('', 'ecf-framework'),
                /* Sync */
                'syncing'                        => __('Syncing…', 'ecf-framework'),
                'sync_not_available'             => __('Sync endpoint not available.', 'ecf-framework'),
                'sync_network_error'             => __('Network error during sync.', 'ecf-framework'),
                'sync_variables'                 => __('%d variables', 'ecf-framework'),
                'sync_classes_count'             => __('%d classes', 'ecf-framework'),
                'sync_label'                     => __('Sync:', 'ecf-framework'),
                /* Preset apply */
                'preset_saved_syncing'           => __('Saved — syncing…', 'ecf-framework'),
                'preset_save_error'              => __('Could not save settings.', 'ecf-framework'),
                'preset_apply_btn'               => __('Apply preset', 'ecf-framework'),
                'preset_apply_confirm'           => __('Apply preset? All current design settings will be overwritten.', 'ecf-framework'),
                'preset_color_conflict'         => __('Skipped — name already used by an unsaved row: %s', 'ecf-framework'),
                'info_heading_font'             => __('Heading font', 'ecf-framework'),
                'info_body_font'                => __('Body font', 'ecf-framework'),
                'show_details'                  => __('Show details', 'ecf-framework'),
                'preset_applying'                => __('Applying…', 'ecf-framework'),
                /* Preset modal */
                'pm_title'                       => __('Preset anwenden', 'ecf-framework'),
                'pm_hint'                        => __('Wähle aus, was du übernehmen möchtest:', 'ecf-framework'),
                'pm_apply'                       => __('Anwenden', 'ecf-framework'),
                'pm_cancel'                      => __('Abbrechen', 'ecf-framework'),
                'pm_colors'                      => __('Farben', 'ecf-framework'),
                'pm_heading_font'                => __('Überschrift-Schrift', 'ecf-framework'),
                'pm_body_font'                   => __('Fließtext-Schrift', 'ecf-framework'),
                'pm_text_size'                   => __('Fließtext-Größe', 'ecf-framework'),
                'pm_base_colors'                 => __('Basis-Farben', 'ecf-framework'),
                'pm_base_colors_detail'          => __('Hintergrund, Text, Links', 'ecf-framework'),
                'pm_site_width'                  => __('Website-Breite', 'ecf-framework'),
                'pm_radius'                      => __('Eckenradien', 'ecf-framework'),
                'pm_shadows'                     => __('Schatten', 'ecf-framework'),
                'pm_spacing'                     => __('Abstände', 'ecf-framework'),
                'pm_spacing_detail'              => __('Fluid-Abstands-Skala', 'ecf-framework'),
                'pm_tokens'                      => __('Token', 'ecf-framework'),
                'token_name_invalid_chars'       => __('Nur a-z, 0-9, - und _ erlaubt. Sonderzeichen werden beim Speichern entfernt.', 'ecf-framework'),
                'token_name_duplicate'           => __('Dieser Name existiert bereits — bitte einen anderen wählen.', 'ecf-framework'),
                'confirm_ok'                     => __('OK', 'ecf-framework'),
                'confirm_cancel'                 => __('Abbrechen', 'ecf-framework'),
                'last_saved_at'                  => __('Gespeichert', 'ecf-framework'),
                /* Custom presets */
                'custom_preset_label'            => __('Custom preset', 'ecf-framework'),
                'custom_preset_category'         => __('Custom', 'ecf-framework'),
                'custom_preset_save_prompt'      => __('Name for the preset (max. 60 characters):', 'ecf-framework'),
                'custom_preset_saved'            => __('Preset saved.', 'ecf-framework'),
                'custom_preset_save_error'       => __('Could not save preset.', 'ecf-framework'),
                'custom_preset_save_network_error' => __('Network error while saving preset.', 'ecf-framework'),
                'custom_preset_save_btn_reset'   => __('+ Save current state', 'ecf-framework'),
                'custom_preset_delete_confirm'   => __('Delete preset?', 'ecf-framework'),
                'custom_preset_deleted'          => __('Preset deleted.', 'ecf-framework'),
                'custom_preset_delete_error'     => __('Could not delete preset.', 'ecf-framework'),
                'custom_preset_network_error'    => __('Network error.', 'ecf-framework'),
                /* Import */
                'import_saving'                  => __('Importing…', 'ecf-framework'),
                'import_saved_syncing'           => __('Import saved — syncing…', 'ecf-framework'),
                'import_failed'                  => __('Import failed.', 'ecf-framework'),
                'import_completed'               => __('Import completed', 'ecf-framework'),
                'import_synced'                  => __('%s synchronized', 'ecf-framework'),
                'import_reloading'               => __('Page is reloading', 'ecf-framework'),
                'import_network_error'           => __('Network error during import.', 'ecf-framework'),
                'import_no_rest'                 => __('REST API not available.', 'ecf-framework'),
                'import_json_loaded'             => __('JSON loaded', 'ecf-framework'),
                'import_invalid_json'            => __('Invalid JSON file.', 'ecf-framework'),
                'import_colors_count'            => __('%d colors', 'ecf-framework'),
                'import_radius_count'            => __('%d radius tokens', 'ecf-framework'),
                'import_shadows_count'           => __('%d shadows', 'ecf-framework'),
                /* File / section chooser */
                'choose_file'                    => __('Choose file', 'ecf-framework'),
                'select_sections'                => __('Select sections →', 'ecf-framework'),
                /* Color row editor */
                'generate_shades'                => __('Generate shades', 'ecf-framework'),
                'generate_tints'                 => __('Generate tints', 'ecf-framework'),
                'apply'                          => __('Apply', 'ecf-framework'),
                'remove'                         => __('Remove', 'ecf-framework'),
                'close'                          => __('Close', 'ecf-framework'),
                'copy_css'                       => __('Copy CSS', 'ecf-framework'),
                'import_modal_btn'               => __('Import', 'ecf-framework'),
                'class_limit_warn_title'         => __('Elementor class limit reached', 'ecf-framework'),
                'class_limit_warn_body'          => __('You are using %1$s of %2$s available Elementor Global Classes. Remove unused classes to avoid sync issues.', 'ecf-framework'),
                'class_limit_warn_ok'            => __('OK, understood', 'ecf-framework'),
            ],
            'pluginVersion' => $this->current_plugin_version(),
            'fontFavorites' => array_values($settings['typography']['font_favorites'] ?? []),
            'localFonts'   => array_values($settings['typography']['local_fonts'] ?? []),
            'classesTotal'     => (int) ($this->get_elementor_limit_snapshot()['classes_total'] ?? 0),
            'classesLimit'     => $this->get_native_global_class_limit(),
            'colorLabels'      => [
                'primary'   => __( 'Primary',   'ecf-framework' ),
                'secondary' => __( 'Secondary', 'ecf-framework' ),
                'accent'    => __( 'Accent',    'ecf-framework' ),
                'surface'   => __( 'Surface',   'ecf-framework' ),
                'text'      => __( 'Text',      'ecf-framework' ),
            ],
            'mediaUploadUrl'   => admin_url('async-upload.php'),
            'mediaUploadNonce' => wp_create_nonce('media-form'),
            'spacingPreview' => $preview_maps['spacingPreview'],
            'typePreview' => $preview_maps['typePreview'],
            'radiusPreview' => $preview_maps['radiusPreview'],
            'typePreviewTexts' => $this->load_type_preview_texts($settings),
            'layoutOrders' => $this->get_user_layout_orders(),
            'layoutColumns' => $this->get_user_layout_columns(),
            'adminDesign' => [
                'preset' => $this->selected_admin_design_preset($settings),
                'mode' => $this->selected_admin_design_mode($settings),
            ],
            'fontLibrary' => array_map(function($entry) {
                return [
                    'family' => (string) ($entry['family'] ?? ''),
                ];
            }, $this->font_library_catalog()),
        ]);
    }

    public function editor_assets() {
        $editor_css_ver = $this->asset_version('assets/editor.css', '0.1.0');
        $editor_js_ver  = $this->asset_version('assets/editor.js', '0.1.0');
        $settings = $this->get_settings();
        $preview_maps = $this->build_editor_preview_maps($settings);

        wp_enqueue_style('ecf-editor', plugins_url('assets/editor.css', ECF_FRAMEWORK_FILE), [], $editor_css_ver);
        wp_enqueue_script('ecf-editor', plugins_url('assets/editor.js', ECF_FRAMEWORK_FILE), ['jquery'], $editor_js_ver, true);

        $atomic_section_js_ver = $this->asset_version('assets/atomic-section-editor.js', '0.1.0');
        wp_enqueue_script(
            'ecf-atomic-section-editor',
            plugins_url('assets/atomic-section-editor.js', ECF_FRAMEWORK_FILE),
            ['elementor-editor'],
            $atomic_section_js_ver,
            true
        );

        // Class IDs for auto-classes feature. Layrix syncs starter classes to
        // Elementor's Global Classes registry with deterministic IDs of the
        // form 'g-ecf-' . substr(md5(label), 0, 10). The editor's Klassen chip
        // UI displays classes by ID, so we hand the JS the IDs directly.
        $cls_id = static function ($label) {
            return 'g-ecf-' . substr(md5($label), 0, 10);
        };
        $auto_default_on = function ($key) use ($settings) {
            return !array_key_exists($key, $settings) || !empty($settings[$key]);
        };
        // Read Elementor-synced variable IDs (label → e-gv-xxx). Used by the
        // editor JS to pre-fill heading typography with Layrix variable refs.
        $variable_ids = [];
        if (
            class_exists('\Elementor\Plugin')
            && class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')
        ) {
            try {
                $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
                if ($kit) {
                    $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
                    $collection = $repo->load();
                    foreach ($collection->all() as $var_id => $variable) {
                        $label = method_exists($variable, 'label') ? (string) $variable->label() : '';
                        if ($label !== '') {
                            $variable_ids[$label] = (string) $var_id;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Silently fall back to no pre-fill if variable repo is unavailable.
            }
        }
        wp_localize_script('ecf-atomic-section-editor', 'ecfAutoClasses', [
            'masterEnabled'      => !empty($settings['auto_classes_enabled']),
            'headingsEnabled'    => $auto_default_on('auto_classes_headings'),
            'buttonsEnabled'     => $auto_default_on('auto_classes_buttons'),
            'headingClassIds'    => [
                'h1' => $cls_id('ecf-heading-1'),
                'h2' => $cls_id('ecf-heading-2'),
                'h3' => $cls_id('ecf-heading-3'),
                'h4' => $cls_id('ecf-heading-4'),
                'h5' => $cls_id('ecf-heading-5'),
                'h6' => $cls_id('ecf-heading-5'),
            ],
            'buttonClassId'      => $cls_id('ecf-button'),
            'layrixSectionClassId' => $cls_id('ecf-layrix-section'),
            'variableIds'      => $variable_ids,
            'headingTypography' => [
                'h1' => [ 'size' => 'ecf-text-4xl', 'leading' => 'ecf-leading-tight' ],
                'h2' => [ 'size' => 'ecf-text-3xl', 'leading' => 'ecf-leading-tight' ],
                'h3' => [ 'size' => 'ecf-text-2xl', 'leading' => 'ecf-leading-snug' ],
                'h4' => [ 'size' => 'ecf-text-xl',  'leading' => 'ecf-leading-snug' ],
                'h5' => [ 'size' => 'ecf-text-l',   'leading' => 'ecf-leading-normal' ],
                'h6' => [ 'size' => 'ecf-text-l',   'leading' => 'ecf-leading-normal' ],
            ],
        ]);
        wp_localize_script('ecf-editor', 'ecfEditor', [
            'variableTypeFilterEnabled' => !empty($settings['elementor_variable_type_filter']),
            'variableTypeFilterScopes' => [
                'color'  => !empty($settings['elementor_variable_type_filter_scopes']['color']),
                'text'   => !empty($settings['elementor_variable_type_filter_scopes']['text']),
                'space'  => !empty($settings['elementor_variable_type_filter_scopes']['space']),
                'radius' => !empty($settings['elementor_variable_type_filter_scopes']['radius']),
                'shadow' => !empty($settings['elementor_variable_type_filter_scopes']['shadow']),
                'string' => !empty($settings['elementor_variable_type_filter_scopes']['string']),
            ],
            'spacingPreview' => $preview_maps['spacingPreview'],
            'typePreview'    => $preview_maps['typePreview'],
            'radiusPreview'  => $preview_maps['radiusPreview'],
        ]);
    }
}
