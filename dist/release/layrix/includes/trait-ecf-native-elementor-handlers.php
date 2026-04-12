<?php

trait ECF_Framework_Native_Elementor_Handlers_Trait {
    private function normalize_elementor_variable_label($label) {
        return trim((string) sanitize_title(wp_unslash((string) $label)));
    }

    private function export_payload($settings) {
        return [
            'meta' => [
                'plugin' => 'Layrix',
                'plugin_version' => $this->current_plugin_version(),
                'schema_version' => 1,
                'exported_at' => gmdate('c'),
                'site_url' => home_url('/'),
                'wordpress_locale' => get_locale(),
            ],
            'settings' => $settings,
        ];
    }

    public function handle_native_sync() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'sync', 'ecf_sync' => 'error']);
        }

        $this->debug_log('native sync entered');
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ecf_native_sync')) {
            $this->debug_log('native sync nonce failed');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'sync', 'ecf_sync' => 'error'],
                __('Security check failed. Please reload the page and try again.', 'ecf-framework')
            );
        }

        try {
            $var_result = $this->sync_native_variables_merge();
            $this->debug_log('native variables synced', $var_result);
            $class_result = $this->sync_native_classes_merge();
            $this->debug_log('native classes synced', $class_result);
            $message = $this->build_sync_summary_message(
                'Variables',
                'Variablen',
                $var_result['created'],
                $var_result['updated']
            ) . ' ' . $this->build_sync_summary_message(
                'Classes',
                'Klassen',
                $class_result['created'],
                $class_result['updated']
            );

            if (!empty($class_result['skipped'])) {
                $message .= ' ' . sprintf(
                    __('%1$d new Global Classes were skipped because Elementor can currently not create more than %3$d Global Classes and already uses %2$d.', 'ecf-framework'),
                    $class_result['skipped'],
                    $class_result['total'],
                    $class_result['limit']
                );
            }

            $this->debug_log('native sync redirecting success');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->debug_log('native sync exception', ['message' => $e->getMessage()]);
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function handle_class_library_sync() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'utilities', 'ecf_sync' => 'error']);
        }

        $this->debug_log('class library sync entered');
        $nonce = isset($_POST['_ecf_class_library_sync_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ecf_class_library_sync_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ecf_class_library_sync')) {
            $this->debug_log('class library sync nonce failed');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'error'],
                __('Security check failed. Please reload the page and try again.', 'ecf-framework')
            );
        }

        try {
            $submitted = $_POST[$this->option_name] ?? [];
            $sanitized = $this->sanitize_settings(is_array($submitted) ? wp_unslash($submitted) : []);
            update_option($this->option_name, $sanitized);
            $this->debug_log('class library settings saved');

            $class_result = $this->sync_native_classes_merge();
            $this->debug_log('class library classes synced', $class_result);
            $message = $this->build_sync_summary_message(
                'Classes',
                'Klassen',
                $class_result['created'],
                $class_result['updated']
            );
            if (!empty($class_result['deleted'])) {
                $message = rtrim($message, '.') . ', ' . sprintf(
                    __('%1$d removed.', 'ecf-framework'),
                    (int) $class_result['deleted']
                );
            }

            if (!empty($class_result['skipped'])) {
                $message .= ' ' . sprintf(
                    __('%1$d new Global Classes were skipped because Elementor can currently not create more than %3$d Global Classes and already uses %2$d.', 'ecf-framework'),
                    $class_result['skipped'],
                    $class_result['total'],
                    $class_result['limit']
                );
            }

            $this->debug_log('class library redirecting success');
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->debug_log('class library exception', ['message' => $e->getMessage()]);
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'utilities', 'ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function handle_native_cleanup() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_native_cleanup');

        try {
            $vars_count = $this->get_native_variable_cleanup_count();
            $classes_count = $this->get_native_class_cleanup_count();

            if ($vars_count === 0 && $classes_count === 0) {
                $this->redirect_with_message(
                    admin_url('admin.php?page=ecf-framework'),
                    ['ecf_sync' => 'ok'],
                    __('No ECF variables or global classes were found in Elementor.', 'ecf-framework')
                );
            }

            $vars_deleted = $this->cleanup_native_variables();
            $classes_deleted = $this->cleanup_native_classes();
            $message = sprintf(
                __('%1$d variables and %2$d global classes were removed. The Elementor cache was cleared automatically.', 'ecf-framework'),
                $vars_deleted,
                $classes_deleted
            );
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function handle_class_cleanup() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['panel' => 'sync', 'ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_class_cleanup');

        try {
            $classes_count = $this->get_native_class_cleanup_count();

            if ($classes_count === 0) {
                $this->redirect_with_message(
                    admin_url('admin.php?page=ecf-framework'),
                    ['panel' => 'sync', 'ecf_sync' => 'ok'],
                    __('No ECF global classes were found in Elementor.', 'ecf-framework')
                );
            }

            $classes_deleted = $this->cleanup_native_classes();
            $message = sprintf(
                __('%1$d ECF classes were removed from Elementor. You can now sync them again as clean empty classes.', 'ecf-framework'),
                $classes_deleted
            );
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'sync', 'ecf_sync' => 'ok'],
                $message
            );
        } catch (\Throwable $e) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['panel' => 'sync', 'ecf_sync' => 'error'],
                $e->getMessage()
            );
        }
    }

    public function ajax_get_variables() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            $this->ajax_error(__('You are not allowed to perform this action.', 'ecf-framework'), 403);
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            $this->ajax_error(__('Elementor variable classes are not available.', 'ecf-framework'), 500);
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            $this->ajax_error(__('No active Elementor kit found.', 'ecf-framework'), 500);
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $ecf = [];
        $foreign = [];

        foreach ($collection->all() as $id => $variable) {
            if ($variable->is_deleted()) {
                continue;
            }
            $entry = [
                'id' => $id,
                'label' => $variable->label(),
                'type' => $variable->type(),
                'value' => $variable->value(),
            ];
            if (strpos(strtolower($variable->label()), 'ecf-') === 0) {
                $ecf[] = $entry;
            } else {
                $foreign[] = $entry;
            }
        }

        wp_send_json_success(['ecf' => $ecf, 'foreign' => $foreign]);
    }

    public function ajax_get_classes() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            $this->ajax_error(__('You are not allowed to perform this action.', 'ecf-framework'), 403);
        }

        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            $this->ajax_error(__('Elementor global classes repository is not available.', 'ecf-framework'), 500);
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];

        if (!is_array($items)) {
            $items = [];
        }

        $ordered_ids = [];
        foreach ($order as $id) {
            if (isset($items[$id])) {
                $ordered_ids[] = $id;
            }
        }
        foreach (array_keys($items) as $id) {
            if (!in_array($id, $ordered_ids, true)) {
                $ordered_ids[] = $id;
            }
        }

        $ecf = [];
        $foreign = [];

        foreach ($ordered_ids as $id) {
            $item = $items[$id];
            $entry = [
                'id' => $id,
                'label' => $item['label'] ?? $id,
                'type' => $this->native_class_category($item),
                'value' => $this->native_class_preview_value($item),
            ];

            if ($this->is_ecf_native_class($id, $item)) {
                $ecf[] = $entry;
            } else {
                $foreign[] = $entry;
            }
        }

        wp_send_json_success(['ecf' => $ecf, 'foreign' => $foreign]);
    }

    public function ajax_delete_variables() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            $this->ajax_error(__('You are not allowed to perform this action.', 'ecf-framework'), 403);
        }

        $ids = isset($_POST['ids']) ? array_map('strval', (array) $_POST['ids']) : [];
        if (empty($ids)) {
            $this->ajax_error(__('No IDs were provided.', 'ecf-framework'));
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            $this->ajax_error(__('Elementor variable classes are not available.', 'ecf-framework'), 500);
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            $this->ajax_error(__('No active Elementor kit found.', 'ecf-framework'), 500);
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $deleted = 0;

        foreach ($collection->all() as $id => $variable) {
            if (in_array((string) $id, $ids, true) && $this->delete_native_variable_entity($collection, $id, $variable)) {
                $deleted++;
            }
        }

        $repo->save($collection);
        $this->clear_elementor_sync_caches();

        wp_send_json_success(['deleted' => $deleted]);
    }

    public function ajax_update_variable() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            $this->ajax_error(__('You are not allowed to perform this action.', 'ecf-framework'), 403);
        }

        $id = sanitize_text_field(wp_unslash($_POST['id'] ?? ''));
        $label = $this->normalize_elementor_variable_label($_POST['label'] ?? '');
        $type = sanitize_key($_POST['type'] ?? '');
        $value = wp_unslash($_POST['value'] ?? '');

        if ($id === '' || $label === '' || $type === '') {
            $this->ajax_error(__('Missing required fields.', 'ecf-framework'));
        }

        if (!in_array($type, ['global-color-variable', 'global-size-variable', 'global-string-variable'], true)) {
            $this->ajax_error(__('Unsupported variable type.', 'ecf-framework'));
        }

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            $this->ajax_error(__('Elementor variable classes are not available.', 'ecf-framework'), 500);
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            $this->ajax_error(__('No active Elementor kit found.', 'ecf-framework'), 500);
        }

        try {
            $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
            $collection = $repo->load();
            $target = null;

            foreach ($collection->all() as $variable_id => $variable) {
                if ((string) $variable_id === $id) {
                    $target = $variable;
                    break;
                }
            }

            if (!$target) {
                $this->ajax_error(__('Variable not found.', 'ecf-framework'), 404);
            }

            if ($this->is_ecf_native_variable($target)) {
                $this->ajax_error(__('Generated ECF variables cannot be edited here.', 'ecf-framework'), 400);
            }

            if ($type === 'global-color-variable') {
                $sanitized_value = $this->sanitize_css_color_value($value);
            } elseif ($type === 'global-size-variable') {
                $sanitized_value = $this->sanitize_css_size_value($value);
            } else {
                $sanitized_value = sanitize_text_field($value);
            }

            if ($sanitized_value === '') {
                $this->ajax_error(__('Invalid variable value.', 'ecf-framework'));
            }

            $target->apply_changes([
                'label' => $label,
                'type' => $type,
                'value' => $sanitized_value,
            ]);

            if (method_exists($target, 'is_deleted') && $target->is_deleted() && method_exists($target, 'restore')) {
                $target->restore();
            }

            $repo->save($collection);

            try {
                $this->clear_elementor_sync_caches();
            } catch (\Throwable $cache_exception) {
                if (method_exists($this, 'debug_log')) {
                    $this->debug_log(
                        'foreign_variable_update_cache_clear_failed',
                        [
                            'id' => $id,
                            'label' => $label,
                            'type' => $type,
                            'message' => $cache_exception->getMessage(),
                        ]
                    );
                }
            }

            wp_send_json_success([
                'item' => [
                    'id' => $id,
                    'label' => $label,
                    'type' => $type,
                    'value' => $sanitized_value,
                ],
            ]);
        } catch (\Throwable $exception) {
            if (method_exists($this, 'debug_log')) {
                $this->debug_log(
                    'foreign_variable_update_exception',
                    [
                        'id' => $id,
                        'label' => $label,
                        'type' => $type,
                        'message' => $exception->getMessage(),
                    ]
                );
            }

            $this->ajax_error(
                sprintf(
                    /* translators: %s: underlying exception message */
                    __('Variable could not be updated: %s', 'ecf-framework'),
                    $exception->getMessage()
                ),
                500
            );
        }
    }

    public function ajax_delete_classes() {
        check_ajax_referer('ecf_variables', 'nonce');
        if (!$this->can_manage_framework()) {
            $this->ajax_error(__('You are not allowed to perform this action.', 'ecf-framework'), 403);
        }

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        if (empty($ids)) {
            $this->ajax_error(__('No IDs were provided.', 'ecf-framework'));
        }

        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            $this->ajax_error(__('Elementor global classes repository is not available.', 'ecf-framework'), 500);
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];
        $deleted = 0;

        if (!is_array($items)) {
            $items = [];
        }

        foreach ($ids as $id) {
            if (isset($items[$id]) && $this->is_ecf_native_class($id, is_array($items[$id]) ? $items[$id] : [])) {
                unset($items[$id]);
                $order = array_values(array_filter($order, static fn($entry_id) => $entry_id !== $id));
                $deleted++;
            }
        }

        $repo->put($items, $order);
        $this->clear_elementor_sync_caches();

        wp_send_json_success(['deleted' => $deleted]);
    }

    public function handle_export() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_export');

        $settings = $this->get_settings();
        $payload = $this->export_payload($settings);
        $filename = 'ecf-framework-' . $this->current_plugin_version() . '-' . date('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handle_import() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('admin.php?page=ecf-framework'), ['ecf_sync' => 'error']);
        }
        check_admin_referer('ecf_import');

        if (empty($_FILES['ecf_import_file']['tmp_name'])) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                __('No file uploaded.', 'ecf-framework')
            );
        }

        $file = $_FILES['ecf_import_file'];
        $filename = sanitize_file_name($file['name'] ?? '');
        $filesize = (int) ($file['size'] ?? 0);
        $max_size = 1024 * 1024 * 2;

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                __('File upload failed.', 'ecf-framework')
            );
        }

        if ($filename === '' || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                __('Please upload a valid JSON file.', 'ecf-framework')
            );
        }

        if ($filesize <= 0 || $filesize > $max_size) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                __('The JSON file is empty or too large.', 'ecf-framework')
            );
        }

        $content = file_get_contents($_FILES['ecf_import_file']['tmp_name']);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->redirect_with_message(
                admin_url('admin.php?page=ecf-framework'),
                ['ecf_sync' => 'error'],
                __('Invalid JSON file.', 'ecf-framework')
            );
        }

        $import_settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : $data;
        $meta = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [];

        $sanitized = $this->sanitize_settings($import_settings);
        update_option($this->option_name, $sanitized);

        $message = __('Settings imported successfully.', 'ecf-framework');
        if (!empty($meta['plugin_version']) && version_compare((string) $meta['plugin_version'], (string) $this->current_plugin_version(), '!=')) {
            $message .= ' ' . sprintf(
                __('Imported from plugin version %1$s into %2$s. Please review General Settings, Sync, and editor-related options afterwards.', 'ecf-framework'),
                (string) $meta['plugin_version'],
                (string) $this->current_plugin_version()
            );
        }

        $this->redirect_with_message(
            admin_url('admin.php?page=ecf-framework'),
            ['ecf_sync' => 'ok'],
            $message
        );
    }

    public function rest_sync_native(\WP_REST_Request $request) {
        try {
            $sync_variables = $request->get_param('variables') !== false;
            $sync_classes = $request->get_param('classes') === true;
            $var_result = $sync_variables ? $this->sync_native_variables_merge() : ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => true];
            $class_result = $sync_classes ? $this->sync_native_classes_merge() : ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => true];

            return rest_ensure_response([
                'success' => true,
                'variables' => $var_result,
                'classes' => $class_result,
                'meta' => method_exists($this, 'rest_admin_meta') ? $this->rest_admin_meta() : null,
                'message' => 'Native Elementor sync completed.',
            ]);
        } catch (\Throwable $e) {
            return new \WP_Error(
                'ecf_sync_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
}
