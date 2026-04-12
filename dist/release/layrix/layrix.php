<?php
/**
 * Plugin Name: Layrix
 * Description: Core-Framework-style tokens, editor panel, and native Elementor variable/class sync.
 * Version: 0.3.7
 * Author: Alexander Kaiser
 * Update URI: https://github.com/alexus-online/layrix
 * Text Domain: ecf-framework
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if (!defined('ECF_FRAMEWORK_FILE')) {
    define('ECF_FRAMEWORK_FILE', __FILE__);
}

if (!function_exists('plugin_is_active') || !function_exists('activate_plugin')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

require_once __DIR__ . '/includes/trait-ecf-updater.php';
require_once __DIR__ . '/includes/trait-ecf-elementor-status.php';
require_once __DIR__ . '/includes/trait-ecf-changelog.php';
require_once __DIR__ . '/includes/trait-ecf-admin-general.php';
require_once __DIR__ . '/includes/trait-ecf-native-elementor-data.php';
require_once __DIR__ . '/includes/trait-ecf-native-elementor-handlers.php';
require_once __DIR__ . '/includes/trait-ecf-editor-preview.php';
require_once __DIR__ . '/includes/trait-ecf-render-helpers.php';
require_once __DIR__ . '/includes/trait-ecf-output-css.php';
require_once __DIR__ . '/includes/trait-ecf-framework-config.php';
require_once __DIR__ . '/includes/trait-ecf-admin-page-sections.php';
require_once __DIR__ . '/includes/trait-ecf-hook-registration.php';
require_once __DIR__ . '/includes/trait-ecf-settings-sanitizer.php';
require_once __DIR__ . '/includes/trait-ecf-design-math.php';
require_once __DIR__ . '/includes/trait-ecf-asset-loading.php';
require_once __DIR__ . '/includes/trait-ecf-core-admin.php';
require_once __DIR__ . '/includes/trait-ecf-rest-api.php';

if (!class_exists('ECF_Framework')) {
class ECF_Framework {
    use ECF_Framework_Updater_Trait;
    use ECF_Framework_Elementor_Status_Trait;
    use ECF_Framework_Changelog_Trait;
    use ECF_Framework_Admin_General_Trait;
    use ECF_Framework_Native_Elementor_Data_Trait;
    use ECF_Framework_Native_Elementor_Handlers_Trait;
    use ECF_Framework_Editor_Preview_Trait;
    use ECF_Framework_Render_Helpers_Trait;
    use ECF_Framework_Output_CSS_Trait;
    use ECF_Framework_Config_Trait;
    use ECF_Framework_Admin_Page_Sections_Trait;
    use ECF_Framework_Hook_Registration_Trait;
    use ECF_Framework_Settings_Sanitizer_Trait;
    use ECF_Framework_Design_Math_Trait;
    use ECF_Framework_Asset_Loading_Trait;
    use ECF_Framework_Core_Admin_Trait;
    use ECF_Framework_REST_API_Trait;

    private $option_name = 'ecf_framework_v50';
    private $github_repo = 'alexus-online/layrix';
    private $github_branch = 'master';
    private $update_cache_key = 'ecf_framework_github_update';
    private $canonical_plugin_slug = 'layrix';

    public function __construct() {
        $this->register_hooks();
    }

    private function get_scale_ratio_presets() {
        return [
            '1.067' => 'Minor Second (1.067)',
            '1.125' => 'Major Second (1.125)',
            '1.2'   => 'Minor Third (1.2)',
            '1.25'  => 'Major Third (1.25)',
            '1.333' => 'Perfect Fourth (1.333)',
            '1.414' => 'Augmented Fourth (1.414)',
            '1.5'   => 'Perfect Fifth (1.5)',
            '1.6'   => 'Minor Sixth (1.6)',
            '1.618' => 'Golden Ratio (1.618)',
            '1.667' => 'Major Sixth (1.667)',
            '1.778' => 'Minor Seventh (1.778)',
            '1.875' => 'Major Seventh (1.875)',
            '2'     => 'Octave (2)',
        ];
    }

    private function render_scale_ratio_select($name, $value) {
        $presets = $this->get_scale_ratio_presets();
        $current = $this->format_preview_number((float) $value, 3);
        $has_match = isset($presets[$current]);

        echo '<select name="' . esc_attr($name) . '">';
        if (!$has_match) {
            echo '<option value="' . esc_attr($current) . '" selected>' . esc_html($current) . '</option>';
        }
        foreach ($presets as $preset_value => $label) {
            echo '<option value="' . esc_attr($preset_value) . '" ' . selected($current, $preset_value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    private function is_local_font_url($url) {
        $url = esc_url_raw($url);
        if ($url === '') return false;

        $font_host = wp_parse_url($url, PHP_URL_HOST);
        $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $font_path = wp_parse_url($url, PHP_URL_PATH);
        $uploads   = wp_get_upload_dir();
        $uploads_baseurl = $uploads['baseurl'] ?? '';
        $uploads_host = wp_parse_url($uploads_baseurl, PHP_URL_HOST);
        $uploads_path = wp_parse_url($uploads_baseurl, PHP_URL_PATH);

        if (!$font_host || !$site_host || strtolower($font_host) !== strtolower($site_host)) {
            return false;
        }

        if (!$uploads_host || strtolower($uploads_host) !== strtolower($site_host)) {
            return false;
        }

        if (!$font_path || !$uploads_path || strpos($font_path, $uploads_path) !== 0) {
            return false;
        }

        return true;
    }

    private function sanitize_css_size_value($value) {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (preg_match('/^-?\d+(?:\.\d+)?(?:px|rem|em|ch|%|vw|vh)$/i', $value)) return strtolower($value);
        if (preg_match('/^(?:clamp|min|max|calc)\([a-zA-Z0-9\s.,%+\-*\/]+\)$/', $value)) return preg_replace('/\s+/', ' ', $value);
        return '';
    }

    private function parse_css_size_parts($value) {
        $value = trim((string) $value);
        if (preg_match('/^(-?\d+(?:\.\d+)?)(px|rem|em|ch|%|vw|vh)$/i', $value, $m)) {
            return [
                'value'  => $m[1],
                'format' => strtolower($m[2]),
            ];
        }

        return [
            'value'  => $value,
            'format' => 'custom',
        ];
    }

    private function sanitize_css_font_stack($value) {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (!preg_match('/^[a-zA-Z0-9,\s"\'.\-]+$/', $value)) return '';
        return sanitize_text_field($value);
    }

    private function sanitize_css_shadow_value($value) {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (!preg_match('/^[a-zA-Z0-9\s(),.%#\-]+$/', $value)) return '';
        return sanitize_text_field($value);
    }

    private function sanitize_css_number_or_size($value) {
        $value = trim((string) $value);
        if ($value === '') return '';
        if (preg_match('/^-?\d+(?:\.\d+)?(?:px|rem|em|%)?$/i', $value)) return strtolower($value);
        return '';
    }

    public function allow_font_upload_mimes($mimes) {
        if (!$this->can_manage_framework()) {
            return $mimes;
        }
        $mimes['woff'] = 'font/woff';
        $mimes['woff2'] = 'font/woff2';
        $mimes['ttf'] = 'font/ttf';
        $mimes['otf'] = 'font/otf';
        return $mimes;
    }

    public function settings_page() {
        $settings = $this->get_settings();
        $cleanup_variable_count = $this->get_native_variable_cleanup_count();
        $cleanup_class_count = $this->get_native_class_cleanup_count();
        $native_variable_counts = $this->get_native_variable_counts();
        $elementor_variable_limit = $this->get_native_global_variable_limit();
        $elementor_variable_limit_status = $this->global_class_limit_status($native_variable_counts['total'] ?? 0, $elementor_variable_limit);
        $elementor_total_class_count = $this->get_native_global_class_total_count();
        $elementor_existing_class_labels = $this->get_native_global_class_labels();
        $elementor_class_limit = $this->get_native_global_class_limit();
        $elementor_class_limit_status = $this->global_class_limit_status($elementor_total_class_count, $elementor_class_limit);
        $elementor_remaining_class_slots = max(0, 100 - $elementor_total_class_count);
        $starter_class_library = $this->starter_class_library();
        $starter_class_categories = $this->starter_class_category_labels();
        $starter_class_tabs = $this->starter_class_tab_groups();
        $starter_class_help_texts = $this->starter_class_tab_help_texts();
        $utility_class_library = $this->utility_class_library();
        $utility_class_categories = $this->utility_class_category_labels();
        $utility_class_help_texts = $this->utility_class_category_help_texts();
        $class_library_help_texts = $this->class_library_tab_help_texts();
        $cleanup_total_count = $cleanup_variable_count + $cleanup_class_count;
        $show_elementor_status_cards = !empty($settings['show_elementor_status_cards']);
        $root_base_px = $this->get_root_font_base_px($settings);
        $spacing_scale = $this->build_spacing_scale($settings['spacing'], $root_base_px);
        $spacing_preview = $this->build_spacing_scale_preview($settings['spacing'], $root_base_px);
        $type_scale_preview = $this->build_type_scale_preview($settings['typography']['scale'], $root_base_px);
        $type_root_preview = $this->find_preview_item_by_step($type_scale_preview, $settings['typography']['scale']['base_index'] ?? 'm');
        $spacing_root_preview = $this->find_preview_item_by_step($spacing_preview, $settings['spacing']['base_index'] ?? 'm');
        $radius_root_preview = $this->find_radius_preview_item($settings['radius'] ?? []);
        $type_preview_font = $this->resolved_base_font_family_css_value($settings);
        $changelog_entries = $this->get_localized_changelog_entries();
        $base_type_preview = $type_scale_preview[0] ?? ['min' => '1', 'max' => '1'];
        foreach ($type_scale_preview as $preview_item) {
            if (($preview_item['step'] ?? '') === ($settings['typography']['scale']['base_index'] ?? 'm')) {
                $base_type_preview = $preview_item;
                break;
            }
        }
        $sync_state = isset($_GET['ecf_sync']) ? sanitize_key($_GET['ecf_sync']) : '';
        ?>
        <div class="wrap ecf-wrap"
             data-ecf-admin-design="<?php echo esc_attr($this->selected_admin_design_preset($settings)); ?>"
             data-ecf-admin-mode="<?php echo esc_attr($this->selected_admin_design_mode($settings)); ?>"
             style="--ecf-admin-content-font-size: <?php echo esc_attr($this->selected_admin_content_font_size($settings)); ?>px; --ecf-admin-menu-font-size: <?php echo esc_attr($this->selected_admin_menu_font_size($settings)); ?>px;">

            <!-- Sidebar -->
            <aside class="ecf-sidebar">
                <div class="ecf-logo">
                    <h1><?php echo esc_html__('Layrix', 'ecf-framework'); ?></h1>
                    <p class="ecf-logo__byline"><?php echo esc_html__('by Alexander Kaiser', 'ecf-framework'); ?></p>
                    <button type="button" class="ecf-version-link" data-ecf-open-changelog-modal>
                        v<?php echo esc_html(get_plugin_data(__FILE__)['Version'] ?? '0.1'); ?>
                    </button>
                </div>
                <nav class="ecf-nav">
                    <div class="ecf-nav-section"><?php echo esc_html__('Design', 'ecf-framework'); ?></div>
                    <button class="ecf-nav-item is-active" data-panel="tokens"><span class="dashicons dashicons-art"></span><?php echo esc_html__('Colors & Radius', 'ecf-framework'); ?></button>
                    <button class="ecf-nav-item" data-panel="typography"><span class="dashicons dashicons-editor-textcolor"></span><?php echo esc_html__('Typography', 'ecf-framework'); ?></button>
                    <button class="ecf-nav-item" data-panel="spacing"><span class="dashicons dashicons-editor-table"></span><?php echo esc_html__('Spacing', 'ecf-framework'); ?></button>
                    <button class="ecf-nav-item" data-panel="shadows"><span class="dashicons dashicons-admin-appearance"></span><?php echo esc_html__('Shadows', 'ecf-framework'); ?></button>

                    <div class="ecf-nav-section"><?php echo esc_html__('Elementor', 'ecf-framework'); ?></div>
                    <button class="ecf-nav-item" data-panel="variables"><span class="dashicons dashicons-list-view"></span><?php echo esc_html__('Variables', 'ecf-framework'); ?></button>
                    <button class="ecf-nav-item" data-panel="utilities"><span class="dashicons dashicons-code-standards"></span><?php echo esc_html__('Classes', 'ecf-framework'); ?></button>
                    <button class="ecf-nav-item" data-panel="sync"><span class="dashicons dashicons-update"></span><?php echo esc_html__('Sync & Export', 'ecf-framework'); ?></button>

                    <div class="ecf-nav-section"><?php echo esc_html__('Settings', 'ecf-framework'); ?></div>
                    <button class="ecf-nav-item" data-panel="components" data-ecf-new-key="general-settings"><span class="dashicons dashicons-layout"></span><?php echo esc_html__('General Settings', 'ecf-framework'); ?><span class="ecf-unsaved-badge" data-ecf-unsaved-badge hidden><?php echo esc_html__('ungespeichert', 'ecf-framework'); ?></span></button>
                </nav>
                <div class="ecf-sidebar-footer">
                    <div class="ecf-sidebar-footer__links">
                        <button type="button" class="ecf-sidebar-link" data-panel="help"><?php echo esc_html__('Hilfe', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-sidebar-link" data-ecf-open-changelog-modal><?php echo esc_html__('Changelog', 'ecf-framework'); ?></button>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <main class="ecf-main">

            <?php if (isset($_GET['settings-updated']) && 'true' === sanitize_text_field(wp_unslash($_GET['settings-updated']))): ?>
                <div class="notice notice-success ecf-panel-notice ecf-panel-notice--success"><p><?php echo esc_html__('Settings saved.', 'ecf-framework'); ?></p></div>
            <?php endif; ?>

            <?php settings_errors('ecf_group'); ?>
            <?php $this->render_consumed_admin_notices('ecf_group', 'ecf-panel-notice'); ?>

            <form method="post" action="options.php">
                <?php settings_fields('ecf_group'); ?>
                <div class="ecf-sticky-topbar" data-ecf-sticky-topbar>
                    <div class="ecf-sticky-topbar__title-wrap">
                        <h2 class="ecf-sticky-topbar__title" data-ecf-active-panel-title><?php echo esc_html__('Colors & Radius', 'ecf-framework'); ?></h2>
                    </div>
                    <div class="ecf-sticky-topbar__actions">
                        <button type="button" class="ecf-btn ecf-btn--ghost ecf-sticky-topbar__reset-layout" data-ecf-reset-layout>
                            <span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
                            <span><?php echo esc_html__('Reset layout', 'ecf-framework'); ?></span>
                        </button>
                        <div class="ecf-sticky-topbar__autosave" data-ecf-autosave-control>
                            <button type="button" class="ecf-autosave-pill ecf-sticky-topbar__autosave-toggle" data-ecf-autosave-toggle aria-haspopup="true" aria-expanded="false">
                                <span data-ecf-autosave-pill><?php echo esc_html(!empty($settings['autosave_enabled']) ? __('Autosave active', 'ecf-framework') : __('Autosave off', 'ecf-framework')); ?></span>
                                <span class="ecf-sticky-topbar__autosave-arrow-hit" data-ecf-autosave-arrow-hit>
                                    <span class="dashicons dashicons-arrow-down-alt2 ecf-sticky-topbar__autosave-arrow" aria-hidden="true"></span>
                                </span>
                            </button>
                            <div class="ecf-sticky-topbar__autosave-menu" data-ecf-autosave-menu hidden>
                                <label class="ecf-sticky-topbar__autosave-option">
                                    <input type="checkbox" data-ecf-topbar-setting="elementor_auto_sync_enabled" <?php checked(!empty($settings['elementor_auto_sync_enabled'])); ?>>
                                    <span><?php echo esc_html__('Elementor auto-sync after autosave', 'ecf-framework'); ?></span>
                                </label>
                                <label class="ecf-sticky-topbar__autosave-option">
                                    <input type="checkbox" data-ecf-topbar-setting="elementor_auto_sync_variables" <?php checked(!empty($settings['elementor_auto_sync_variables'])); ?>>
                                    <span><?php echo esc_html__('Auto-sync variables', 'ecf-framework'); ?></span>
                                </label>
                                <label class="ecf-sticky-topbar__autosave-option">
                                    <input type="checkbox" data-ecf-topbar-setting="elementor_auto_sync_classes" <?php checked(!empty($settings['elementor_auto_sync_classes'])); ?>>
                                    <span><?php echo esc_html__('Auto-sync classes', 'ecf-framework'); ?></span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="ecf-btn ecf-btn--primary ecf-sticky-topbar__save">
                            <?php echo esc_html__('Save settings', 'ecf-framework'); ?>
                        </button>
                    </div>
                </div>

                <?php
                $this->render_variables_panel([
                    'show_elementor_status_cards' => $show_elementor_status_cards,
                    'elementor_variable_limit_status' => $elementor_variable_limit_status,
                    'native_variable_counts' => $native_variable_counts,
                    'elementor_variable_limit' => $elementor_variable_limit,
                ]);
                $this->render_tokens_panel($settings);
                ?>

                <?php
                $this->render_typography_panel([
                    'settings' => $settings,
                    'type_scale_preview' => $type_scale_preview,
                    'type_preview_font' => $type_preview_font,
                    'base_type_preview' => $base_type_preview,
                ]);
                $this->render_spacing_panel([
                    'settings' => $settings,
                    'spacing_preview' => $spacing_preview,
                ]);
                ?>

                <?php $this->render_shadows_panel($settings); ?>

                <?php
                $this->render_utilities_panel([
                    'settings' => $settings,
                    'elementor_class_limit' => $elementor_class_limit,
                    'elementor_total_class_count' => $elementor_total_class_count,
                    'elementor_existing_class_labels' => $elementor_existing_class_labels,
                    'elementor_class_limit_status' => $elementor_class_limit_status,
                    'class_library_help_texts' => $class_library_help_texts,
                    'starter_class_tabs' => $starter_class_tabs,
                    'starter_class_library' => $starter_class_library,
                    'starter_class_categories' => $starter_class_categories,
                    'utility_class_categories' => $utility_class_categories,
                    'utility_class_help_texts' => $utility_class_help_texts,
                    'utility_class_library' => $utility_class_library,
                ]);
                ?>

                <?php
                $this->render_components_panel([
                    'settings' => $settings,
                    'root_base_px' => $root_base_px,
                    'type_root_preview' => $type_root_preview,
                    'spacing_root_preview' => $spacing_root_preview,
                    'radius_root_preview' => $radius_root_preview,
                ]);
                ?>

                <div class="ecf-form-footer" id="ecf-save-footer">
                    <?php submit_button(__('Save settings', 'ecf-framework'), 'primary', 'submit', false); ?>
                </div>
            </form>
            <form id="ecf-clear-debug-history-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden>
                <?php wp_nonce_field('ecf_clear_debug_history'); ?>
                <input type="hidden" name="action" value="ecf_clear_debug_history">
            </form>

                <?php
                $this->render_sync_panel([
                    'cleanup_variable_count' => $cleanup_variable_count,
                    'cleanup_class_count' => $cleanup_class_count,
                    'cleanup_total_count' => $cleanup_total_count,
                    'show_elementor_status_cards' => $show_elementor_status_cards,
                    'elementor_class_limit_status' => $elementor_class_limit_status,
                    'elementor_class_limit' => $elementor_class_limit,
                    'elementor_total_class_count' => $elementor_total_class_count,
                ]);
                $this->render_help_panel($changelog_entries);
                $this->render_changelog_modal($changelog_entries);
                $this->render_row_templates($starter_class_categories);
                ?>
            </main>
        </div>
        <?php
    }

}
}

if (!isset($GLOBALS['ecf_framework_instance']) || !($GLOBALS['ecf_framework_instance'] instanceof ECF_Framework)) {
    $GLOBALS['ecf_framework_instance'] = new ECF_Framework();
}
