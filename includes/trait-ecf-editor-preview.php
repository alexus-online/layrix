<?php

trait ECF_Framework_Editor_Preview_Trait {
    private function format_preview_number($number, $precision = 2) {
        $formatted = number_format((float) $number, $precision, '.', '');
        $trimmed   = rtrim(rtrim($formatted, '0'), '.');
        // rtrim('0.000', '0') → '0.' → rtrim('.', '.') → '' for zero values.
        // Returning '' produces invalid CSS like '--ecf-space-s:px;' (Elementor
        // then registers the variable with an empty value). Always emit at least
        // '0' so consumers get a parseable size like '0px'.
        return $trimmed === '' ? '0' : $trimmed;
    }

    private function build_type_scale_preview($scale, $root_base_px = 16) {
        $steps = $scale['steps'];
        $min_base = floatval($scale['min_base'] ?? ($scale['max_base'] ?? 16) * floatval($scale['scale_factor'] ?? 0.8));
        $max_base = floatval($scale['max_base'] ?? $scale['base'] ?? 16);
        $min_ratio = floatval($scale['min_ratio'] ?? $scale['ratio'] ?? 1.125);
        $max_ratio = floatval($scale['max_ratio'] ?? $scale['ratio'] ?? 1.25);
        $base_index = array_search($scale['base_index'], $steps, true);
        if ($base_index === false) {
            $base_index = 2;
        }
        $fluid = !empty($scale['fluid']);
        $min_vw = intval($scale['min_vw']);
        $max_vw = intval($scale['max_vw']);

        $result = [];
        foreach ($steps as $i => $step) {
            $exp = $i - $base_index;
            $max_size = round($max_base * pow($max_ratio, $exp), 3);
            $min_size = round($min_base * pow($min_ratio, $exp), 3);
            if ($min_size > $max_size) { [$min_size, $max_size] = [$max_size, $min_size]; }

            if ($fluid && $max_vw > $min_vw) {
                $css_value = $this->build_fluid_rem_clamp($min_size, $max_size, $min_vw, $max_vw, $root_base_px);
            } else {
                $min_size = $max_size;
                $css_value = $this->format_preview_number($max_size, 3) . 'px';
            }

            $result[] = [
                'step' => $step,
                'token' => '--ecf-text-' . $step,
                'css_value' => $css_value,
                'min' => $this->format_preview_number($this->format_rem_value($min_size, 2, $root_base_px)),
                'max' => $this->format_preview_number($this->format_rem_value($max_size, 2, $root_base_px)),
                'min_px' => $this->format_preview_number($min_size, 3),
                'max_px' => $this->format_preview_number($max_size, 3),
            ];
        }

        return $result;
    }

    private function font_format_from_url($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        $formats = [
            'woff2' => 'woff2',
            'woff' => 'woff',
            'ttf' => 'truetype',
            'otf' => 'opentype',
        ];
        return $formats[$ext] ?? '';
    }

