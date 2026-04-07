<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Asset_Loading_Trait {
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
        wp_enqueue_script('ecf-admin', plugins_url('assets/admin.js', ECF_FRAMEWORK_FILE), ['jquery', 'wp-color-picker'], $admin_js_ver, true);
        wp_localize_script('ecf-admin', 'ecfAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ecf_variables'),
            'restUrl' => esc_url_raw(rest_url('ecf-framework/v1/settings')),
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
                'system_refreshed' => __('Elementor status refreshed.', 'ecf-framework'),
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
            ],
            'pluginVersion' => $this->current_plugin_version(),
            'spacingPreview' => $preview_maps['spacingPreview'],
            'typePreview' => $preview_maps['typePreview'],
            'radiusPreview' => $preview_maps['radiusPreview'],
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
