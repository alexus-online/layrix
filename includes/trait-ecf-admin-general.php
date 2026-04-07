<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Admin_General_Trait {
    private function general_setting_favorite_keys() {
        return [
            'root_font_size',
            'interface_language',
            'admin_design_preset',
            'github_update_checks_enabled',
            'content_max_width',
            'elementor_boxed_width',
            'base_font_family',
            'base_body_text_size',
            'base_text_color',
            'base_background_color',
            'link_color',
            'focus_color',
            'show_elementor_status_cards',
            'elementor_variable_type_filter',
        ];
    }

    private function default_general_setting_favorites() {
        return [
            'root_font_size' => '1',
            'interface_language' => '1',
            'admin_design_preset' => '1',
            'content_max_width' => '1',
            'elementor_boxed_width' => '1',
            'base_font_family' => '1',
            'base_body_text_size' => '1',
            'base_text_color' => '1',
            'github_update_checks_enabled' => '1',
            'show_elementor_status_cards' => '1',
            'elementor_variable_type_filter' => '1',
        ];
    }

    private function is_general_setting_favorite($settings, $key) {
        return !empty($settings['general_setting_favorites'][$key]);
    }

    private function render_general_setting_favorite_toggle($settings, $key) {
        $is_favorite = $this->is_general_setting_favorite($settings, $key);
        $tip_add = __('Add to Favorites', 'ecf-framework');
        $tip_added = __('Already in Favorites', 'ecf-framework');
        ?>
        <label class="ecf-favorite-toggle"
               data-tip="<?php echo esc_attr($is_favorite ? $tip_added : $tip_add); ?>"
               data-tip-off="<?php echo esc_attr($tip_add); ?>"
               data-tip-on="<?php echo esc_attr($tip_added); ?>"
               aria-label="<?php echo esc_attr($is_favorite ? $tip_added : $tip_add); ?>">
            <input type="checkbox"
                   name="<?php echo esc_attr($this->option_name); ?>[general_setting_favorites][<?php echo esc_attr($key); ?>]"
                   value="1"
                   data-ecf-general-favorite-toggle
                   data-ecf-favorite-key="<?php echo esc_attr($key); ?>"
                   <?php checked($is_favorite); ?>>
            <span class="ecf-favorite-toggle__icon" aria-hidden="true"><?php echo $is_favorite ? '♥' : '♡'; ?></span>
            <span class="screen-reader-text"><?php echo esc_html__('Favorite', 'ecf-framework'); ?></span>
        </label>
        <?php
    }

    private function general_setting_label($label, $tip, $icon = 'admin-generic') {
        return sprintf(
            '<span class="ecf-tip-hover ecf-general-setting-label" data-tip="%1$s"><span class="ecf-general-setting-label__icon dashicons dashicons-%2$s" aria-hidden="true"></span><span class="ecf-general-setting-label__text">%3$s</span></span>',
            esc_attr(__($tip, 'ecf-framework')),
            esc_attr($icon),
            esc_html($label)
        );
    }

    private function general_setting_favorite_definitions($settings) {
        $root_base_px = $this->get_root_font_base_px($settings);
        $base_font_options = $this->base_font_family_options($settings);
        $base_font_value = (string) ($settings['base_font_family'] ?? 'var(--ecf-font-primary)');
        $base_font_label = $base_font_options[$base_font_value] ?? $base_font_value;

        return [
            'root_font_size' => [
                'group' => 'website',
                'tab' => 'system',
                'title' => __('Root Font Size', 'ecf-framework'),
                'value' => sprintf(__('%s%% (%spx = 1rem)', 'ecf-framework'), str_replace('.', ',', (string) ($settings['root_font_size'] ?? '62.5')), $this->format_preview_number($root_base_px)),
            ],
            'github_update_checks_enabled' => [
                'group' => 'plugin',
                'tab' => 'system',
                'title' => __('GitHub update checks', 'ecf-framework'),
                'value' => !empty($settings['github_update_checks_enabled']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
            'interface_language' => [
                'group' => 'plugin',
                'tab' => 'system',
                'title' => __('Plugin Language', 'ecf-framework'),
                'value' => $this->selected_interface_language($settings) === 'de'
                    ? __('German', 'ecf-framework')
                    : __('English', 'ecf-framework'),
            ],
            'admin_design_preset' => [
                'group' => 'plugin',
                'tab' => 'system',
                'title' => __('Design', 'ecf-framework'),
                'value' => sprintf(
                    __('%1$s / %2$s', 'ecf-framework'),
                    $this->admin_design_preset_options()[$this->selected_admin_design_preset($settings)] ?? __('Current design', 'ecf-framework'),
                    $this->selected_admin_design_mode($settings) === 'light'
                        ? __('White mode', 'ecf-framework')
                        : __('Dark mode', 'ecf-framework')
                ),
            ],
            'content_max_width' => [
                'group' => 'website',
                'tab' => 'layout',
                'title' => __('Content Max Width', 'ecf-framework'),
                'value' => (string) ($settings['content_max_width'] ?? '72ch'),
            ],
            'elementor_boxed_width' => [
                'group' => 'website',
                'tab' => 'layout',
                'title' => __('Elementor Boxed Width', 'ecf-framework'),
                'value' => (string) ($settings['elementor_boxed_width'] ?? '1140px'),
            ],
            'base_font_family' => [
                'group' => 'website',
                'tab' => 'typography',
                'title' => __('Base Font Family', 'ecf-framework'),
                'value' => $base_font_label,
            ],
            'base_body_text_size' => [
                'group' => 'website',
                'tab' => 'typography',
                'title' => __('Base Body Text Size', 'ecf-framework'),
                'value' => (string) ($settings['base_body_text_size'] ?? '16px'),
            ],
            'base_text_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Base Text Color', 'ecf-framework'),
                'value' => (string) ($settings['base_text_color'] ?? '#111827'),
            ],
            'base_background_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Base Background Color', 'ecf-framework'),
                'value' => (string) ($settings['base_background_color'] ?? '#ffffff'),
            ],
            'link_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Link Color', 'ecf-framework'),
                'value' => (string) ($settings['link_color'] ?? '#3b82f6'),
            ],
            'focus_color' => [
                'group' => 'website',
                'tab' => 'colors',
                'title' => __('Focus Color', 'ecf-framework'),
                'value' => (string) ($settings['focus_color'] ?? '#6366f1'),
            ],
            'show_elementor_status_cards' => [
                'group' => 'plugin',
                'tab' => 'behavior',
                'title' => __('Status cards in Variables & Sync', 'ecf-framework'),
                'value' => !empty($settings['show_elementor_status_cards']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
            'elementor_variable_type_filter' => [
                'group' => 'plugin',
                'tab' => 'behavior',
                'title' => __('Filter variables by field type', 'ecf-framework'),
                'value' => !empty($settings['elementor_variable_type_filter']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework'),
            ],
        ];
    }

    private function general_setting_favorite_sort_order() {
        return [
            'root_font_size' => 10,
            'base_body_text_size' => 20,
            'base_font_family' => 30,
            'base_text_color' => 40,
            'base_background_color' => 50,
            'link_color' => 60,
            'focus_color' => 70,
            'content_max_width' => 80,
            'elementor_boxed_width' => 90,
            'interface_language' => 110,
            'admin_design_preset' => 120,
            'github_update_checks_enabled' => 130,
            'show_elementor_status_cards' => 140,
            'elementor_variable_type_filter' => 150,
        ];
    }

    private function sort_general_setting_favorite_definitions($definitions) {
        $order = $this->general_setting_favorite_sort_order();

        uasort($definitions, function ($left, $right) use ($order) {
            $left_key = $left['_favorite_key'] ?? '';
            $right_key = $right['_favorite_key'] ?? '';
            $left_order = $order[$left_key] ?? 999;
            $right_order = $order[$right_key] ?? 999;

            if ($left_order === $right_order) {
                return strcmp($left_key, $right_key);
            }

            return $left_order <=> $right_order;
        });

        return $definitions;
    }

    private function render_general_favorites_section($settings) {
        $definitions = [];
        foreach ($this->general_setting_favorite_definitions($settings) as $favorite_key => $definition) {
            $definition['_favorite_key'] = $favorite_key;
            $definitions[$favorite_key] = $definition;
        }
        $definitions = $this->sort_general_setting_favorite_definitions($definitions);
        $group_labels = [
            'website' => __('Website', 'ecf-framework'),
            'plugin'  => __('Plugin', 'ecf-framework'),
        ];
        ?>
        <div class="ecf-general-favorites" data-ecf-general-favorites>
            <p class="ecf-muted-copy"><?php echo esc_html__('Your pinned quick settings. Use the star icon on any supported setting to add or remove it here.', 'ecf-framework'); ?></p>
            <div class="ecf-general-favorites__empty" data-ecf-general-favorites-empty hidden>
                <?php echo esc_html__('No favorites selected yet. Mark important settings with the heart icon.', 'ecf-framework'); ?>
            </div>
            <?php foreach ($group_labels as $group_key => $group_label): ?>
                <div class="ecf-general-favorites__group" data-ecf-general-favorites-group="<?php echo esc_attr($group_key); ?>">
                    <div class="ecf-vargroup-header">
                        <h3><?php echo esc_html($group_label); ?></h3>
                    </div>
                    <div class="ecf-general-favorites__grid">
                        <?php foreach ($definitions as $favorite_key => $definition): ?>
                            <?php if (($definition['group'] ?? '') !== $group_key) { continue; } ?>
                            <div class="ecf-general-favorite-card"
                                 data-ecf-favorite-card="<?php echo esc_attr($favorite_key); ?>"
                                 <?php echo $this->is_general_setting_favorite($settings, $favorite_key) ? '' : 'hidden'; ?>>
                                <div class="ecf-general-favorite-card__top">
                                    <strong><?php echo esc_html($definition['title']); ?></strong>
                                </div>
                                <div class="ecf-general-favorite-card__editor">
                                    <?php $this->render_general_favorite_editor($settings, $favorite_key); ?>
                                </div>
                                <div class="ecf-general-favorite-card__meta"><?php echo esc_html($definition['value']); ?></div>
                                <div class="ecf-general-favorite-card__remove-row">
                                    <span class="ecf-general-favorite-card__remove-label"><?php echo esc_html__('Remove from favorites', 'ecf-framework'); ?></span>
                                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--tiny" data-ecf-favorite-remove="<?php echo esc_attr($favorite_key); ?>" title="<?php echo esc_attr__('Remove from favorites', 'ecf-framework'); ?>">
                                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_root_font_size_select($settings, $canonical = false) {
        $value = (string) ($settings['root_font_size'] ?? '62.5');
        $root_base_px = $this->get_root_font_base_px($settings);
        $name_attr = $canonical ? ' name="' . esc_attr($this->option_name) . '[root_font_size]"' : '';
        $sync_attr = $canonical ? ' data-ecf-root-font-source="1"' : ' data-ecf-root-font-mirror="1"';
        ?>
        <label class="ecf-root-font-select" data-ecf-general-field="root_font_size">
            <span class="ecf-root-font-select__label">
                <?php echo $this->general_setting_label(__('Root Font Size', 'ecf-framework'), 'Base rem size used for token conversion. Choose 100% for 16px = 1rem or 62.5% for 10px = 1rem.', 'editor-textcolor'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'root_font_size'); ?>
            </span>
            <span class="ecf-root-font-select__control">
                <select<?php echo $name_attr; ?><?php echo $sync_attr; ?>>
                    <option value="62.5" <?php selected($value, '62.5'); ?>>62,5%</option>
                    <option value="100" <?php selected($value, '100'); ?>>100%</option>
                </select>
                <span class="ecf-root-font-select__meta" data-ecf-root-font-inline><?php echo esc_html(sprintf(__('%spx = 1rem', 'ecf-framework'), $this->format_preview_number($root_base_px))); ?></span>
            </span>
        </label>
        <?php
    }

    private function render_interface_language_field($settings) {
        $current = $this->selected_interface_language($settings);
        $wp_default = $this->wordpress_default_interface_language();
        ?>
        <label data-ecf-general-field="interface_language">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Plugin Language', 'ecf-framework'), 'Controls the ECF interface language. The initial selection follows the current WordPress language setting.', 'translation'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'interface_language'); ?>
            </span>
            <select name="<?php echo esc_attr($this->option_name); ?>[interface_language]" class="ecf-general-favorite-input">
                <option value="de" <?php selected($current, 'de'); ?>>
                    <?php echo esc_html($wp_default === 'de' ? __('German (matches current WordPress language)', 'ecf-framework') : __('German', 'ecf-framework')); ?>
                </option>
                <option value="en" <?php selected($current, 'en'); ?>>
                    <?php echo esc_html($wp_default === 'en' ? __('English (matches current WordPress language)', 'ecf-framework') : __('English', 'ecf-framework')); ?>
                </option>
            </select>
        </label>
        <?php
    }

    private function admin_design_preset_definitions() {
        return [
            'current' => [
                'label' => __('Current design', 'ecf-framework'),
                'description' => __('Keeps the existing ECF look exactly as it is right now.', 'ecf-framework'),
                'preview' => 'current',
            ],
            'hero' => [
                'label' => __('Hero', 'ecf-framework'),
                'description' => __('Bold purple product UI inspired by modern component systems.', 'ecf-framework'),
                'preview' => 'hero',
            ],
            'next' => [
                'label' => __('Next', 'ecf-framework'),
                'description' => __('Clean slate interface with crisp contrast and restrained accents.', 'ecf-framework'),
                'preview' => 'next',
            ],
            'untitled' => [
                'label' => __('Untitled', 'ecf-framework'),
                'description' => __('Soft editorial workspace with calm surfaces and gentle blue structure.', 'ecf-framework'),
                'preview' => 'untitled',
            ],
            'minimal' => [
                'label' => __('Minimal', 'ecf-framework'),
                'description' => __('Reduced monochrome admin look with subtle borders and quiet emphasis.', 'ecf-framework'),
                'preview' => 'minimal',
            ],
        ];
    }

    private function normalize_admin_design_preset($preset) {
        $preset = sanitize_key((string) $preset);
        $aliases = [
            'graphite' => 'next',
            'ocean' => 'untitled',
            'aurora' => 'hero',
        ];

        if (isset($aliases[$preset])) {
            $preset = $aliases[$preset];
        }

        return array_key_exists($preset, $this->admin_design_preset_definitions()) ? $preset : 'current';
    }

    private function admin_design_preset_options() {
        $options = [];
        foreach ($this->admin_design_preset_definitions() as $key => $definition) {
            $options[$key] = $definition['label'];
        }

        return $options;
    }

    private function selected_admin_design_preset($settings = null) {
        $defaults = $this->defaults();
        $current = is_array($settings)
            ? sanitize_key($settings['admin_design_preset'] ?? $defaults['admin_design_preset'])
            : sanitize_key((string) ($this->get_settings()['admin_design_preset'] ?? $defaults['admin_design_preset']));

        return $this->normalize_admin_design_preset($current ?: $defaults['admin_design_preset']);
    }

    private function selected_admin_design_mode($settings = null) {
        $defaults = $this->defaults();
        $current = is_array($settings)
            ? sanitize_key($settings['admin_design_mode'] ?? $defaults['admin_design_mode'])
            : sanitize_key((string) ($this->get_settings()['admin_design_mode'] ?? $defaults['admin_design_mode']));

        return in_array($current, ['dark', 'light'], true) ? $current : $defaults['admin_design_mode'];
    }

    private function render_admin_design_field($settings) {
        $current_preset = $this->selected_admin_design_preset($settings);
        $current_mode = $this->selected_admin_design_mode($settings);
        $preset_definitions = $this->admin_design_preset_definitions();
        ?>
        <div class="ecf-admin-design-field" data-ecf-general-field="admin_design_preset">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Design', 'ecf-framework'), 'Choose the admin look of ECF. Current design keeps the existing appearance; other presets restyle the interface.', 'art'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'admin_design_preset'); ?>
            </span>
            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[admin_design_preset]" value="<?php echo esc_attr($current_preset); ?>" data-ecf-admin-design-preset>
            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[admin_design_mode]" value="<?php echo esc_attr($current_mode); ?>" data-ecf-admin-design-mode>
            <div class="ecf-admin-design-grid" data-ecf-admin-design-grid>
                <?php foreach ($preset_definitions as $value => $definition): ?>
                    <button type="button"
                            class="ecf-admin-design-card<?php echo $current_preset === $value ? ' is-active' : ''; ?>"
                            data-ecf-admin-design-option
                            data-value="<?php echo esc_attr($value); ?>"
                            data-preview="<?php echo esc_attr($definition['preview']); ?>">
                        <span class="ecf-admin-design-card__preview" aria-hidden="true">
                            <span class="ecf-admin-design-card__preview-window">
                                <span class="ecf-admin-design-card__preview-topbar"></span>
                                <span class="ecf-admin-design-card__preview-sidebar"></span>
                                <span class="ecf-admin-design-card__preview-panel"></span>
                                <span class="ecf-admin-design-card__preview-accent"></span>
                                <span class="ecf-admin-design-card__preview-chip"></span>
                            </span>
                        </span>
                        <span class="ecf-admin-design-card__body">
                            <strong><?php echo esc_html($definition['label']); ?></strong>
                            <span><?php echo esc_html($definition['description']); ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="ecf-admin-design-mode" data-ecf-admin-design-mode-group>
                <span class="ecf-admin-design-mode__label"><?php echo esc_html__('Mode', 'ecf-framework'); ?></span>
                <div class="ecf-admin-design-mode__options">
                    <button type="button"
                            class="ecf-admin-design-mode__option<?php echo $current_mode === 'dark' ? ' is-active' : ''; ?>"
                            data-ecf-admin-design-mode-option
                            data-value="dark">
                        <?php echo esc_html__('Dark mode', 'ecf-framework'); ?>
                    </button>
                    <button type="button"
                            class="ecf-admin-design-mode__option<?php echo $current_mode === 'light' ? ' is-active' : ''; ?>"
                            data-ecf-admin-design-mode-option
                            data-value="light">
                        <?php echo esc_html__('White mode', 'ecf-framework'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_general_color_field($settings, $key, $label_en, $label_de, $tip_en, $tip_de, $icon = 'admin-appearance') {
        $value = (string) ($settings[$key] ?? '');
        ?>
        <label data-ecf-general-field="<?php echo esc_attr($key); ?>">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__($label_en, 'ecf-framework'), $tip_en, $icon); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, $key); ?>
            </span>
            <input type="text" class="ecf-color-input" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" data-default-color="<?php echo esc_attr($value); ?>">
        </label>
        <?php
    }

    private function boxed_format_options() {
        return [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Example: 1140 becomes 1140px.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value. Example: 90 becomes 90%.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Example: 72 becomes 72rem.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Example: 72 becomes 72em.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Example: 90 becomes 90vw.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Example: 80 becomes 80vh.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(100% - 2rem, 1140px), calc(...) or clamp(...).', 'ecf-framework')],
        ];
    }

    private function content_format_options() {
        return [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Good for strict content widths like 720px.', 'ecf-framework')],
            'ch'     => ['label' => 'ch',  'tip' => __('Character-based width. Great for readable text columns like 65ch or 72ch.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value if the content width should stay fluid.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Useful if content width should scale with your root font size.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Rarely needed, but possible for content wrappers.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Useful for fluid readable widths.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Usually uncommon here, but available if needed.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(72ch, 100% - 2rem), calc(...) or clamp(...).', 'ecf-framework')],
        ];
    }

    private function body_text_size_format_options() {
        return [
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Best default for body text like 1rem or 1.125rem.', 'ecf-framework')],
            'px'     => ['label' => 'px',  'tip' => __('Fixed pixel value. Useful if you need a strict size like 16px or 18px.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Rarely needed for body text, but available if you want it.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value relative to the inherited font size.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like clamp(1rem, 0.95rem + 0.2vw, 1.125rem).', 'ecf-framework')],
        ];
    }

    private function render_general_size_field_inline($settings, $field_key, $stored_value, $options, $default_format, $placeholder, $title) {
        $parts = $this->parse_css_size_parts($stored_value);
        $selected_format = isset($options[$parts['format']]) ? $parts['format'] : $default_format;
        ?>
        <div class="ecf-inline-size-input ecf-inline-size-input--favorite">
            <input type="text"
                   name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field_key); ?>_value]"
                   value="<?php echo esc_attr($parts['value']); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>"
                   title="<?php echo esc_attr($title); ?>">
            <div class="ecf-format-picker" data-ecf-format-picker>
                <input type="hidden"
                       name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field_key); ?>_format]"
                       value="<?php echo esc_attr($selected_format); ?>"
                       data-ecf-format-input>
                <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false">
                    <span data-ecf-format-current><?php echo esc_html($options[$selected_format]['label']); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
                <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                    <div class="ecf-format-picker__options">
                        <?php foreach ($options as $format_value => $format_config): ?>
                            <button type="button"
                                    class="ecf-format-picker__option<?php echo $format_value === $selected_format ? ' is-active' : ''; ?>"
                                    data-ecf-format-option
                                    data-value="<?php echo esc_attr($format_value); ?>"
                                    data-label="<?php echo esc_attr($format_config['label']); ?>"
                                    data-tip="<?php echo esc_attr($format_config['tip']); ?>">
                                <?php echo esc_html($format_config['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_general_favorite_editor($settings, $key) {
        switch ($key) {
            case 'root_font_size':
                ?>
                <select name="<?php echo esc_attr($this->option_name); ?>[root_font_size]" data-ecf-root-font-mirror="1" class="ecf-general-favorite-input">
                    <option value="62.5" <?php selected((string) ($settings['root_font_size'] ?? '62.5'), '62.5'); ?>>62,5%</option>
                    <option value="100" <?php selected((string) ($settings['root_font_size'] ?? '62.5'), '100'); ?>>100%</option>
                </select>
                <?php
                break;
            case 'interface_language':
                $this->render_interface_language_field($settings);
                break;
            case 'admin_design_preset':
                $this->render_admin_design_field($settings);
                break;
            case 'github_update_checks_enabled':
                ?>
                <label class="ecf-form-grid__checkbox ecf-form-grid__checkbox--favorite">
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->option_name); ?>[github_update_checks_enabled]"
                           value="1"
                           <?php checked(!empty($settings['github_update_checks_enabled'])); ?>>
                    <span><?php echo esc_html(!empty($settings['github_update_checks_enabled']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework')); ?></span>
                </label>
                <?php
                break;
            case 'content_max_width':
                $this->render_general_size_field_inline(
                    $settings,
                    'content_max_width',
                    $settings['content_max_width'] ?? '72ch',
                    $this->content_format_options(),
                    'ch',
                    '72 oder min(72ch, 100% - 2rem)',
                    __('Readable width for text/content areas.', 'ecf-framework')
                );
                break;
            case 'elementor_boxed_width':
                $this->render_general_size_field_inline(
                    $settings,
                    'elementor_boxed_width',
                    $settings['elementor_boxed_width'] ?? '1140px',
                    $this->boxed_format_options(),
                    'px',
                    '1140 oder clamp(20rem, 80vw, 1140px)',
                    __('Width of centered boxed layout containers.', 'ecf-framework')
                );
                break;
            case 'base_font_family':
                $this->render_base_font_family_field($settings);
                break;
            case 'base_body_text_size':
                $this->render_base_body_text_size_field($settings);
                break;
            case 'base_text_color':
            case 'base_background_color':
            case 'link_color':
            case 'focus_color':
                ?>
                <input type="text"
                       class="ecf-color-input ecf-general-favorite-input"
                       name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
                       value="<?php echo esc_attr((string) ($settings[$key] ?? '')); ?>"
                       data-default-color="<?php echo esc_attr((string) ($settings[$key] ?? '')); ?>">
                <?php
                break;
            case 'show_elementor_status_cards':
            case 'elementor_variable_type_filter':
                ?>
                <label class="ecf-form-grid__checkbox ecf-form-grid__checkbox--favorite">
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($key); ?>]"
                           value="1"
                           <?php checked(!empty($settings[$key])); ?>>
                    <span><?php echo esc_html(!empty($settings[$key]) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework')); ?></span>
                </label>
                <?php
                break;
        }
    }

    private function base_font_family_options($settings) {
        $options = [
            'var(--ecf-font-primary)' => __('Primary', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][0]['value'] ?? 'Inter, sans-serif'),
            'var(--ecf-font-secondary)' => __('Secondary', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][1]['value'] ?? 'Georgia, serif'),
            'var(--ecf-font-mono)' => __('Mono', 'ecf-framework') . ': ' . ($settings['typography']['fonts'][2]['value'] ?? 'JetBrains Mono, monospace'),
        ];

        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family === '') {
                continue;
            }
            $options["'" . $family . "'"] = __('Uploaded font', 'ecf-framework') . ': ' . $family;
        }

        return $options;
    }

    private function render_base_font_family_field($settings) {
        $current = (string) ($settings['base_font_family'] ?? 'var(--ecf-font-primary)');
        $options = $this->base_font_family_options($settings);
        $is_custom = !isset($options[$current]);
        $current_local_family = '';
        foreach ((array) ($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family !== '' && ("'" . $family . "'") === $current) {
                $current_local_family = $family;
                break;
            }
        }
        ?>
        <label data-ecf-general-field="base_font_family">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Base Font Family', 'ecf-framework'), 'Base font stack applied to the whole site body. Choose one of your saved stacks or a locally uploaded font. Use Custom only for a special free text stack.', 'editor-textcolor'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'base_font_family'); ?>
            </span>
            <div class="ecf-form-grid ecf-form-grid--single">
                <select name="<?php echo esc_attr($this->option_name); ?>[base_font_family_preset]" data-ecf-base-font-preset>
                    <?php foreach ($options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected(!$is_custom && $current === $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                    <option value="__custom__" <?php selected($is_custom); ?>><?php echo esc_html__('Custom stack', 'ecf-framework'); ?></option>
                </select>
                <input type="text" name="<?php echo esc_attr($this->option_name); ?>[base_font_family_custom]" value="<?php echo esc_attr($is_custom ? $current : ''); ?>" placeholder="Inter, sans-serif" data-ecf-base-font-custom <?php echo $is_custom ? '' : 'hidden'; ?>>
            </div>
            <div class="ecf-inline-actions ecf-inline-actions--fonts">
                <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--tiny" data-ecf-local-font-add>
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <span><?php echo esc_html__('Add local font', 'ecf-framework'); ?></span>
                </button>
                <?php if ($current_local_family !== ''): ?>
                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--tiny" data-ecf-local-font-remove="<?php echo esc_attr($current_local_family); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                        <span><?php echo esc_html__('Remove selected local font', 'ecf-framework'); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </label>
        <?php
    }

    private function render_base_body_text_size_field($settings) {
        $stored_value = (string) ($settings['base_body_text_size'] ?? '16px');
        ?>
        <label data-ecf-general-field="base_body_text_size" class="ecf-general-field ecf-general-field--body-size">
            <span class="ecf-general-label-with-favorite">
                <?php echo $this->general_setting_label(__('Base Body Text Size', 'ecf-framework'), 'Default font size for normal paragraph text across the site. This sets the body text baseline independently from your token scale.', 'editor-paragraph'); ?>
                <?php $this->render_general_setting_favorite_toggle($settings, 'base_body_text_size'); ?>
            </span>
            <?php
            $this->render_general_size_field_inline(
                $settings,
                'base_body_text_size',
                $stored_value,
                $this->body_text_size_format_options(),
                'px',
                '16 oder clamp(16px, 15px + 0.2vw, 18px)',
                __('Default body text size for regular paragraphs and flowing content.', 'ecf-framework')
            );
            ?>
            <p class="ecf-muted-copy"><?php echo wp_kses(sprintf(__('Sets the token <code>%s</code> for regular body text.', 'ecf-framework'), '--ecf-base-body-text-size'), ['code' => []]); ?></p>
        </label>
        <?php
    }
}