    private function render_local_font_rows($rows, $input_key) {
        echo '<div class="ecf-font-file-table" data-local-font-table data-input-key="' . esc_attr($input_key) . '">';
        echo '<div class="ecf-font-file-head"><span>' . esc_html__('Key', 'ecf-framework') . '</span><span>' . esc_html__('Family', 'ecf-framework') . '</span><span>' . esc_html__('File URL', 'ecf-framework') . '</span><span>' . esc_html__('Weight', 'ecf-framework') . '</span><span>' . esc_html__('Style', 'ecf-framework') . '</span><span>' . esc_html__('Display', 'ecf-framework') . '</span><span></span></div>';
        foreach ($rows as $i => $row) {
            echo '<div class="ecf-font-file-row">';
            echo '<input type="text" data-ecf-slug-field="token" name="' . esc_attr($input_key . '[' . $i . '][name]') . '" value="' . esc_attr($row['name'] ?? '') . '" placeholder="' . esc_attr__('primary-regular', 'ecf-framework') . '" />';
            echo '<input type="text" name="' . esc_attr($input_key . '[' . $i . '][family]') . '" value="' . esc_attr($row['family'] ?? '') . '" placeholder="' . esc_attr__('Primary', 'ecf-framework') . '" />';
            echo '<div class="ecf-font-file-picker">';
            echo '<input type="text" class="ecf-font-file-url" name="' . esc_attr($input_key . '[' . $i . '][src]') . '" value="' . esc_attr($row['src'] ?? '') . '" placeholder="' . esc_attr__('Select a local upload', 'ecf-framework') . '" readonly />';
            echo '<button type="button" class="button ecf-font-file-select">' . esc_html__('Select file', 'ecf-framework') . '</button>';
            echo '</div>';
            echo '<input type="text" name="' . esc_attr($input_key . '[' . $i . '][weight]') . '" value="' . esc_attr($row['weight'] ?? '400') . '" placeholder="400" />';
            echo '<select name="' . esc_attr($input_key . '[' . $i . '][style]') . '">';
            $style_labels = [
                'normal' => __('normal', 'ecf-framework'),
                'italic' => __('italic', 'ecf-framework'),
                'oblique' => __('oblique', 'ecf-framework'),
            ];
            foreach ($style_labels as $style => $style_label) {
                echo '<option value="' . esc_attr($style) . '" ' . selected($row['style'] ?? 'normal', $style, false) . '>' . esc_html($style_label) . '</option>';
            }
            echo '</select>';
            echo '<select name="' . esc_attr($input_key . '[' . $i . '][display]') . '">';
            $display_labels = [
                'swap' => __('swap', 'ecf-framework'),
                'fallback' => __('fallback', 'ecf-framework'),
                'optional' => __('optional', 'ecf-framework'),
                'block' => __('block', 'ecf-framework'),
                'auto' => __('auto', 'ecf-framework'),
            ];
            foreach ($display_labels as $display => $display_label) {
                echo '<option value="' . esc_attr($display) . '" ' . selected($row['display'] ?? 'swap', $display, false) . '>' . esc_html($display_label) . '</option>';
            }
            echo '</select>';
            echo '<button type="button" class="button ecf-remove-row">×</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button button-secondary ecf-add-local-font">' . esc_html__('Add local font file', 'ecf-framework') . '</button>';
    }

    private function render_imported_local_font_rows($rows, $input_key) {
        echo '<div class="ecf-font-file-table ecf-font-file-table--imported" data-local-font-table data-input-key="' . esc_attr($input_key) . '">';
        if (empty($rows)) {
            echo '<p class="ecf-muted-copy">' . esc_html__('No local fonts imported yet. Import a font from the Body or Heading selectors above and it will appear here.', 'ecf-framework') . '</p>';
        }
        foreach ($rows as $i => $row) {
            $family = (string) ($row['family'] ?? '');
            $src = (string) ($row['src'] ?? '');
            $file_label = wp_basename(wp_parse_url($src, PHP_URL_PATH) ?: $src);
            echo '<div class="ecf-font-file-row ecf-font-file-row--imported">';
            echo '<input type="hidden" name="' . esc_attr($input_key . '[' . $i . '][name]') . '" value="' . esc_attr($row['name'] ?? '') . '" />';
            echo '<input type="hidden" name="' . esc_attr($input_key . '[' . $i . '][family]') . '" value="' . esc_attr($family) . '" />';
            echo '<input type="hidden" class="ecf-font-file-url" name="' . esc_attr($input_key . '[' . $i . '][src]') . '" value="' . esc_attr($src) . '" />';
            echo '<input type="hidden" name="' . esc_attr($input_key . '[' . $i . '][weight]') . '" value="' . esc_attr($row['weight'] ?? '400') . '" />';
            echo '<input type="hidden" name="' . esc_attr($input_key . '[' . $i . '][style]') . '" value="' . esc_attr($row['style'] ?? 'normal') . '" />';
            echo '<input type="hidden" name="' . esc_attr($input_key . '[' . $i . '][display]') . '" value="' . esc_attr($row['display'] ?? 'swap') . '" />';
            echo '<div class="ecf-font-imported-summary">';
            echo '<strong>' . esc_html($family) . '</strong>';
            echo '<span>' . esc_html($file_label !== '' ? $file_label : __('Local media file', 'ecf-framework')) . '</span>';
            echo '</div>';
            echo '<div class="ecf-font-imported-meta">';
            echo '<span>' . esc_html(sprintf(__('Weight: %s', 'ecf-framework'), (string) ($row['weight'] ?? '400'))) . '</span>';
            echo '<span>' . esc_html(sprintf(__('Style: %s', 'ecf-framework'), (string) ($row['style'] ?? 'normal'))) . '</span>';
            echo '</div>';
            echo '<button type="button" class="button ecf-remove-row">×</button>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function find_preview_item_by_step($items, $step) {
        foreach ((array) $items as $item) {
            if (($item['step'] ?? '') === $step) {
                return $item;
            }
        }

        return [];
    }

    private function find_radius_preview_item($rows) {
        $preferred = ['m', 'md', 'base'];

        foreach ($preferred as $name) {
            foreach ((array) $rows as $row) {
                if (sanitize_key($row['name'] ?? '') === $name) {
                    return $row;
                }
            }
        }

        return $rows[0] ?? [];
    }

    private function root_font_size_hint($root_base_px) {
        return sprintf(
            __('Current root font size: %spx = 1rem.', 'ecf-framework'),
            $this->format_preview_number($root_base_px)
        );
    }

    private function get_editor_palette_html() {
        return '<div class="ecf-editor-help">' . esc_html__('Manage starter classes and optional utility classes in the Klassen tab. Add class names here only when you really need them on the current element.', 'ecf-framework') . '</div>';
    }

    public function inject_editor_controls($element, $section_id, $args) {
        if ('_section_responsive' !== $section_id) {
            return;
        }

        $element->start_controls_section('ecf_framework_section', [
            'label' => esc_html__('Layrix', 'ecf-framework'),
            'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
        ]);

        $element->add_control('ecf_framework_palette', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => $this->get_editor_palette_html(),
            'content_classes' => 'ecf-editor-raw',
        ]);

        $element->add_control('ecf_classes', [
            'label' => esc_html__('ECF Classes', 'ecf-framework'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'rows' => 4,
            'description' => esc_html__('Additional ECF classes separated by spaces.', 'ecf-framework'),
        ]);

        $element->end_controls_section();
    }

    public function append_ecf_classes_before_render($element) {
        if (!method_exists($element, 'get_settings_for_display')) {
            return;
        }
        $settings = $element->get_settings_for_display();
        if (empty($settings['ecf_classes'])) {
            return;
        }
        $classes = trim((string) $settings['ecf_classes']);
        if ($classes === '') {
            return;
        }
        $classes = preg_replace('/\s+/', ' ', $classes);
        $element->add_render_attribute('_wrapper', 'class', $classes);
    }

    /**
     * Auto-apply Layrix utility classes to common Elementor widgets when the
     * "auto_classes_enabled" setting is on. Covers both classic widgets (v3)
     * and atomic widgets (v4): heading → ecf-heading-N (from tag), button →
     * ecf-button, text-link → ecf-text-link, form → ecf-form. Body text is
     * intentionally not auto-classed because base body size is a global
     * setting in Layrix.
     */
    public function apply_auto_classes_before_render($element) {
        $plugin_settings = $this->get_settings();
        if (empty($plugin_settings['auto_classes_enabled'])) {
            return;
        }
        if (!method_exists($element, 'get_name') || !method_exists($element, 'add_render_attribute')) {
            return;
        }
        // Per-widget toggle: when key is not set yet (existing installs upgrading),
        // default to enabled. When explicitly set to '0', honour that.
        $is_enabled = function ($key) use ($plugin_settings) {
            return !array_key_exists($key, $plugin_settings) || !empty($plugin_settings[$key]);
        };
        $name = $element->get_name();
        $heading_widgets = [
            'heading',
            'e-heading',
            'theme-site-title',
            'theme-page-title',
            'theme-post-title',
        ];
        if (in_array($name, $heading_widgets, true)) {
            if (!$is_enabled('auto_classes_headings')) {
                return;
            }
            $tag = '';
            if (method_exists($element, 'get_settings_for_display')) {
                $tag = strtolower((string) (
                    $element->get_settings_for_display('tag')
                    ?: $element->get_settings_for_display('header_size')
                ));
            }
            $tag_to_class = [
                'h1' => 'ecf-heading-1',
                'h2' => 'ecf-heading-2',
                'h3' => 'ecf-heading-3',
                'h4' => 'ecf-heading-4',
                'h5' => 'ecf-heading-5',
                'h6' => 'ecf-heading-5',
            ];
            if (isset($tag_to_class[$tag])) {
                $element->add_render_attribute('_wrapper', 'class', $tag_to_class[$tag]);
            }
            return;
        }
        if ($name === 'button' || $name === 'e-button' || $name === 'e-form-submit-button') {
            // e-form-submit-button uses the same ecf-button class so Layrix's
            // transparent-by-default + token-driven padding/radius/font apply
            // uniformly across all button-like widgets.
            if ($is_enabled('auto_classes_buttons')) {
                $element->add_render_attribute('_wrapper', 'class', 'ecf-button');
            }
            return;
        }
        if ($name === 'text-link') {
            if ($is_enabled('auto_classes_text_link')) {
                $element->add_render_attribute('_wrapper', 'class', 'ecf-text-link');
            }
            return;
        }
        if ($name === 'form') {
            if ($is_enabled('auto_classes_form')) {
                $element->add_render_attribute('_wrapper', 'class', 'ecf-form');
            }
        }
    }
}
