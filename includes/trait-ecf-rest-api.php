<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_REST_API_Trait {
    private function rest_admin_meta() {
        return [
            'elementor_limit_snapshot' => $this->get_elementor_limit_snapshot(),
            'elementor_debug_snapshot' => $this->get_elementor_debug_snapshot(),
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

        return rest_ensure_response([
            'success'  => true,
            'message'  => __('Settings updated.', 'ecf-framework'),
            'settings' => $this->get_settings(),
            'meta' => $this->rest_admin_meta(),
        ]);
    }
}
