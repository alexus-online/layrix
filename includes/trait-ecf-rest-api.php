<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_REST_API_Trait {
    private function rest_admin_meta() {
        $settings = $this->get_settings();
        return [
            'elementor_limit_snapshot' => $this->get_elementor_limit_snapshot(),
            'elementor_debug_snapshot' => $this->get_elementor_debug_snapshot(),
            'layrix_variable_count' => count($this->build_native_variable_payloads($settings)),
            'layout_orders' => $this->get_user_layout_orders(),
            'layout_columns' => $this->get_user_layout_columns(),
        ];
    }

    public function register_rest_routes() {
        register_rest_route('ecf-framework/v1', '/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_settings'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'rest_update_settings'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
        ]);

        register_rest_route('ecf-framework/v1', '/sync', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_sync_native'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        // Owner-only "Ideen" notes — Application Password authenticated.
        // Permission self-checks via is_layrix_owner() (email match), so even
        // a valid app-password from another user won't pass.
        register_rest_route('ecf-framework/v1', '/owner-notes', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_owner_notes_list'],
                'permission_callback' => [$this, 'rest_owner_permission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_owner_note_create'],
                'permission_callback' => [$this, 'rest_owner_permission'],
            ],
        ]);
        register_rest_route('ecf-framework/v1', '/owner-notes/(?P<id>[A-Za-z0-9_-]+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'rest_owner_note_delete'],
            'permission_callback' => [$this, 'rest_owner_permission'],
        ]);
        register_rest_route('ecf-framework/v1', '/owner-notes/reorder', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_owner_notes_reorder'],
            'permission_callback' => [$this, 'rest_owner_permission'],
        ]);

        // Theme-Style-Importer — read kit + apply to Layrix settings.
        // Standard manage_options gate (any admin can use this, not owner-only).
        register_rest_route('ecf-framework/v1', '/kit-import-preview', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_kit_import_preview'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);
        register_rest_route('ecf-framework/v1', '/kit-import', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_kit_import_apply'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route('ecf-framework/v1', '/layout', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_layout_orders'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'rest_update_layout_orders'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
        ]);

        register_rest_route('ecf-framework/v1', '/fonts/import', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_import_font_library_font'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route('ecf-framework/v1', '/fonts/search', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_search_font_library_fonts'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route('ecf-framework/v1', '/elementor-values', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'rest_get_elementor_values'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route('ecf-framework/v1', '/custom-presets', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'rest_get_custom_presets'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_save_custom_preset'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
        ]);

        register_rest_route('ecf-framework/v1', '/custom-presets/(?P<id>[a-zA-Z0-9_-]+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'rest_delete_custom_preset'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route('ecf-framework/v1', '/reset-defaults', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'rest_reset_defaults'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

    }

    public function rest_manage_options_permission() {
        return $this->can_manage_framework();
    }

    public function rest_get_settings(\WP_REST_Request $request) {
        return rest_ensure_response([
            'success'  => true,
            'settings' => $this->get_settings(),
            'meta' => $this->rest_admin_meta(),
        ]);
    }

    public function rest_update_settings(\WP_REST_Request $request) {
        $data = $request->get_json_params();

        if (!is_array($data)) {
            return $this->rest_error(
                'ecf_invalid_payload',
                __('Invalid JSON payload.', 'ecf-framework'),
                400
            );
        }

        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : $data;
        $sanitized = $this->sanitize_settings($settings);
        update_option($this->option_name, $sanitized);
        $this->settings_cache = $sanitized;
        $this->clear_css_cache();
        if (method_exists($this, 'clear_elementor_sync_caches')) {
            $this->clear_elementor_sync_caches();
        }

        $auto_sync = !empty($sanitized['elementor_auto_sync_enabled']);
        if ($auto_sync && method_exists($this, 'sync_native_variables_merge')) {
            $plugin_ref = $this;
            $sync_classes = !empty($sanitized['elementor_auto_sync_classes']);
            add_action('shutdown', static function () use ($plugin_ref, $sync_classes) {
                // Flush response to client first so the save feels instant
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                try {
                    $plugin_ref->sync_native_variables_merge();
                    if ($sync_classes && method_exists($plugin_ref, 'sync_native_classes_merge')) {
                        $plugin_ref->sync_native_classes_merge();
                    }
                } catch (\Throwable $e) {
                    error_log( 'ECF autosave sync failed: ' . $e->getMessage() );
                }
            }, 99);
        }

        return rest_ensure_response([
            'success'  => true,
            'message'  => __('Settings updated.', 'ecf-framework'),
            'settings' => $this->get_settings(),
            'meta' => $this->rest_admin_meta(),
        ]);
    }

    public function rest_get_elementor_values(\WP_REST_Request $request) {
        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            return rest_ensure_response(['success' => true, 'values' => [], 'available' => false]);
        }
        try {
            $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
            if (!$kit) {
                return rest_ensure_response(['success' => true, 'values' => [], 'available' => false]);
            }
            $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
            $collection = $repo->load();
            $values = [];
            foreach ($collection->all() as $variable) {
                if (!$variable->is_deleted()) {
                    $values[strtolower($variable->label())] = $variable->value();
                }
            }
            return rest_ensure_response(['success' => true, 'values' => $values, 'available' => true]);
        } catch (\Throwable $e) {
            error_log( 'ECF get_elementor_values failed: ' . $e->getMessage() );
            return rest_ensure_response(['success' => true, 'values' => [], 'available' => false]);
        }
    }

    public function rest_get_layout_orders(\WP_REST_Request $request) {
        return rest_ensure_response([
            'success' => true,
            'orders' => $this->get_user_layout_orders(),
            'columns' => $this->get_user_layout_columns(),
        ]);
    }

    public function rest_update_layout_orders(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        $orders = isset($data['orders']) && is_array($data['orders']) ? $data['orders'] : $data;
        $columns = isset($data['columns']) && is_array($data['columns']) ? $data['columns'] : [];

        if (!is_array($orders)) {
            return $this->rest_error(
                'ecf_invalid_layout_payload',
                __('Invalid layout payload.', 'ecf-framework'),
                400
            );
        }

        return rest_ensure_response([
            'success' => true,
            'orders' => $this->save_user_layout_orders($orders),
            'columns' => $this->save_user_layout_columns($columns),
            'message' => __('Layout order updated.', 'ecf-framework'),
        ]);
    }

    private function local_font_rows_for_family($settings, $family) {
        $rows = [];
        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            if (strcasecmp(trim((string) ($row['family'] ?? '')), $family) === 0) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function sideload_remote_font_to_media_library($remote_url, $family_slug) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp_file = download_url($remote_url, 30);
        if (is_wp_error($tmp_file)) {
            return $tmp_file;
        }

        $path = wp_parse_url($remote_url, PHP_URL_PATH);
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'woff2';
        }

        $file_array = [
            'name' => $family_slug . '-regular.' . $extension,
            'tmp_name' => $tmp_file,
        ];

        $attachment_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);
            return $attachment_id;
        }

        $url = wp_get_attachment_url($attachment_id);
        if (!$url) {
            return new \WP_Error('ecf_font_import_missing_url', __('Imported font could not be resolved from the media library.', 'ecf-framework'));
        }

        return [
            'attachment_id' => (int) $attachment_id,
            'url' => esc_url_raw($url),
        ];
    }

    private function fetch_google_font_css_url($family) {
        $query = rawurlencode((string) $family);
        $response = wp_remote_get('https://fonts.googleapis.com/css2?family=' . $query . '&display=swap', [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Layrix Font Import)',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = (string) wp_remote_retrieve_body($response);
        if ($body === '') {
            return new \WP_Error('ecf_font_import_empty_css', __('The font library returned an empty stylesheet.', 'ecf-framework'));
        }

        if (preg_match('/url\((https:[^)]+)\)\s*format\([\'"]woff2[\'"]\)/i', $body, $matches)) {
            return esc_url_raw($matches[1]);
        }

        if (preg_match('/url\((https:[^)]+)\)/i', $body, $matches)) {
            return esc_url_raw($matches[1]);
        }

        return new \WP_Error('ecf_font_import_css_parse', __('No downloadable font file could be found for this font.', 'ecf-framework'));
    }

    private function google_fonts_metadata_list() {
        $bundled_path = plugin_dir_path(ECF_FRAMEWORK_FILE) . 'assets/data/google-fonts.json';
        if (file_exists($bundled_path)) {
            $bundled = json_decode((string) file_get_contents($bundled_path), true);
            if (is_array($bundled) && !empty($bundled)) {
                return array_map(static function($family) {
                    return ['family' => (string) $family];
                }, array_values(array_filter($bundled, 'is_string')));
            }
        }

        $cache_key = 'ecf_google_fonts_metadata_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $response = wp_remote_get('https://fonts.google.com/metadata/fonts', [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Layrix Font Search)',
            ],
        ]);

        if (is_wp_error($response)) {
            return $this->font_library_catalog();
        }

        $body = trim((string) wp_remote_retrieve_body($response));
        if ($body === '') {
            return $this->font_library_catalog();
        }

        $body = preg_replace('/^\)\]\}\'\s*/', '', $body);
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || empty($decoded['familyMetadataList']) || !is_array($decoded['familyMetadataList'])) {
            return $this->font_library_catalog();
        }

        $families = [];
        foreach ($decoded['familyMetadataList'] as $entry) {
            $family = trim((string) ($entry['family'] ?? ''));
            if ($family === '') {
                continue;
            }
            $families[] = ['family' => $family];
        }

        if (empty($families)) {
            return $this->font_library_catalog();
        }

        usort($families, static function($a, $b) {
            return strcasecmp((string) ($a['family'] ?? ''), (string) ($b['family'] ?? ''));
        });

        set_transient($cache_key, $families, 12 * HOUR_IN_SECONDS);

        return $families;
    }

    private function search_font_library_groups($query, $settings, $limit = 60) {
        $query = trim((string) $query);
        $needle = function($value) use ($query) {
            if ($query === '') {
                return true;
            }
            // Single char or short query: prefix match (starts with)
            if (strlen($query) <= 2) {
                return stripos((string) $value, $query) === 0;
            }
            return stripos((string) $value, $query) !== false;
        };

        $groups = [];

        $local = [];
        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family === '' || !$needle($family)) {
                continue;
            }
            $local[] = [
                'value' => "'" . $family . "'",
                'label' => $family,
                'source' => 'local',
            ];
        }
        if (!empty($local)) {
            $groups[] = [
                'label' => __('Local fonts', 'ecf-framework'),
                'options' => $local,
            ];
        }

        $core = [];
        foreach ($this->grouped_font_family_field_options($settings) as $group) {
            foreach ((array) ($group['options'] ?? []) as $option) {
                if (($option['source'] ?? '') !== 'core') {
                    continue;
                }
                if ($needle($option['label'] ?? '')) {
                    $core[] = $option;
                }
            }
        }
        if (!empty($core)) {
            $groups[] = [
                'label' => __('Core font tokens', 'ecf-framework'),
                'options' => $core,
            ];
        }

        $library = [];
        foreach ($this->google_fonts_metadata_list() as $entry) {
            $family = trim((string) ($entry['family'] ?? ''));
            if ($family === '' || !$needle($family)) {
                continue;
            }
            $library[] = [
                'value' => '__library__|' . $family,
                'label' => $family,
                'source' => 'library',
            ];
            if (count($library) >= $limit) {
                break;
            }
        }
        if (!empty($library)) {
            $groups[] = [
                'label' => __('Google Fonts library', 'ecf-framework'),
                'options' => $library,
            ];
        }

        return $groups;
    }

    private function import_font_library_family($family, $settings) {
        $family = trim((string) $family);
        if ($family === '') {
            return new \WP_Error('ecf_font_import_missing_family', __('No font family was selected.', 'ecf-framework'));
        }

        $existing_rows = $this->local_font_rows_for_family($settings, $family);
        if (!empty($existing_rows)) {
            return $existing_rows[0];
        }

        $remote_url = $this->fetch_google_font_css_url($family);
        if (is_wp_error($remote_url)) {
            return $remote_url;
        }

        $family_slug = sanitize_title($family);
        $media_item = $this->sideload_remote_font_to_media_library($remote_url, $family_slug);
        if (is_wp_error($media_item)) {
            return $media_item;
        }

        return [
            'name' => sanitize_key($family_slug . '-regular'),
            'family' => $this->sanitize_css_font_stack($family),
            'src' => $media_item['url'],
            'weight' => '400',
            'style' => 'normal',
            'display' => 'swap',
        ];
    }

    public function rest_import_font_library_font(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        $family = sanitize_text_field($data['family'] ?? '');
        $target = sanitize_key($data['target'] ?? 'body');

        if (!in_array($target, ['body', 'heading'], true)) {
            return $this->rest_error(
                'ecf_invalid_font_target',
                __('Invalid font target.', 'ecf-framework'),
                400
            );
        }

        $settings = $this->get_settings();
        $imported_row = $this->import_font_library_family($family, $settings);
        if (is_wp_error($imported_row)) {
            return $this->rest_error(
                'ecf_font_import_failed',
                $imported_row->get_error_message(),
                500
            );
        }

        if (empty($this->local_font_rows_for_family($settings, $family))) {
            $settings['typography']['local_fonts'][] = $imported_row;
        }

        $selected_value = "'" . $family . "'";
        if ($target === 'heading') {
            $settings['heading_font_family'] = $selected_value;
        } else {
            $settings['base_font_family'] = $selected_value;
        }

        $sanitized = $this->sanitize_settings($settings);
        update_option($this->option_name, $sanitized);
        $this->clear_settings_cache();
        $this->clear_css_cache();

        return rest_ensure_response([
            'success' => true,
            'message' => __('Font imported locally.', 'ecf-framework'),
            'family' => $family,
            'target' => $target,
            'selectedValue' => $selected_value,
            'settings' => $this->get_settings(),
            'meta' => $this->rest_admin_meta(),
        ]);
    }

    public function rest_search_font_library_fonts(\WP_REST_Request $request) {
        $query = substr(sanitize_text_field((string) $request->get_param('q')), 0, 200);
        $limit = max(10, min(80, (int) $request->get_param('limit')));
        $settings = $this->get_settings();

        return rest_ensure_response([
            'success' => true,
            'groups' => $this->search_font_library_groups($query, $settings, $limit),
        ]);
    }

    /* ── Custom Presets ─────────────────────────────────────────────────── */

    private function get_custom_presets() {
        return get_option('ecf_custom_presets', []);
    }

    public function rest_get_custom_presets(\WP_REST_Request $request) {
        return rest_ensure_response(['success' => true, 'presets' => $this->get_custom_presets()]);
    }

    public function rest_save_custom_preset(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        $name = sanitize_text_field(substr($data['name'] ?? '', 0, 60));
        if ($name === '') {
            return $this->rest_error('ecf_preset_name_missing', __('Name fehlt.', 'ecf-framework'), 400);
        }

        $snapshot = $data['snapshot'] ?? [];
        if (empty($snapshot) || !is_array($snapshot)) {
            return $this->rest_error('ecf_preset_empty', __('Keine Daten.', 'ecf-framework'), 400);
        }
        $snapshot = $this->sanitize_settings($snapshot);

        $presets = $this->get_custom_presets();
        $id = 'cp_' . uniqid();
        $presets[] = [
            'id'       => $id,
            'name'     => $name,
            'created'  => wp_date('Y-m-d H:i'),
            'snapshot' => $snapshot,
        ];
        update_option('ecf_custom_presets', $presets);

        return rest_ensure_response(['success' => true, 'id' => $id, 'name' => $name, 'created' => end($presets)['created']]);
    }

    public function rest_delete_custom_preset(\WP_REST_Request $request) {
        $id = sanitize_key($request->get_param('id'));
        $presets = $this->get_custom_presets();
        $presets = array_values(array_filter($presets, fn($p) => ($p['id'] ?? '') !== $id));
        update_option('ecf_custom_presets', $presets);
        return rest_ensure_response(['success' => true]);
    }

    public function rest_reset_defaults(\WP_REST_Request $request) {
        $sections = $request->get_param('sections');

        if (empty($sections) || !is_array($sections)) {
            // Alles zurücksetzen
            delete_option($this->option_name);
            $this->clear_settings_cache();
            $this->clear_css_cache();
            $defaults  = $this->get_settings();
            $sanitized = $this->sanitize_settings($defaults);
            update_option($this->option_name, $sanitized);
            $this->settings_cache = $sanitized;
            return rest_ensure_response(['success' => true]);
        }

        // Partielles Reset: nur ausgewählte Bereiche
        $current  = get_option($this->option_name, []);
        $defaults = $this->defaults();

        $general_keys = [
            'root_font_size', 'interface_language', 'admin_design_preset', 'admin_design_mode',
            'ui_btn_font_size', 'ui_base_font_size', 'ui_nav_font_size', 'ui_font_family',
            'admin_content_font_size', 'admin_menu_font_size', 'autosave_enabled',
            'elementor_auto_sync_enabled', 'elementor_auto_sync_variables', 'elementor_auto_sync_classes',
            'auto_classes_enabled', 'auto_classes_headings', 'auto_classes_buttons',
            'auto_classes_text_link', 'auto_classes_form',
            'layrix_class_defaults',
            'github_update_checks_enabled', 'elementor_boxed_width', 'content_max_width',
            'base_font_family', 'heading_font_family', 'base_body_text_size', 'base_body_font_weight',
            'typography_browser_margin_reset', 'base_text_color', 'base_background_color',
            'link_color', 'focus_color', 'focus_outline_width', 'focus_outline_offset',
            'show_elementor_status_cards', 'elementor_variable_type_filter',
            'elementor_variable_type_filter_scopes', 'general_setting_favorites',
        ];

        foreach ($sections as $section) {
            $section = sanitize_key((string) $section);
            switch ($section) {
                case 'colors':
                    $current['colors'] = $defaults['colors'];
                    break;
                case 'radius':
                    $current['radius'] = $defaults['radius'];
                    break;
                case 'typography':
                    $current['typography'] = $defaults['typography'];
                    break;
                case 'spacing':
                    $current['spacing'] = $defaults['spacing'];
                    break;
                case 'shadows':
                    $current['shadows'] = $defaults['shadows'];
                    break;
                case 'general':
                    foreach ($general_keys as $key) {
                        if (array_key_exists($key, $defaults)) {
                            $current[$key] = $defaults[$key];
                        }
                    }
                    break;
                case 'utility_classes':
                    $current['utility_classes'] = $defaults['utility_classes'];
                    $current['starter_classes']  = $defaults['starter_classes'];
                    break;
            }
        }

        $sanitized = $this->sanitize_settings($current);
        update_option($this->option_name, $sanitized);
        $this->settings_cache = $sanitized;
        $this->clear_css_cache();
        return rest_ensure_response(['success' => true]);
    }
}
