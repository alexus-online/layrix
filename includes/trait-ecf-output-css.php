<?php

trait ECF_Framework_Output_CSS_Trait {
    private function class_prop_value_for_output($prop_value) {
        if (!is_array($prop_value)) {
            return is_scalar($prop_value) ? (string) $prop_value : '';
        }

        $type = $prop_value['$$type'] ?? '';
        $value = $prop_value['value'] ?? null;

        if ($type === 'size' && is_array($value)) {
            return (string) ($value['size'] ?? '') . (string) ($value['unit'] ?? '');
        }

        if (($type === 'string' || $type === 'color') && is_scalar($value)) {
            return (string) $value;
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function build_selected_utility_class_css($settings) {
        $css = '';

        foreach ($this->get_selected_utility_class_names($settings) as $class_name) {
            $props = $this->utility_class_props($class_name, $settings);
            if (!is_array($props) || empty($props)) {
                continue;
            }

            $declarations = [];
            foreach ($props as $prop_name => $prop_value) {
                $value = trim($this->class_prop_value_for_output($prop_value));
                if ($value === '') {
                    continue;
                }
                $declarations[] = $prop_name . ':' . esc_attr($value);
            }

            if (empty($declarations)) {
                continue;
            }

            $selector = '.' . sanitize_html_class($class_name);
            $css .= $selector . '{' . implode(';', $declarations) . ';}';
        }

        return $css;
    }

    private function css_font_value_for_output($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^var\(--ecf-font-[a-z0-9_-]+\)$/i', $value)) {
            return $value;
        }

        return $this->sanitize_css_font_stack($value);
    }

    private function css_string_literal($value) {
        $value = trim((string) $value);
        $value = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);

        return "'" . $value . "'";
    }

