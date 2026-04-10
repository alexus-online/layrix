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
        return file_exists($full_path) ? filemtime($full_path) : $fallback;
    }

    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_ecf-framework') {
            return;
        }

        wp_enqueue_media();

        $settings = $this->get_settings();
        $preview_maps = $this->build_editor_preview_maps($settings);
        $admin_css_ver = $this->asset_version('assets/admin.css', '0.1.5');
        $admin_js_ver  = $this->asset_version('assets/admin.js', '0.1.5');

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('ecf-admin', plugins_url('assets/admin.css', ECF_FRAMEWORK_FILE), [], $admin_css_ver);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('ecf-admin', plugins_url('assets/admin.js', ECF_FRAMEWORK_FILE), ['jquery', 'jquery-ui-sortable', 'wp-color-picker'], $admin_js_ver, true);
        wp_localize_script('ecf-admin', 'ecfAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_variables'),
            'restUrl' => esc_url_raw(rest_url('ecf-framework/v1/settings')),
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
                'col_type'       => __('Type', 'ecf-framework'),
                'col_value'      => __('Value', 'ecf-framework'),
                'choose_font'    => __('Choose font file', 'ecf-framework'),
                'use_font'       => __('Use this file', 'ecf-framework'),
                'select_file'    => __('Select file', 'ecf-framework'),
                'copy'           => __('Copy', 'ecf-framework'),
                'copied'         => __('Copied!', 'ecf-framework'),
                'edit'           => __('Edit', 'ecf-framework'),
                'delete'         => __('Delete', 'ecf-framework'),
                'save'           => __('Save', 'ecf-framework'),
                'cancel'         => __('Cancel', 'ecf-framework'),
                'autosave_saving'=> __('Saving…', 'ecf-framework'),
                'autosave_saved' => __('Settings saved automatically.', 'ecf-framework'),
                'autosave_failed'=> __('Could not save settings automatically.', 'ecf-framework'),
                'autosave_invalid' => __('Please fix the highlighted field before saving.', 'ecf-framework'),
                'size_value_required' => __('This field cannot be empty.', 'ecf-framework'),
                'size_value_positive' => __('Only values greater than 0 can be saved here.', 'ecf-framework'),
                'system_refreshed' => __('Elementor status refreshed.', 'ecf-framework'),
                'layout_saved' => __('Card layout saved.', 'ecf-framework'),
                'layout_failed' => __('Could not save card layout.', 'ecf-framework'),
                'layout_columns_label' => __('Columns', 'ecf-framework'),
                'topbar_colors_radius' => __('Colors & Radius', 'ecf-framework'),
                'topbar_typography' => __('Typography', 'ecf-framework'),
                'topbar_spacing' => __('Spacing', 'ecf-framework'),
                'topbar_shadows' => __('Shadows', 'ecf-framework'),
                'topbar_elementor_variables' => __('Elementor Variables', 'ecf-framework'),
                'topbar_elementor_classes' => __('Elementor Classes', 'ecf-framework'),
                'topbar_sync_export' => __('Sync & Export', 'ecf-framework'),
                'topbar_general_settings' => __('General Settings', 'ecf-framework'),
                'topbar_help_support' => __('Help & Support', 'ecf-framework'),
                'font_import_missing' => __('Please choose a font from the library list first.', 'ecf-framework'),
                'font_import_running' => __('Font is being imported locally…', 'ecf-framework'),
                'font_import_success' => __('Font imported locally and activated.', 'ecf-framework'),
                'font_import_failed' => __('The font could not be imported locally.', 'ecf-framework'),
                'font_search_running' => __('Searching the font library…', 'ecf-framework'),
                'font_search_empty' => __('No matching fonts found.', 'ecf-framework'),
                'font_search_error' => __('The font library search could not be loaded right now.', 'ecf-framework'),
                'font_option_primary' => __('Primary', 'ecf-framework'),
                'font_option_secondary' => __('Secondary', 'ecf-framework'),
                'font_option_mono' => __('Mono', 'ecf-framework'),
                'font_option_uploaded' => __('Uploaded font', 'ecf-framework'),
                'font_option_custom_stack' => __('Custom stack', 'ecf-framework'),
                'font_search_placeholder' => __('Search local and Google fonts', 'ecf-framework'),
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
                'body_size_warn_large_unit' => __('This value looks unusually large for rem/em body text. Did you mean px or the value from your active type scale token?', 'ecf-framework'),
                'body_size_warn_unusual' => __('This body text size looks unusual for normal reading text. Please double-check the unit and value.', 'ecf-framework'),
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
