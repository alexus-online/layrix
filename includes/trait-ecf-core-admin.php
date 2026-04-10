<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Core_Admin_Trait {
    private $runtime_gettext_fallback_enabled = false;

    public function load_textdomain() {
        unload_textdomain('ecf-framework');
        $locale = $this->selected_interface_locale();
        if ($locale === 'en_US') {
            $this->runtime_gettext_fallback_enabled = false;
            return;
        }

        $domain_path = dirname(plugin_basename(ECF_FRAMEWORK_FILE)) . '/languages/';
        $custom_mofile = trailingslashit(dirname(ECF_FRAMEWORK_FILE)) . 'languages/ecf-framework-' . $locale . '.mo';
        $global_mofile = trailingslashit(WP_LANG_DIR) . 'plugins/ecf-framework-' . $locale . '.mo';

        if (file_exists($global_mofile)) {
            load_textdomain('ecf-framework', $global_mofile);
        } elseif (file_exists($custom_mofile)) {
            load_textdomain('ecf-framework', $custom_mofile);
        } else {
            load_plugin_textdomain(
                'ecf-framework',
                false,
                $domain_path
            );
        }

        $this->runtime_gettext_fallback_enabled = $this->selected_interface_language() === 'de';
    }

    public function filter_plugin_locale($locale, $domain) {
        if ($domain !== 'ecf-framework') {
            return $locale;
        }

        return $this->selected_interface_locale();
    }

    public function filter_runtime_gettext($translation, $text, $domain) {
        if ($domain !== 'ecf-framework') {
            return $translation;
        }

        if (!$this->runtime_gettext_fallback_enabled) {
            return $translation;
        }

        if ($translation !== $text && $translation !== '') {
            return $translation;
        }

        $map = $this->runtime_de_translations();

        return $map[$text] ?? $translation;
    }

    private function can_manage_framework() {
        return current_user_can('manage_options') && current_user_can('activate_plugins');
    }

    public function menu() {
        $svg_path = dirname(ECF_FRAMEWORK_FILE) . '/assets/layrix-icon.svg';
        $svg_raw  = file_exists($svg_path) ? file_get_contents($svg_path) : '';
        $icon     = $svg_raw
            ? 'data:image/svg+xml;base64,' . base64_encode($svg_raw)
            : 'dashicons-admin-customizer';

        add_menu_page('Layrix', 'Layrix', 'manage_options', 'ecf-framework', [$this, 'settings_page'], $icon, 58);
    }

    public function register() {
        register_setting('ecf_group', $this->option_name, [$this, 'sanitize_settings']);
    }

    private function synced_variable_labels_option_name() {
        return $this->option_name . '_synced_variable_labels';
    }

    private function debug_history_option_name() {
        return $this->option_name . '_debug_history';
    }

    private function layout_order_meta_key() {
        return $this->option_name . '_layout_order';
    }

    private function layout_columns_meta_key() {
        return $this->option_name . '_layout_columns';
    }

    private function wordpress_default_interface_language() {
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $locale = strtolower((string) $locale);

        return strpos($locale, 'de') === 0 ? 'de' : 'en';
    }

    private function selected_interface_language($settings = null) {
        if (is_array($settings) && !empty($settings['interface_language'])) {
            $choice = sanitize_key($settings['interface_language']);
        } else {
            $saved = get_option($this->option_name);
            $choice = is_array($saved) && !empty($saved['interface_language'])
                ? sanitize_key($saved['interface_language'])
                : '';
        }

        if (!in_array($choice, ['de', 'en'], true)) {
            $choice = $this->wordpress_default_interface_language();
        }

        return $choice;
    }

    private function selected_interface_locale($settings = null) {
        return $this->selected_interface_language($settings) === 'de' ? 'de_DE' : 'en_US';
    }

    private function is_german() {
        return $this->selected_interface_language() === 'de';
    }

    private function is_backend_german() {
        return $this->is_german();
    }

    private function runtime_de_translations() {
        static $translations = null;

        if ($translations !== null) {
            return $translations;
        }

        $file = dirname(__DIR__) . '/includes/ecf-runtime-de-translations.php';
        if (!file_exists($file)) {
            $translations = [];
            return $translations;
        }

        $loaded = require $file;
        $translations = is_array($loaded) ? $loaded : [];

        return $translations;
    }

    private function translate_ecf_text($english, $fallback_german = '') {
        $translated = __($english, 'ecf-framework');

        if ($translated === $english && $fallback_german !== '') {
            return $fallback_german;
        }

        return $translated;
    }

    private function tip($en, $de) {
        $text = esc_attr($this->translate_ecf_text($en, $de));
        return '<span class="ecf-tip" data-tip="'.$text.'">?</span>';
    }

    private function tip_hover_label($label, $tip_en, $tip_de) {
        $tip_text = $this->translate_ecf_text($tip_en, $tip_de);
        return '<span class="ecf-tip-hover" data-tip="'.esc_attr($tip_text).'">'.esc_html($label).'</span>';
    }

    private function debug_logging_enabled() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    private function debug_log($message, $context = null, $type = '') {
        if (!$this->debug_logging_enabled()) {
            return;
        }

        $line = 'ECF debug: ' . $message;
        if ($context !== null) {
            $line .= ' ' . wp_json_encode($context);
        }

        error_log($line);
        $this->append_debug_history_entry($message, $context, $type);
    }

    private function infer_debug_history_type($message) {
        $normalized = strtolower((string) $message);

        if (strpos($normalized, 'import') !== false || strpos($normalized, 'export') !== false) {
            return 'import';
        }

        if (strpos($normalized, 'update') !== false || strpos($normalized, 'version') !== false) {
            return 'update';
        }

        if (strpos($normalized, 'sync') !== false || strpos($normalized, 'class library') !== false || strpos($normalized, 'native ') !== false) {
            return 'sync';
        }

        if (strpos($normalized, 'autosave') !== false || strpos($normalized, 'language') !== false || strpos($normalized, 'settings') !== false) {
            return 'settings';
        }

        return 'system';
    }

    private function append_debug_history_entry($message, $context = null, $type = '') {
        $entries = get_option($this->debug_history_option_name(), []);
        if (!is_array($entries)) {
            $entries = [];
        }

        $entries[] = [
            'time' => current_time('mysql'),
            'type' => $type !== '' ? sanitize_key($type) : $this->infer_debug_history_type($message),
            'message' => (string) $message,
            'context' => $context === null ? '' : wp_json_encode($context),
        ];

        if (count($entries) > 50) {
            $entries = array_slice($entries, -50);
        }

        update_option($this->debug_history_option_name(), $entries, false);
    }

    private function debug_history_entries() {
        $entries = get_option($this->debug_history_option_name(), []);
        return is_array($entries) ? array_reverse($entries) : [];
    }

    private function sanitize_layout_orders($orders) {
        if (!is_array($orders)) {
            return [];
        }

        $sanitized = [];

        foreach ($orders as $group => $items) {
            $group_key = sanitize_key($group);
            if ($group_key === '' || !is_array($items)) {
                continue;
            }

            $seen = [];
            $sanitized[$group_key] = [];

            foreach ($items as $item) {
                $item_key = sanitize_key($item);
                if ($item_key === '' || isset($seen[$item_key])) {
                    continue;
                }

                $seen[$item_key] = true;
                $sanitized[$group_key][] = $item_key;
            }

            if (empty($sanitized[$group_key])) {
                unset($sanitized[$group_key]);
            }
        }

        return $sanitized;
    }

    private function sanitize_layout_columns($columns) {
        if (!is_array($columns)) {
            return [];
        }

        $sanitized = [];
        foreach ($columns as $group => $count) {
            $group_key = sanitize_key($group);
            $count_int = (int) $count;
            if ($group_key === '' || $count_int < 1 || $count_int > 3) {
                continue;
            }
            $sanitized[$group_key] = $count_int;
        }

        return $sanitized;
    }

    private function get_user_layout_orders($user_id = 0) {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ($user_id <= 0) {
            return [];
        }

        $saved = get_user_meta($user_id, $this->layout_order_meta_key(), true);

        return $this->sanitize_layout_orders(is_array($saved) ? $saved : []);
    }

    private function get_user_layout_columns($user_id = 0) {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ($user_id <= 0) {
            return $this->default_layout_columns();
        }

        $saved = get_user_meta($user_id, $this->layout_columns_meta_key(), true);

        return array_merge(
            $this->default_layout_columns(),
            $this->sanitize_layout_columns(is_array($saved) ? $saved : [])
        );
    }

    private function save_user_layout_orders($orders, $user_id = 0) {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ($user_id <= 0) {
            return [];
        }

        $sanitized = $this->sanitize_layout_orders($orders);
        update_user_meta($user_id, $this->layout_order_meta_key(), $sanitized);

        return $sanitized;
    }

    private function save_user_layout_columns($columns, $user_id = 0) {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ($user_id <= 0) {
            return $this->default_layout_columns();
        }

        $sanitized = $this->sanitize_layout_columns($columns);
        update_user_meta($user_id, $this->layout_columns_meta_key(), $sanitized);

        return array_merge($this->default_layout_columns(), $sanitized);
    }

    private function default_layout_columns() {
        return [
            'components-website-type-size' => 2,
        ];
    }

    public function handle_clear_debug_history() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'components', 'ecf_sync' => 'error']);
        }

        check_admin_referer('ecf_clear_debug_history');
        delete_option($this->debug_history_option_name());
        $this->redirect_with_message(
            admin_url('admin.php?page=ecf-framework'),
            ['panel' => 'components', 'ecf_sync' => 'ok'],
            __('Debug history cleared.', 'ecf-framework')
        );
    }

    private function unauthorized_notice_message() {
        return __('You are not allowed to perform this action.', 'ecf-framework');
    }

    private function admin_notice_scope_from_url($base_url) {
        return strpos((string) $base_url, 'plugins.php') !== false ? 'plugins' : 'ecf_group';
    }

    private function admin_notice_transient_key($scope = 'ecf_group') {
        return $this->option_name . '_notice_' . sanitize_key((string) $scope) . '_' . get_current_user_id();
    }

    private function queue_admin_notice($message, $type = 'success', $scope = 'ecf_group') {
        $message = trim((string) $message);
        if ($message === '') {
            return;
        }

        $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'success';
        $scope = sanitize_key((string) $scope) ?: 'ecf_group';
        $key = $this->admin_notice_transient_key($scope);
        $queue = get_transient($key);
        if (!is_array($queue)) {
            $queue = [];
        }

        $queue[] = [
            'message' => $message,
            'type' => $type,
        ];

        set_transient($key, $queue, MINUTE_IN_SECONDS * 10);
    }

    private function consume_admin_notices($scope = 'ecf_group') {
        $key = $this->admin_notice_transient_key($scope);
        $queue = get_transient($key);
        delete_transient($key);

        return is_array($queue) ? $queue : [];
    }

    private function render_consumed_admin_notices($scope = 'ecf_group', $panel_class = 'ecf-panel-notice') {
        foreach ($this->consume_admin_notices($scope) as $notice) {
            $type = sanitize_key((string) ($notice['type'] ?? 'success'));
            $message = (string) ($notice['message'] ?? '');
            if ($message === '') {
                continue;
            }

            $notice_class = 'notice notice-' . $type;
            if ($panel_class !== '') {
                $notice_class .= ' ' . $panel_class . ' ' . $panel_class . '--' . $type;
            }

            echo '<div class="' . esc_attr($notice_class) . '"><p>' . esc_html($message) . '</p></div>';
        }
    }

    private function redirect_with_message($base_url, array $query = [], $message = '', $message_key = 'ecf_message') {
        if ($message !== '') {
            $type = (!empty($query['ecf_sync']) && $query['ecf_sync'] === 'error') ? 'error' : 'success';
            $this->queue_admin_notice($message, $type, $this->admin_notice_scope_from_url($base_url));
        }

        wp_safe_redirect(add_query_arg($query, $base_url));
        exit;
    }

    private function deny_admin_request($base_url, array $query = [], $message_key = 'ecf_message') {
        $this->redirect_with_message($base_url, $query, $this->unauthorized_notice_message(), $message_key);
    }

    private function ajax_error($message, $status = 400, array $extra = []) {
        $payload = array_merge([
            'message' => (string) $message,
            'status' => (int) $status,
        ], $extra);

        wp_send_json_error($payload, $status);
    }

    private function rest_error($code, $message, $status = 400, array $data = []) {
        return new \WP_Error(
            (string) $code,
            (string) $message,
            array_merge(['status' => (int) $status], $data)
        );
    }
}
