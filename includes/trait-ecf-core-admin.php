<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Core_Admin_Trait {
    private $runtime_gettext_fallback_enabled = false;

    public function load_textdomain() {
        unload_textdomain('ecf-framework');
        $locale = $this->selected_interface_locale();
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
        add_menu_page('ECF Elementor v4 Core Framework', 'ECF Elementor v4 Core Framework', 'manage_options', 'ecf-framework', [$this, 'settings_page'], 'dashicons-admin-customizer', 58);
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

    private function get_user_layout_orders($user_id = 0) {
        $user_id = $user_id ? (int) $user_id : (int) get_current_user_id();
        if ($user_id <= 0) {
            return [];
        }

        $saved = get_user_meta($user_id, $this->layout_order_meta_key(), true);

        return $this->sanitize_layout_orders(is_array($saved) ? $saved : []);
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

    private function redirect_with_message($base_url, array $query = [], $message = '', $message_key = 'ecf_message') {
        if ($message !== '') {
            $query[$message_key] = $message;
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
