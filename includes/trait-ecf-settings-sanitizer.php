<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Settings_Sanitizer_Trait {
    private function fallback_positive_css_size($candidate, $saved_value, $default_value, $settings_for_default = null) {
        $candidate = trim((string) $candidate);
        $saved_value = trim((string) $saved_value);
        $default_value = trim((string) $default_value);

        $is_positive = function($value) {
            $parts = $this->parse_css_size_parts($value);
            if (($parts['format'] ?? '') === 'custom') {
                $normalized = strtolower(trim((string) $value));
                if ($normalized === '' || preg_match('/^(?:0|0px|0rem|0em|0ch|0%|0vw|0vh)$/', $normalized)) {
                    return false;
                }
                return $this->sanitize_css_size_value($value) !== '';
            }
            $numeric = (float) str_replace(',', '.', (string) ($parts['value'] ?? '0'));
            return $value !== '' && $numeric > 0;
        };

        if ($is_positive($candidate)) {
            return $candidate;
        }
        if ($is_positive($saved_value)) {
            return $saved_value;
        }
        if ($default_value === '16px' && $settings_for_default !== null) {
            return $this->derived_base_body_text_size($settings_for_default);
        }
        return $default_value;
    }

    public function sanitize_settings($input) {
        $defaults = $this->defaults();
        $output = $defaults;
        $saved_settings = get_option($this->option_name);
        $saved_settings = is_array($saved_settings) ? $saved_settings : [];
        $root_font_size = isset($input['root_font_size']) ? str_replace(',', '.', sanitize_text_field($input['root_font_size'])) : $defaults['root_font_size'];
        $output['root_font_size'] = in_array($root_font_size, ['100', '62.5'], true) ? $root_font_size : $defaults['root_font_size'];
        $interface_language = sanitize_key($input['interface_language'] ?? $defaults['interface_language']);
        $output['interface_language'] = in_array($interface_language, ['de', 'en'], true)
            ? $interface_language
            : $this->wordpress_default_interface_language();
        $admin_design_preset = sanitize_key($input['admin_design_preset'] ?? $defaults['admin_design_preset']);
        $output['admin_design_preset'] = $this->normalize_admin_design_preset($admin_design_preset ?: $defaults['admin_design_preset']);
        $output['admin_design_mode'] = 'dark';
        $admin_content_font_size = absint($input['admin_content_font_size'] ?? $defaults['admin_content_font_size']);
        $output['admin_content_font_size'] = (string) min(22, max(14, $admin_content_font_size ?: (int) $defaults['admin_content_font_size']));
        $admin_menu_font_size = absint($input['admin_menu_font_size'] ?? $defaults['admin_menu_font_size']);
        $output['admin_menu_font_size'] = (string) min(20, max(12, $admin_menu_font_size ?: (int) $defaults['admin_menu_font_size']));
        $output['autosave_enabled'] = !empty($input['autosave_enabled']) ? '1' : '0';
        $output['elementor_auto_sync_enabled'] = !empty($input['elementor_auto_sync_enabled']) ? '1' : '0';
        // Layrix-Class-Defaults: nested array [class_name][prop_key] = variable_label
        $output['layrix_class_defaults'] = [];
        if (isset($input['layrix_class_defaults']) && is_array($input['layrix_class_defaults'])) {
            $schema = method_exists($this, 'layrix_class_defaults_schema') ? $this->layrix_class_defaults_schema() : [];
            foreach ($input['layrix_class_defaults'] as $cls => $props) {
                if (!is_string($cls) || !isset($schema[$cls]) || !is_array($props)) continue;
                $allowed_props = array_keys($schema[$cls]['props'] ?? []);
                foreach ($props as $prop_key => $value) {
                    if (!in_array($prop_key, $allowed_props, true)) continue;
                    $value = sanitize_text_field((string) $value);
                    if ($value === '') continue;
                    if (!preg_match('/^[a-z][a-z0-9_-]*$/i', $value)) continue;
                    $output['layrix_class_defaults'][$cls][$prop_key] = $value;
                }
            }
        }

        $output['auto_classes_enabled']    = !empty($input['auto_classes_enabled'])    ? '1' : '0';
        $output['auto_classes_headings']   = !empty($input['auto_classes_headings'])   ? '1' : '0';
        $output['auto_classes_buttons']    = !empty($input['auto_classes_buttons'])    ? '1' : '0';
        $output['auto_classes_text_link']  = !empty($input['auto_classes_text_link'])  ? '1' : '0';
        $output['auto_classes_form']       = !empty($input['auto_classes_form'])       ? '1' : '0';
        $output['elementor_auto_sync_variables'] = !empty($input['elementor_auto_sync_variables']) ? '1' : '0';
        $output['elementor_auto_sync_classes'] = !empty($input['elementor_auto_sync_classes']) ? '1' : '0';
        $output['github_update_checks_enabled'] = !empty($input['github_update_checks_enabled']) ? '1' : '0';
        $content_width_value  = trim((string) ($input['content_max_width_value'] ?? $input['content_max_width'] ?? ''));
        $content_width_format = sanitize_key($input['content_max_width_format'] ?? '');
        if (in_array($content_width_format, ['px', 'rem', 'em', '%', 'vw', 'vh', 'ch'], true)) {
            $content_width = $this->sanitize_css_size_value($content_width_value . $content_width_format);
        } else {
            $content_width = $this->sanitize_css_size_value($content_width_value);
        }
        $output['content_max_width'] = $this->fallback_positive_css_size(
            $content_width,
            $saved_settings['content_max_width'] ?? '',
            $defaults['content_max_width']
        );
        $boxed_width_value  = trim((string) ($input['elementor_boxed_width_value'] ?? $input['elementor_boxed_width'] ?? ''));
        $boxed_width_format = sanitize_key($input['elementor_boxed_width_format'] ?? '');
        if (in_array($boxed_width_format, ['px', 'rem', 'em', '%', 'vw', 'vh'], true)) {
            $boxed_width = $this->sanitize_css_size_value($boxed_width_value . $boxed_width_format);
        } else {
            $boxed_width = $this->sanitize_css_size_value($boxed_width_value);
        }
        $output['elementor_boxed_width'] = $this->fallback_positive_css_size(
            $boxed_width,
            $saved_settings['elementor_boxed_width'] ?? '',
            $defaults['elementor_boxed_width']
        );
        $base_font_family_preset = sanitize_text_field($input['base_font_family_preset'] ?? '');
        $base_font_family_custom = sanitize_text_field($input['base_font_family_custom'] ?? '');
        if ($base_font_family_preset === '__custom__') {
            $base_font_family = $base_font_family_custom;
        } elseif ($base_font_family_preset !== '') {
            $base_font_family = $base_font_family_preset;
        } else {
            $base_font_family = sanitize_text_field($input['base_font_family'] ?? $defaults['base_font_family']);
        }
        $output['base_font_family'] = $base_font_family !== '' ? $base_font_family : $defaults['base_font_family'];
        $heading_font_family_preset = sanitize_text_field($input['heading_font_family_preset'] ?? '');
        $heading_font_family_custom = sanitize_text_field($input['heading_font_family_custom'] ?? '');
        if ($heading_font_family_preset === '__custom__') {
            $heading_font_family = $heading_font_family_custom;
        } elseif ($heading_font_family_preset !== '') {
            $heading_font_family = $heading_font_family_preset;
        } else {
            $heading_font_family = sanitize_text_field($input['heading_font_family'] ?? $defaults['heading_font_family']);
        }
        $output['heading_font_family'] = $heading_font_family !== '' ? $heading_font_family : $defaults['heading_font_family'];
        $base_body_text_size_value = trim((string) ($input['base_body_text_size_value'] ?? $input['base_body_text_size'] ?? ''));
        $base_body_text_size_format = sanitize_key($input['base_body_text_size_format'] ?? '');
        if (in_array($base_body_text_size_format, ['px', 'rem', 'em', 'ch', '%', 'vw', 'vh'], true)) {
            $base_body_text_size = $this->sanitize_css_size_value($base_body_text_size_value . $base_body_text_size_format);
        } else {
            $base_body_text_size = $this->sanitize_css_size_value($base_body_text_size_value);
        }
        $output['base_body_text_size'] = $this->fallback_positive_css_size(
            $base_body_text_size,
            $saved_settings['base_body_text_size'] ?? '',
            $defaults['base_body_text_size'],
            $output
        );
        $base_body_font_weight = $this->sanitize_css_number_or_size($input['base_body_font_weight'] ?? $defaults['base_body_font_weight']);
        $output['base_body_font_weight'] = $base_body_font_weight !== '' ? $base_body_font_weight : $this->typography_row_value('weights', 'normal', '400');
        $output['typography_browser_margin_reset'] = !empty($input['typography_browser_margin_reset']) ? '1' : '0';
        $base_text_color = $this->sanitize_css_color_value($input['base_text_color'] ?? $defaults['base_text_color']);
        $base_background_color = $this->sanitize_css_color_value($input['base_background_color'] ?? $defaults['base_background_color']);
        $link_color = $this->sanitize_css_color_value($input['link_color'] ?? $defaults['link_color']);
        $focus_color = $this->sanitize_css_color_value($input['focus_color'] ?? $defaults['focus_color']);
        $output['base_text_color'] = $base_text_color !== '' ? $base_text_color : $defaults['base_text_color'];
        $output['base_background_color'] = $base_background_color !== '' ? $base_background_color : $defaults['base_background_color'];
        $output['link_color'] = $link_color !== '' ? $link_color : $defaults['link_color'];
        $output['focus_color'] = $focus_color !== '' ? $focus_color : $defaults['focus_color'];
        $focus_outline_width_value = trim((string) ($input['focus_outline_width_value'] ?? $input['focus_outline_width'] ?? ''));
        $focus_outline_width_format = sanitize_key($input['focus_outline_width_format'] ?? '');
        if (in_array($focus_outline_width_format, ['px', 'rem', 'em'], true)) {
            $focus_outline_width = $this->sanitize_css_size_value($focus_outline_width_value . $focus_outline_width_format);
        } else {
            $focus_outline_width = $this->sanitize_css_size_value($focus_outline_width_value);
        }
        $output['focus_outline_width'] = $this->fallback_positive_css_size(
            $focus_outline_width,
            $saved_settings['focus_outline_width'] ?? '',
            $defaults['focus_outline_width']
        );
        $focus_outline_offset_value = trim((string) ($input['focus_outline_offset_value'] ?? $input['focus_outline_offset'] ?? ''));
        $focus_outline_offset_format = sanitize_key($input['focus_outline_offset_format'] ?? '');
        if (in_array($focus_outline_offset_format, ['px', 'rem', 'em'], true)) {
            $focus_outline_offset = $this->sanitize_css_size_value($focus_outline_offset_value . $focus_outline_offset_format);
        } else {
            $focus_outline_offset = $this->sanitize_css_size_value($focus_outline_offset_value);
        }
        $output['focus_outline_offset'] = $this->fallback_positive_css_size(
            $focus_outline_offset,
            $saved_settings['focus_outline_offset'] ?? '',
            $defaults['focus_outline_offset']
        );
        $output['show_elementor_status_cards'] = !empty($input['show_elementor_status_cards']) ? '1' : '0';
        $output['elementor_variable_type_filter'] = !empty($input['elementor_variable_type_filter']) ? '1' : '0';
        $output['general_setting_favorites'] = [];
        foreach ($this->general_setting_favorite_keys() as $favorite_key) {
            if (!empty($input['general_setting_favorites'][$favorite_key])) {
                $output['general_setting_favorites'][$favorite_key] = '1';
            }
        }
        $output['elementor_variable_type_filter_scopes'] = [
            'color'  => !empty($input['elementor_variable_type_filter_scopes']['color']) ? '1' : '0',
            'text'   => !empty($input['elementor_variable_type_filter_scopes']['text']) ? '1' : '0',
            'space'  => !empty($input['elementor_variable_type_filter_scopes']['space']) ? '1' : '0',
            'radius' => !empty($input['elementor_variable_type_filter_scopes']['radius']) ? '1' : '0',
            'shadow' => !empty($input['elementor_variable_type_filter_scopes']['shadow']) ? '1' : '0',
            'string' => !empty($input['elementor_variable_type_filter_scopes']['string']) ? '1' : '0',
        ];
        $output['starter_classes'] = [
            'enabled' => [],
            'custom'  => [],
            'seeded'  => '1',
        ];
        if (!empty($input['starter_classes']['enabled']) && is_array($input['starter_classes']['enabled'])) {
            foreach ($input['starter_classes']['enabled'] as $name => $enabled) {
                $normalized = $this->normalize_starter_class_name($name);
                if ($normalized !== '' && !empty($enabled)) {
                    $output['starter_classes']['enabled'][$normalized] = '1';
                }
            }
        }
        if (!empty($input['starter_classes']['custom']) && is_array($input['starter_classes']['custom'])) {
            foreach ($input['starter_classes']['custom'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = $this->normalize_starter_class_name($row['name'] ?? '');
                $category = sanitize_key($row['category'] ?? 'custom');
                $enabled = !empty($row['enabled']) ? '1' : '0';
                if ($name === '') {
                    continue;
                }
                $output['starter_classes']['custom'][] = [
                    'name' => $name,
                    'category' => $category ?: 'custom',
                    'enabled' => $enabled,
                ];
            }
        }
        $output['utility_classes'] = [
            'enabled' => [],
        ];
        if (!empty($input['utility_classes']['enabled']) && is_array($input['utility_classes']['enabled'])) {
            foreach ($input['utility_classes']['enabled'] as $name => $enabled) {
                $normalized = $this->normalize_starter_class_name($name);
                if ($normalized !== '' && !empty($enabled)) {
                    $output['utility_classes']['enabled'][$normalized] = '1';
                }
            }
        }

        $output['colors'] = [];
        if (!empty($input['colors']) && is_array($input['colors'])) {
            foreach ($input['colors'] as $row) {
                if (!is_array($row)) continue;
                $name = isset($row['name']) ? sanitize_key($row['name']) : '';
                $format = isset($row['format']) ? strtolower(sanitize_key($row['format'])) : '';
                $value = isset($row['value']) ? $this->sanitize_css_color_value($row['value'], $format) : '';
                if ($format === '') $format = $this->detect_color_format($value);
                if ($name === '' || $value === '') continue;
                $output['colors'][] = [
                    'name' => $name,
                    'value' => $value,
                    'format' => $format,
                    'generate_shades' => !empty($row['generate_shades']) ? '1' : '0',
                    'shade_count' => min(10, max(4, (int) ($row['shade_count'] ?? 6))),
                    'generate_tints' => !empty($row['generate_tints']) ? '1' : '0',
                    'tint_count' => min(10, max(4, (int) ($row['tint_count'] ?? 6))),
                ];
            }
        }
        if (empty($output['colors'])) $output['colors'] = $defaults['colors'];

        $output['radius'] = [];
        if (!empty($input['radius']) && is_array($input['radius'])) {
            foreach ($input['radius'] as $row) {
                if (!is_array($row)) continue;
                $name = isset($row['name']) ? sanitize_key($row['name']) : '';
                $min  = isset($row['min'])   ? $this->sanitize_css_size_value($row['min'])
                      : (isset($row['value']) ? $this->sanitize_css_size_value($row['value']) : '');
                $max  = isset($row['max'])   ? $this->sanitize_css_size_value($row['max'])
                      : (isset($row['value']) ? $this->sanitize_css_size_value($row['value']) : '');
                if ($name === '' || $min === '' || $max === '') continue;
                $output['radius'][] = ['name' => $name, 'min' => $min, 'max' => $max];
            }
        }
        if (empty($output['radius'])) $output['radius'] = $defaults['radius'];

        if (!empty($input['spacing']) && is_array($input['spacing'])) {
            $sp = $input['spacing'];
            $all_sp_steps = ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl'];
            if (!empty($sp['steps']) && is_array($sp['steps'])) {
                $validated_sp = array_values(array_filter(array_map('sanitize_key', $sp['steps']), function($s) use ($all_sp_steps) {
                    return in_array($s, $all_sp_steps, true);
                }));
                usort($validated_sp, function($a, $b) use ($all_sp_steps) {
                    return array_search($a, $all_sp_steps) - array_search($b, $all_sp_steps);
                });
                $valid_steps = count($validated_sp) >= 2 ? $validated_sp : $defaults['spacing']['steps'];
            } else {
                $valid_steps = $defaults['spacing']['steps'];
            }
            $base_index = sanitize_key($sp['base_index'] ?? $defaults['spacing']['base_index']);
            if (!in_array($base_index, $valid_steps, true)) $base_index = 'm';
            $max_base = max(1, floatval($sp['max_base'] ?? $sp['base'] ?? $defaults['spacing']['max_base']));
            $min_base = max(1, floatval($sp['min_base'] ?? $max_base * ($defaults['spacing']['scale_factor'] ?? 0.75)));
            if ($min_base > $max_base) $min_base = $max_base;
            $output['spacing'] = [
                'prefix'       => sanitize_key($sp['prefix'] ?? $defaults['spacing']['prefix']),
                'min_base'     => $min_base,
                'max_base'     => $max_base,
                'min_ratio'    => max(1.01, floatval($sp['min_ratio'] ?? $sp['ratio_up'] ?? $defaults['spacing']['min_ratio'])),
                'max_ratio'    => max(1.01, floatval($sp['max_ratio'] ?? $sp['ratio_up'] ?? $defaults['spacing']['max_ratio'])),
                'steps'        => $valid_steps,
                'base_index'   => $base_index,
                'fluid'        => !empty($sp['fluid']) ? true : false,
                'min_vw'       => max(200, intval($sp['min_vw'] ?? $defaults['spacing']['min_vw'])),
                'max_vw'       => max(800, intval($sp['max_vw'] ?? $defaults['spacing']['max_vw'])),
                'base'         => $max_base,
                'ratio_up'     => max(1.01, floatval($sp['max_ratio'] ?? $sp['ratio_up'] ?? $defaults['spacing']['max_ratio'])),
                'ratio_down'   => min(0.99, max(0.1, floatval($sp['ratio_down'] ?? 1 / max(1.01, floatval($sp['min_ratio'] ?? $defaults['spacing']['min_ratio']))))),
                'scale_factor' => $max_base > 0 ? round($min_base / $max_base, 4) : 0.75,
            ];
        }

        $output['shadows'] = [];
        if (!empty($input['shadows']) && is_array($input['shadows'])) {
            foreach ($input['shadows'] as $row) {
                if (!is_array($row)) continue;
                $name  = isset($row['name'])  ? sanitize_key($row['name'])         : '';
                $value = isset($row['value']) ? $this->sanitize_css_shadow_value($row['value']) : '';
                if ($name === '' || $value === '') continue;
                $output['shadows'][] = ['name' => $name, 'value' => $value];
            }
        }
        if (empty($output['shadows'])) $output['shadows'] = $defaults['shadows'];

        if (!empty($input['container']) && is_array($input['container'])) {
            foreach (['sm','md','lg','xl'] as $size) {
                $output['container'][$size] = $this->sanitize_css_size_value($input['container'][$size] ?? $defaults['container'][$size]);
                if ($output['container'][$size] === '') $output['container'][$size] = $defaults['container'][$size];
            }
        }

        $output['enabled_components'] = [
            'layout' => !empty($input['enabled_components']['layout']) ? '1' : '0',
            'buttons' => !empty($input['enabled_components']['buttons']) ? '1' : '0',
            'cards' => !empty($input['enabled_components']['cards']) ? '1' : '0',
        ];

        $typo_defaults = $defaults['typography'];

        $output['typography']['fonts'] = [];
        if (!empty($input['typography']['fonts']) && is_array($input['typography']['fonts'])) {
            foreach ($input['typography']['fonts'] as $row) {
                if (!is_array($row)) continue;
                $name  = isset($row['name'])  ? sanitize_key($row['name'])           : '';
                $value = isset($row['value']) ? $this->sanitize_css_font_stack($row['value'])   : '';
                if ($name === '' || $value === '') continue;
                $output['typography']['fonts'][] = ['name' => $name, 'value' => $value];
            }
        }
        if (empty($output['typography']['fonts'])) $output['typography']['fonts'] = $typo_defaults['fonts'];

        $output['typography']['local_fonts'] = [];
        if (!empty($input['typography']['local_fonts']) && is_array($input['typography']['local_fonts'])) {
            foreach ($input['typography']['local_fonts'] as $row) {
                if (!is_array($row)) continue;
                $name = sanitize_key($row['name'] ?? '');
                $family = $this->sanitize_css_font_stack($row['family'] ?? '');
                $src = esc_url_raw($row['src'] ?? '');
                $weight = $this->sanitize_css_number_or_size($row['weight'] ?? '400');
                $style = sanitize_key($row['style'] ?? 'normal');
                $display = sanitize_key($row['display'] ?? 'swap');
                if ($name === '' || $family === '' || $src === '') continue;
                if (!$this->is_local_font_url($src)) continue;
                if (!in_array($style, ['normal', 'italic', 'oblique'], true)) $style = 'normal';
                if (!in_array($display, ['auto', 'block', 'swap', 'fallback', 'optional'], true)) $display = 'swap';
                $output['typography']['local_fonts'][] = [
                    'name'    => $name,
                    'family'  => $family,
                    'src'     => $src,
                    'weight'  => $weight,
                    'style'   => $style,
                    'display' => $display,
                ];
            }
        }
        if (empty($output['typography']['local_fonts'])) $output['typography']['local_fonts'] = $typo_defaults['local_fonts'];

        $output['typography']['font_favorites'] = [];
        if (!empty($input['typography']['font_favorites']) && is_array($input['typography']['font_favorites'])) {
            foreach ($input['typography']['font_favorites'] as $fav) {
                $fav = sanitize_text_field((string) $fav);
                if ($fav !== '') $output['typography']['font_favorites'][] = $fav;
            }
        }

        if (!empty($input['typography']['scale']) && is_array($input['typography']['scale'])) {
            $sc = $input['typography']['scale'];
            $all_allowed_steps = ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl','7xl','8xl','9xl'];
            if (!empty($sc['steps']) && is_array($sc['steps'])) {
                $validated = array_values(array_filter(array_map('sanitize_key', $sc['steps']), function($s) use ($all_allowed_steps) {
                    return in_array($s, $all_allowed_steps, true);
                }));
                usort($validated, function($a, $b) use ($all_allowed_steps) {
                    return array_search($a, $all_allowed_steps) - array_search($b, $all_allowed_steps);
                });
                $valid_steps = count($validated) >= 2 ? $validated : $typo_defaults['scale']['steps'];
            } else {
                $valid_steps = $typo_defaults['scale']['steps'];
            }
            $base_index  = sanitize_key($sc['base_index'] ?? $typo_defaults['scale']['base_index']);
            if (!in_array($base_index, $valid_steps, true)) $base_index = 'm';
            $max_base = max(8, floatval($sc['max_base'] ?? $sc['base'] ?? $typo_defaults['scale']['max_base']));
            $legacy_scale_factor = floatval($sc['scale_factor'] ?? ($typo_defaults['scale']['min_base'] / max(1, $typo_defaults['scale']['max_base'])));
            $legacy_ratio = floatval($sc['ratio'] ?? $typo_defaults['scale']['max_ratio']);
            $min_base = isset($sc['min_base'])
                ? max(4, floatval($sc['min_base']))
                : max(4, round($max_base * $legacy_scale_factor, 3));
            if ($min_base > $max_base) {
                $min_base = $max_base;
            }
            $output['typography']['scale'] = [
                'min_base'     => $min_base,
                'max_base'     => $max_base,
                'min_ratio'    => max(1.01, floatval($sc['min_ratio'] ?? $legacy_ratio ?? $typo_defaults['scale']['min_ratio'])),
                'max_ratio'    => max(1.01, floatval($sc['max_ratio'] ?? $legacy_ratio ?? $typo_defaults['scale']['max_ratio'])),
                'steps'        => $valid_steps,
                'base_index'   => $base_index,
                'fluid'        => !empty($sc['fluid']) ? true : false,
                'min_vw'       => max(200, intval($sc['min_vw'] ?? $typo_defaults['scale']['min_vw'])),
                'max_vw'       => max(800, intval($sc['max_vw'] ?? $typo_defaults['scale']['max_vw'])),
            ];
        } else {
            $output['typography']['scale'] = $typo_defaults['scale'];
        }

        foreach (['weights', 'leading', 'tracking'] as $group) {
            $output['typography'][$group] = [];
            if (!empty($input['typography'][$group]) && is_array($input['typography'][$group])) {
                foreach ($input['typography'][$group] as $row) {
                    if (!is_array($row)) continue;
                    $name  = isset($row['name'])  ? sanitize_key($row['name'])         : '';
                    $value = isset($row['value']) ? $this->sanitize_css_number_or_size($row['value']) : '';
                    if ($name === '' || $value === '') continue;
                    $output['typography'][$group][] = ['name' => $name, 'value' => $value];
                }
            }
            if (empty($output['typography'][$group])) $output['typography'][$group] = $typo_defaults[$group];
        }

        if ($base_body_text_size === '' || $this->should_upgrade_base_body_text_size($output['base_body_text_size'] ?? '', $output)) {
            $output['base_body_text_size'] = $this->derived_base_body_text_size($output);
        }

        $output['base_font_family'] = $this->normalize_saved_font_family_value(
            $output['base_font_family'] ?? $defaults['base_font_family'],
            $output['typography']['local_fonts'] ?? [],
            $defaults['base_font_family']
        );
        $output['heading_font_family'] = $this->normalize_saved_font_family_value(
            $output['heading_font_family'] ?? $defaults['heading_font_family'],
            $output['typography']['local_fonts'] ?? [],
            $defaults['heading_font_family']
        );

        if (empty($output['github_update_checks_enabled'])) {
            $this->clear_registered_plugin_update_state();
        }

        return $output;
    }

    private function normalize_saved_font_family_value($value, $local_fonts, $default) {
        $value = trim((string) $value);
        $default = trim((string) $default);

        if ($value === '') {
            return $default;
        }

        $family_from_library_pointer = '';
        if (strpos($value, '__library__|') === 0) {
            $family_from_library_pointer = trim(substr($value, strlen('__library__|')));
        }

        foreach ((array) $local_fonts as $row) {
            $family = trim((string) ($row['family'] ?? ''));
            if ($family === '') {
                continue;
            }

            if ($value === $family || $value === "'" . $family . "'" || $family_from_library_pointer === $family) {
                return "'" . $family . "'";
            }
        }

        if ($family_from_library_pointer !== '') {
            return $default;
        }

        if (preg_match("/^'([^']+)'$/", $value, $matches)) {
            $single_family = trim((string) ($matches[1] ?? ''));
            if ($single_family !== '' && strpos($single_family, ',') === false) {
                return $default;
            }
        }

        if ($value !== '' && strpos($value, ',') === false && preg_match('/^[A-Za-z0-9 _-]+$/', $value)) {
            return $default;
        }

        return $value;
    }
}
