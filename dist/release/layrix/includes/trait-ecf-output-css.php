<?php

trait ECF_Framework_Output_CSS_Trait {
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
        foreach ($settings['colors'] as $row) {
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
        foreach ($settings['radius'] as $row) {
            $name = sanitize_key($row['name']);
            $value = $this->radius_css_value($row, 375, 1280, $root_base_px);
            $css .= "--ecf-radius-$name:$value;";
        }
        foreach (['sm', 'md', 'lg', 'xl'] as $size) {
            $value = esc_attr($settings['container'][$size]);
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
        foreach ($settings['typography']['fonts'] as $row) {
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
        foreach ($settings['typography']['weights'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-weight-$name:$value;";
        }
        foreach ($settings['typography']['leading'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-leading-$name:$value;";
        }
        foreach ($settings['typography']['tracking'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            $css .= "--ecf-tracking-$name:$value;";
        }
        foreach ($settings['shadows'] as $row) {
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
        $css .= ".ecf-container-boxed,.cf-container-boxed,.elementor .ecf-container-boxed,.elementor .cf-container-boxed{max-width:min(calc(100% - 2rem), var(--ecf-container-boxed))!important;margin-inline:auto!important;margin-left:auto!important;margin-right:auto!important;width:100%!important;}";

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

    public function output_css() {
        echo "<style id='ecf-framework-v010'>";
        echo $this->build_generated_css();
        echo "</style>";
    }
}
