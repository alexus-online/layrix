<?php

trait ECF_Framework_Output_CSS_Trait {
    public function output_css() {
        $settings = $this->get_settings();
        $root_base_px = $this->get_root_font_base_px($settings);
        $root_font_css = $this->get_root_font_css_value($settings);
        $spacing_scale = $this->build_spacing_scale($settings['spacing'], $root_base_px);
        $type_scale = $this->build_type_scale($settings['typography']['scale'], $root_base_px);
        echo "<style id='ecf-framework-v010'>";
        echo ":root{font-size:" . esc_attr($root_font_css) . ";";
        foreach ($settings['colors'] as $row) {
            $n = sanitize_key($row['name']);
            $v = esc_attr($this->sanitize_css_color_value($row['value'], $row['format'] ?? ''));
            if ($v === '') {
                continue;
            }
            echo "--ecf-color-$n:$v;";
            foreach ($this->shades_for_hex($row['value']) as $shade => $shade_hex) {
                echo "--ecf-color-$n-$shade:$shade_hex;";
            }
        }
        foreach ($spacing_scale as $name => $value) {
            echo "--ecf-space-$name:$value;";
        }
        foreach ($settings['radius'] as $row) {
            $name = sanitize_key($row['name']);
            $value = $this->radius_css_value($row, 375, 1280, $root_base_px);
            echo "--ecf-radius-$name:$value;";
        }
        foreach (['sm', 'md', 'lg', 'xl'] as $size) {
            $value = esc_attr($settings['container'][$size]);
            echo "--ecf-container-$size:$value;";
        }
        echo "--ecf-container-boxed:" . esc_attr($settings['elementor_boxed_width'] ?? '1140px') . ";";
        echo "--ecf-content-max-width:" . esc_attr($settings['content_max_width'] ?? '72ch') . ";";
        echo "--ecf-base-text-color:" . esc_attr($this->sanitize_css_color_value($settings['base_text_color'] ?? '#111827')) . ";";
        echo "--ecf-base-background-color:" . esc_attr($this->sanitize_css_color_value($settings['base_background_color'] ?? '#ffffff')) . ";";
        echo "--ecf-link-color:" . esc_attr($this->sanitize_css_color_value($settings['link_color'] ?? '#3b82f6')) . ";";
        echo "--ecf-focus-color:" . esc_attr($this->sanitize_css_color_value($settings['focus_color'] ?? '#6366f1')) . ";";
        echo "--ecf-base-font-family:" . esc_attr($settings['base_font_family'] ?? 'var(--ecf-font-primary)') . ";";
        echo "--ecf-base-body-text-size:" . esc_attr($settings['base_body_text_size'] ?? '16px') . ";";
        foreach ($settings['typography']['fonts'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            echo "--ecf-font-$name:$value;";
        }
        foreach ($type_scale as $name => $value) {
            echo "--ecf-text-$name:$value;";
        }
        foreach ($settings['typography']['weights'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            echo "--ecf-weight-$name:$value;";
        }
        foreach ($settings['typography']['leading'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            echo "--ecf-leading-$name:$value;";
        }
        foreach ($settings['typography']['tracking'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            echo "--ecf-tracking-$name:$value;";
        }
        foreach ($settings['shadows'] as $row) {
            $name = sanitize_key($row['name']);
            $value = esc_attr($row['value']);
            echo "--ecf-shadow-$name:$value;";
        }
        echo "}";

        if ($settings['enabled_components']['layout'] === '1') {
            echo ".ecf-container-boxed,.cf-container-boxed{width:min(100% - 2rem, var(--ecf-container-boxed));margin-inline:auto;}";
        }
        echo "body{font-family:var(--ecf-base-font-family);font-size:var(--ecf-base-body-text-size);color:var(--ecf-base-text-color);background-color:var(--ecf-base-background-color);}";
        echo "a{color:var(--ecf-link-color);}";
        echo ":focus-visible{outline:2px solid var(--ecf-focus-color);outline-offset:2px;}";
        echo ".ecf-content-width,.cf-content-width{width:min(100%,var(--ecf-content-max-width));margin-inline:auto;}";
        if ($settings['enabled_components']['cards'] === '1') {
            echo ".ecf-card,.cf-card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--ecf-radius-l,16px);padding:var(--ecf-space-l,24px);box-shadow:0 10px 30px rgba(0,0,0,.06);}";
        }
        foreach (($settings['typography']['local_fonts'] ?? []) as $row) {
            $family = sanitize_text_field($row['family'] ?? '');
            $src = esc_url($row['src'] ?? '');
            if ($family === '' || $src === '') {
                continue;
            }
            $format = $this->font_format_from_url($src);
            echo "@font-face{font-family:'" . esc_attr($family) . "';src:url('" . esc_url($src) . "')";
            if ($format !== '') {
                echo " format('" . esc_attr($format) . "')";
            }
            echo ";font-weight:" . esc_attr($row['weight'] ?? '400') . ";font-style:" . esc_attr($row['style'] ?? 'normal') . ";font-display:" . esc_attr($row['display'] ?? 'swap') . ";}";
        }
        if ($settings['enabled_components']['buttons'] === '1') {
            echo ".ecf-btn,.cf-btn{display:inline-flex;align-items:center;justify-content:center;padding:var(--ecf-space-s,8px) var(--ecf-space-m,16px);border-radius:var(--ecf-radius-m,12px);text-decoration:none;border:0;cursor:pointer;}.ecf-btn-primary,.cf-btn-primary{background:var(--ecf-color-primary,#3b82f6);color:#fff;}.ecf-btn-secondary,.cf-btn-secondary{background:var(--ecf-color-secondary,#64748b);color:#fff;}";
        }
        echo "</style>";
    }
}
