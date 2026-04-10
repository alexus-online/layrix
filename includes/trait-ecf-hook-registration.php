<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Hook_Registration_Trait {
    private function register_core_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_head', [$this, 'output_css'], 99);
        add_action('admin_post_ecf_clear_debug_history', [$this, 'handle_clear_debug_history']);

        add_filter('plugin_locale', [$this, 'filter_plugin_locale'], 10, 2);
        add_filter('gettext', [$this, 'filter_runtime_gettext'], 20, 3);
        add_filter('upload_mimes', [$this, 'allow_font_upload_mimes']);
    }

    private function register_native_elementor_hooks() {
        add_action('admin_post_ecf_native_sync', [$this, 'handle_native_sync']);
        add_action('admin_post_nopriv_ecf_native_sync', [$this, 'handle_native_sync']);
        add_action('admin_post_ecf_class_library_sync', [$this, 'handle_class_library_sync']);
        add_action('admin_post_nopriv_ecf_class_library_sync', [$this, 'handle_class_library_sync']);
        add_action('admin_post_ecf_native_cleanup', [$this, 'handle_native_cleanup']);
        add_action('admin_post_ecf_class_cleanup', [$this, 'handle_class_cleanup']);
        add_action('admin_post_ecf_export', [$this, 'handle_export']);
        add_action('admin_post_ecf_import', [$this, 'handle_import']);

        add_action('wp_ajax_ecf_get_variables', [$this, 'ajax_get_variables']);
        add_action('wp_ajax_ecf_delete_variables', [$this, 'ajax_delete_variables']);
        add_action('wp_ajax_ecf_update_variable', [$this, 'ajax_update_variable']);
        add_action('wp_ajax_ecf_get_classes', [$this, 'ajax_get_classes']);
        add_action('wp_ajax_ecf_delete_classes', [$this, 'ajax_delete_classes']);
    }

    private function register_github_updater_hooks() {
        add_action('admin_post_ecf_check_updates', [$this, 'handle_check_updates']);
        add_action('admin_post_ecf_toggle_auto_updates', [$this, 'handle_toggle_auto_updates']);
        add_action('admin_notices', [$this, 'render_plugin_list_notice']);
        add_action('load-plugins.php', [$this, 'maybe_refresh_plugin_updates']);
        add_action('upgrader_process_complete', [$this, 'clear_github_update_cache'], 10, 2);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'merge_github_update_into_transient']);
        add_filter('update_plugins_github.com', [$this, 'inject_github_plugin_update'], 10, 4);
        add_filter('plugins_api', [$this, 'inject_github_plugin_info'], 10, 3);
        add_filter('http_request_args', [$this, 'add_github_auth_headers'], 10, 2);
        add_filter('upgrader_pre_install', [$this, 'remember_active_state_before_upgrade'], 10, 2);
        add_filter('upgrader_source_selection', [$this, 'rename_github_update_source'], 10, 4);
        add_filter('upgrader_post_install', [$this, 'normalize_github_plugin_destination'], 10, 3);
        add_filter('plugin_action_links_' . plugin_basename(ECF_FRAMEWORK_FILE), [$this, 'add_plugin_action_links']);
        add_filter('plugin_auto_update_setting_html', [$this, 'render_plugin_auto_update_column'], 10, 3);
    }

    private function register_admin_asset_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    private function register_elementor_editor_hooks() {
        add_action('elementor/element/after_section_end', [$this, 'inject_editor_controls'], 10, 3);
        add_action('elementor/frontend/widget/before_render', [$this, 'append_ecf_classes_before_render']);
        add_action('elementor/frontend/container/before_render', [$this, 'append_ecf_classes_before_render']);
        add_action('elementor/frontend/section/before_render', [$this, 'append_ecf_classes_before_render']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'editor_assets']);
    }

    private function register_hooks() {
        $this->register_core_hooks();
        $this->register_native_elementor_hooks();
        $this->register_github_updater_hooks();
        $this->register_admin_asset_hooks();
        $this->register_elementor_editor_hooks();
    }
}
