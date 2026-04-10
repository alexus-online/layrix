<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_REST_API_Trait {
    private function rest_admin_meta() {
        return [
            'elementor_limit_snapshot' => $this->get_elementor_limit_snapshot(),
            'elementor_debug_snapshot' => $this->get_elementor_debug_snapshot(),
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
        if (method_exists($this, 'clear_elementor_sync_caches')) {
            $this->clear_elementor_sync_caches();
        }

        return rest_ensure_response([
            'success'  => true,
            'message'  => __('Settings updated.', 'ecf-framework'),
            'settings' => $this->get_settings(),
            'meta' => $this->rest_admin_meta(),
        ]);
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
                'User-Agent' => 'Mozilla/5.0 (compatible; ECF Framework Font Import)',
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
                'User-Agent' => 'Mozilla/5.0 (compatible; ECF Framework Font Search)',
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
        $query = sanitize_text_field((string) $request->get_param('q'));
        $limit = max(10, min(80, (int) $request->get_param('limit')));
        $settings = $this->get_settings();

        return rest_ensure_response([
            'success' => true,
            'groups' => $this->search_font_library_groups($query, $settings, $limit),
        ]);
    }
}
