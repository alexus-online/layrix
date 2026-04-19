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
        ];
        $admin_js_ver  = $this->asset_version('assets/admin.js', '0.1.5');

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('ecf-admin-base',       plugins_url('assets/admin-base.css',       ECF_FRAMEWORK_FILE), [],                $admin_css_ver['base']);
        wp_enqueue_style('ecf-admin-layout',     plugins_url('assets/admin-layout.css',     ECF_FRAMEWORK_FILE), ['ecf-admin-base'],     $admin_css_ver['layout']);
        wp_enqueue_style('ecf-admin-forms',      plugins_url('assets/admin-forms.css',      ECF_FRAMEWORK_FILE), ['ecf-admin-layout'],   $admin_css_ver['forms']);
        wp_enqueue_style('ecf-admin-panels',     plugins_url('assets/admin-panels.css',     ECF_FRAMEWORK_FILE), ['ecf-admin-forms'],    $admin_css_ver['panels']);
        wp_enqueue_style('ecf-admin-ui',         plugins_url('assets/admin-ui.css',         ECF_FRAMEWORK_FILE), ['ecf-admin-panels'],   $admin_css_ver['ui']);
        wp_enqueue_style('ecf-admin-responsive', plugins_url('assets/admin-responsive.css', ECF_FRAMEWORK_FILE), ['ecf-admin-ui'],       $admin_css_ver['responsive']);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('ecf-admin', plugins_url('assets/admin.js', ECF_FRAMEWORK_FILE), ['jquery', 'jquery-ui-sortable', 'wp-color-picker'], $admin_js_ver, false);
        wp_localize_script('ecf-admin', 'ecfAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_variables'),
            'restUrl' => esc_url_raw(rest_url('ecf-framework/v1/settings')),
            'syncRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/sync')),
            'fontImportRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/fonts/import')),
            'fontSearchRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/fonts/search')),
            'layoutRestUrl' => esc_url_raw(rest_url('ecf-framework/v1/layout')),
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
                'autosave_saving'=> __('Saving…', 'ecf-framework'),
                'autosave_saved' => __('Settings saved automatically.', 'ecf-framework'),
                'autosave_failed'=> __('Could not save settings automatically.', 'ecf-framework'),
                'autosave_invalid' => __('Please fix the highlighted field before saving.', 'ecf-framework'),
                'autosave_active' => __('Autosave: on', 'ecf-framework'),
                'autosave_off' => __('Autosave: off', 'ecf-framework'),
                'autosync_active' => __('Auto-Sync: on', 'ecf-framework'),
                'autosync_off' => __('Auto-Sync: off', 'ecf-framework'),
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
            ],
            'pluginVersion' => $this->current_plugin_version(),
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