    private function resolved_font_family_css_value($settings, $setting_key, $default_value) {
        $selected = trim((string) ($settings[$setting_key] ?? ''));
        if ($selected === '') {
            $selected = $default_value;
        }

        if (preg_match('/^var\(--ecf-font-([a-z0-9_-]+)\)$/i', $selected, $matches)) {
            $token_name = sanitize_key($matches[1]);
            foreach ((array) ($settings['typography']['fonts'] ?? []) as $row) {
                if (sanitize_key($row['name'] ?? '') !== $token_name) {
                    continue;
                }

                $value = trim((string) ($row['value'] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return $selected;
    }

    private function resolved_base_font_family_css_value($settings) {
        return $this->resolved_font_family_css_value($settings, 'base_font_family', 'var(--ecf-font-primary)');
    }

    private function resolved_heading_font_family_css_value($settings) {
        return $this->resolved_font_family_css_value($settings, 'heading_font_family', 'var(--ecf-font-primary)');
    }

    private function build_generated_css($settings = null, $pretty = false) {
        if ($settings === null) {
            $settings = $this->get_settings();
        }
        $root_base_px = $this->get_root_font_base_px($settings);
        $root_font_css = $this->get_root_font_css_value($settings);
        $spacing_scale = $this->build_spacing_scale($settings['spacing'], $root_base_px);
        $type_scale = $this->build_type_scale($settings['typography']['scale'], $root_base_px);
        $base_body_text_size = trim((string) ($settings['base_body_text_size'] ?? ''));
        if ($this->should_upgrade_base_body_text_size($base_body_text_size, $settings)) {
            $base_body_text_size = $this->derived_base_body_text_size($settings);
        }
        $base_body_font_weight = trim((string) ($settings['base_body_font_weight'] ?? ''));
        if ($base_body_font_weight === '') {
            $base_body_font_weight = $this->typography_row_value('weights', 'normal', '400');
        }
        $css = ":root{font-size:" . esc_attr($root_font_css) . ";";
        foreach ($settings['colors'] ?? [] as $row) {
            $n = sanitize_key($row['name']);
            $v = esc_attr($this->sanitize_css_color_value($row['value'], $row['format'] ?? ''));
            if ($v === '') {
                continue;
            }
            $css .= "--ecf-color-$n:$v;";
            foreach ($this->shades_for_hex($row['value']) as $shade => $shade_hex) {
                $css .= "--ecf-color-$n-$shade:$shade_hex;";
            }
            foreach ($this->generated_color_variants($row['value'], $row) as $variant => $variant_value) {
                $css .= "--ecf-color-$n-" . sanitize_key($variant) . ":" . esc_attr($variant_value) . ";";
            }
        }
        foreach ($spacing_scale as $name => $value) {
            $css .= "--ecf-space-$name:$value;";
        }
        foreach ($settings['radius'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = $this->radius_css_value($row, 375, 1280, $root_base_px);
            $css .= "--ecf-radius-$name:$value;";
        }
        foreach (['sm', 'md', 'lg', 'xl'] as $size) {
            $value = esc_attr($settings['container'][$size] ?? '');
            $css .= "--ecf-container-$size:$value;";
        }
        $css .= "--ecf-container-boxed:" . esc_attr($settings['elementor_boxed_width'] ?? '1140px') . ";";
        $css .= "--ecf-content-max-width:" . esc_attr($settings['content_max_width'] ?? '72ch') . ";";
        $css .= "--ecf-base-text-color:" . esc_attr($this->sanitize_css_color_value($settings['base_text_color'] ?? '#111827')) . ";";
        $css .= "--ecf-base-background-color:" . esc_attr($this->sanitize_css_color_value($settings['base_background_color'] ?? '#ffffff')) . ";";
        $css .= "--ecf-link-color:" . esc_attr($this->sanitize_css_color_value($settings['link_color'] ?? '#3b82f6')) . ";";
        $css .= "--ecf-focus-color:" . esc_attr($this->sanitize_css_color_value($settings['focus_color'] ?? '#6366f1')) . ";";
        $css .= "--ecf-focus-outline-width:" . esc_attr($this->sanitize_css_size_value($settings['focus_outline_width'] ?? '2px') ?: '2px') . ";";
        $css .= "--ecf-focus-outline-offset:" . esc_attr($this->sanitize_css_size_value($settings['focus_outline_offset'] ?? '2px') ?: '2px') . ";";
        $resolved_base_font_family = $this->resolved_base_font_family_css_value($settings);
        $resolved_heading_font_family = $this->resolved_heading_font_family_css_value($settings);
        $css .= "--ecf-base-font-family:" . $this->css_font_value_for_output($resolved_base_font_family) . ";";
        $css .= "--ecf-base-body-font-family:" . $this->css_font_value_for_output($resolved_base_font_family) . ";";
        $css .= "--ecf-heading-font-family:" . $this->css_font_value_for_output($resolved_heading_font_family) . ";";
        $css .= "--ecf-base-body-text-size:" . esc_attr($base_body_text_size) . ";";
        $css .= "--ecf-base-body-font-weight:" . esc_attr($base_body_font_weight) . ";";
        foreach ($settings['typography']['fonts'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = $this->css_font_value_for_output($row['value'] ?? '');
            if ($value === '') {
                continue;
            }
            $css .= "--ecf-font-$name:$value;";
        }
        foreach ($type_scale as $name => $value) {
            $css .= "--ecf-text-$name:$value;";
        }
        foreach ($settings['typography']['weights'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-weight-$name:$value;";
        }
        foreach ($settings['typography']['leading'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-leading-$name:$value;";
        }
        foreach ($settings['typography']['tracking'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-tracking-$name:$value;";
        }
        foreach ($settings['shadows'] ?? [] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-shadow-$name:$value;";
        }
        $css .= "}";

        if ($settings['enabled_components']['layout'] === '1') {
            $css .= ".ecf-container-boxed{margin-left:auto!important;margin-right:auto!important;}";
            $css .= ".ecf-container-boxed,.cf-container-boxed,.elementor .ecf-container-boxed,.elementor .cf-container-boxed{--margin-inline-start:auto!important;--margin-inline-end:auto!important;width:100%!important;max-width:min(calc(100% - 2rem), var(--ecf-container-boxed))!important;margin-inline:auto!important;margin-left:auto!important;margin-right:auto!important;}";
            $css .= ".elementor .elementor-element.ecf-container-boxed,.elementor .elementor-element.cf-container-boxed{--margin-inline-start:auto!important;--margin-inline-end:auto!important;margin-inline-start:auto!important;margin-inline-end:auto!important;margin-left:auto!important;margin-right:auto!important;width:min(calc(100% - 2rem), var(--ecf-container-boxed))!important;max-width:min(calc(100% - 2rem), var(--ecf-container-boxed))!important;}";
            $css .= ".elementor .elementor-element.e-con.e-atomic-element.e-flexbox-base:has(> .elementor-element.ecf-container-boxed),.elementor .elementor-element.e-con.e-atomic-element.e-flexbox-base:has(> .elementor-element.cf-container-boxed){justify-content:center!important;}";
        }
        $css .= "body{font-family:var(--ecf-base-body-font-family,var(--ecf-base-font-family));font-size:var(--ecf-base-body-text-size);font-weight:var(--ecf-base-body-font-weight);color:var(--ecf-base-text-color);background-color:var(--ecf-base-background-color);}";
        if (!array_key_exists('typography_browser_margin_reset', (array) $settings) || !empty($settings['typography_browser_margin_reset'])) {
            $css .= "h1,h2,h3,h4,h5,h6,p{margin-block:0;}";
        }
        $css .= "h1,h2,h3,h4,h5,h6{font-family:var(--ecf-heading-font-family,var(--ecf-font-primary));}";
        $css .= "a{color:var(--ecf-link-color);}";
        $css .= ":focus-visible{outline:var(--ecf-focus-outline-width) solid var(--ecf-focus-color);outline-offset:var(--ecf-focus-outline-offset);}";
        $css .= ".ecf-content-width,.cf-content-width{width:min(100%,var(--ecf-content-max-width));margin-inline:auto;}";
        if ($settings['enabled_components']['cards'] === '1') {
            $css .= ".ecf-card,.cf-card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--ecf-radius-l,16px);padding:var(--ecf-space-l,24px);box-shadow:0 10px 30px rgba(0,0,0,.06);}";
        }
        foreach (($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = sanitize_text_field($row['family'] ?? '');
            $src = esc_url($row['src'] ?? '');
            if ($family === '' || $src === '') {
                continue;
            }
            $format = $this->font_format_from_url($src);
            $css .= "@font-face{font-family:" . $this->css_string_literal($family) . ";src:url('" . esc_url($src) . "')";
            if ($format !== '') {
                $css .= " format('" . esc_attr($format) . "')";
            }
            $css .= ";font-weight:" . esc_attr($row['weight'] ?? '400') . ";font-style:" . esc_attr($row['style'] ?? 'normal') . ";font-display:" . esc_attr($row['display'] ?? 'swap') . ";}";
        }
        if ($settings['enabled_components']['buttons'] === '1') {
            $css .= ".ecf-btn,.cf-btn{display:inline-flex;align-items:center;justify-content:center;padding:var(--ecf-space-s,8px) var(--ecf-space-m,16px);border-radius:var(--ecf-radius-m,12px);text-decoration:none;border:0;cursor:pointer;}.ecf-btn-primary,.cf-btn-primary{background:var(--ecf-color-primary,#3b82f6);color:#fff;}.ecf-btn-secondary,.cf-btn-secondary{background:var(--ecf-color-secondary,#64748b);color:#fff;}";
        }
        $css .= $this->build_selected_utility_class_css($settings);
        $css .= ".ecf-container-boxed,.cf-container-boxed,.elementor .ecf-container-boxed,.elementor .cf-container-boxed{max-width:min(calc(100% - 2rem), var(--ecf-container-boxed))!important;margin-inline:auto!important;margin-left:auto!important;margin-right:auto!important;width:100%!important;}";

        /* Dark Mode overrides */
        $dark_vars = '';
        foreach ($settings['colors'] ?? [] as $row) {
            if (empty($row['dark_enabled']) || empty($row['dark_value'])) continue;
            $n  = sanitize_key($row['name'] ?? '');
            $dv = esc_attr($this->sanitize_css_color_value($row['dark_value'], 'hex'));
            if ($n && $dv) $dark_vars .= "--ecf-color-$n:$dv;";
        }
        if ($dark_vars) {
            $css .= "@media(prefers-color-scheme:dark){:root{{$dark_vars}}}";
        }

        return $pretty ? $this->format_generated_css($css) : $css;
    }

    private function format_generated_css($css) {
        $css = preg_replace('/\s+/', ' ', trim((string) $css));
        $css = str_replace('{', " {\n    ", $css);
        $css = str_replace(';', ";\n    ", $css);
        $css = str_replace('}', "\n}\n\n", $css);
        $css = preg_replace("/\n    \n}/", "\n}", $css);
        $css = preg_replace("/\n{3,}/", "\n\n", $css);

        return trim($css) . "\n";
    }

    private function css_transient_key(): string {
        return 'ecf_generated_css_v1';
    }

    public function clear_css_cache(): void {
        delete_transient($this->css_transient_key());
    }

    public function output_css() {
        $css = get_transient($this->css_transient_key());
        if ($css === false) {
            $css = $this->build_generated_css();
            set_transient($this->css_transient_key(), $css, DAY_IN_SECONDS);
        }
        echo "<style id='ecf-framework-v010'>" . $css . "</style>";
    }
}
