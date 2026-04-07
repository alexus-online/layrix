<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Elementor_Status_Trait {
    private function get_detected_elementor_limits() {
        $class_limit = 100;
        $class_source = __('ECF fallback', 'ecf-framework');

        if (class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_REST_API')) {
            $class_limit = (int) \Elementor\Modules\GlobalClasses\Global_Classes_REST_API::MAX_ITEMS;
            $class_source = __('Elementor Core', 'ecf-framework');
        }

        $variable_limit = 100;
        $variable_source = __('ECF fallback', 'ecf-framework');

        if (class_exists('\Elementor\Modules\Variables\Storage\Constants')) {
            $variable_limit = (int) \Elementor\Modules\Variables\Storage\Constants::TOTAL_VARIABLES_COUNT;
            $variable_source = __('Elementor Core', 'ecf-framework');
        }

        $filtered_class_limit = (int) apply_filters('ecf/elementor_global_class_limit', $class_limit);
        if ($filtered_class_limit !== $class_limit) {
            $class_source = __('ECF filter', 'ecf-framework');
        }

        $filtered_variable_limit = (int) apply_filters('ecf/elementor_global_variable_limit', $variable_limit);
        if ($filtered_variable_limit !== $variable_limit) {
            $variable_source = __('ECF filter', 'ecf-framework');
        }

        return [
            'classes' => max(1, $filtered_class_limit),
            'classes_source' => $class_source,
            'variables' => max(1, $filtered_variable_limit),
            'variables_source' => $variable_source,
        ];
    }

    private function get_native_global_class_limit() {
        $limits = $this->get_detected_elementor_limits();
        return (int) ($limits['classes'] ?? 100);
    }

    private function get_native_global_variable_limit() {
        $limits = $this->get_detected_elementor_limits();
        return (int) ($limits['variables'] ?? 100);
    }

    private function get_elementor_debug_snapshot() {
        $limits = $this->get_detected_elementor_limits();
        $core_recognized = defined('ELEMENTOR_VERSION') || did_action('elementor/loaded');
        $pro_recognized = defined('ELEMENTOR_PRO_VERSION') || did_action('elementor-pro/init');

        return [
            'core_recognized' => $core_recognized,
            'core_version' => defined('ELEMENTOR_VERSION') ? (string) ELEMENTOR_VERSION : '',
            'pro_recognized' => $pro_recognized,
            'pro_version' => defined('ELEMENTOR_PRO_VERSION') ? (string) ELEMENTOR_PRO_VERSION : '',
            'variables_active' => class_exists('\Elementor\Modules\Variables\Module'),
            'global_classes_active' => class_exists('\Elementor\Modules\GlobalClasses\Module'),
            'design_system_sync_active' => class_exists('\Elementor\Modules\DesignSystemSync\Module'),
            'classes_limit' => (int) ($limits['classes'] ?? 100),
            'classes_limit_source' => (string) ($limits['classes_source'] ?? ''),
            'variables_limit' => (int) ($limits['variables'] ?? 100),
            'variables_limit_source' => (string) ($limits['variables_source'] ?? ''),
        ];
    }

    private function get_elementor_limit_snapshot() {
        $limits = $this->get_detected_elementor_limits();
        $variable_counts = $this->get_native_variable_counts();

        return [
            'classes_total' => (int) $this->get_native_global_class_total_count(),
            'classes_limit' => (int) ($limits['classes'] ?? 100),
            'variables_total' => (int) ($variable_counts['total'] ?? 0),
            'variables_limit' => (int) ($limits['variables'] ?? 1000),
        ];
    }

    private function global_class_limit_status($count, $limit = 100) {
        $limit = max(1, (int) $limit);
        $count = max(0, (int) $count);
        $ratio = $count / $limit;

        if ($ratio >= 0.9) {
            return 'danger';
        }

        if ($ratio >= 0.7) {
            return 'warning';
        }

        return 'success';
    }
}
