<?php

trait ECF_Framework_Admin_Page_Sections_Trait {
    private function help_getting_started_items() {
        return [
            [
                'title' => __('Set the site basics', 'ecf-framework'),
                'description' => __('Open Base Settings and define root size, body text size, body font, heading font and the base colors of the site.', 'ecf-framework'),
            ],
            [
                'title' => __('Build your tokens', 'ecf-framework'),
                'description' => __('Adjust colors, radius, spacing, shadows and typography tokens until the preview feels right.', 'ecf-framework'),
            ],
            [
                'title' => __('Choose what should sync', 'ecf-framework'),
                'description' => __('Enable only the starter classes and utility classes you really want to keep maintainable in Elementor.', 'ecf-framework'),
            ],
            [
                'title' => __('Sync and verify', 'ecf-framework'),
                'description' => __('Run Sync & Export, then reload open Elementor tabs once so the new variables and classes appear reliably.', 'ecf-framework'),
            ],
        ];
    }

    private function help_quick_help_items() {
        return [
            [
                'title' => __('What are Variables?', 'ecf-framework'),
                'description' => __('Variables are reusable design tokens such as colors, spacing, radius, shadows, and text sizes. They help you keep Elementor and your CSS values consistent.', 'ecf-framework'),
            ],
            [
                'title' => __('What are Classes?', 'ecf-framework'),
                'description' => __('Classes are reusable styling bundles. In ECF they can be starter classes for semantic naming or compact utility classes for repeated helper patterns.', 'ecf-framework'),
            ],
            [
                'title' => __('What counts against Elementor limits?', 'ecf-framework'),
                'description' => __('Global Classes count against Elementor’s class limit. Synced ECF variables count against Elementor’s variable limit. Keep utility classes intentionally compact.', 'ecf-framework'),
            ],
            [
                'title' => __('What do Base Settings do?', 'ecf-framework'),
                'description' => __('Base Settings control global basics like root font size, plugin language, container widths, base colors, body font, and editor helper behavior.', 'ecf-framework'),
            ],
        ];
    }

    private function utility_class_preview_text($class_name) {
        static $map = null;
        if ($map === null) {
            $heading_text = __('The quick fox jumps over the fence', 'ecf-framework');
            $shadow_text  = __('Shadow token preview for this utility class.', 'ecf-framework');
            $default_text = __('Reading text for the live preview directly in the class list.', 'ecf-framework');
            $map = [
                'ecf-heading-1'      => $heading_text,
                'ecf-heading-2'      => $heading_text,
                'ecf-heading-3'      => $heading_text,
                'ecf-heading-4'      => $heading_text,
                'ecf-heading-5'      => $heading_text,
                'ecf-caption'        => __('Compact meta note for small hints', 'ecf-framework'),
                'ecf-overline'       => __('Section label', 'ecf-framework'),
                'ecf-text-left'      => __('Left aligned sample text in the preview.', 'ecf-framework'),
                'ecf-text-center'    => __('Centered sample text in the preview.', 'ecf-framework'),
                'ecf-text-right'     => __('Right aligned sample text in the preview.', 'ecf-framework'),
                'ecf-text-balance'   => __('Balanced line breaks make longer headings feel calmer.', 'ecf-framework'),
                'ecf-text-pretty'    => __('Pretty wrapping keeps paragraph breaks more natural.', 'ecf-framework'),
                'ecf-inline'         => __('Items stay inline with flexible spacing.', 'ecf-framework'),
                'ecf-inline-block'   => __('This element keeps its own box but stays inline.', 'ecf-framework'),
                'ecf-hidden'         => __('This helper hides the element visually.', 'ecf-framework'),
                'ecf-center-inline'  => __('Inline content is centered inside the available width.', 'ecf-framework'),
                'ecf-cluster'        => __('Small items group into a compact wrapping cluster.', 'ecf-framework'),
                'ecf-shadow-xs'      => $shadow_text,
                'ecf-shadow-s'       => $shadow_text,
                'ecf-shadow-m'       => $shadow_text,
                'ecf-shadow-l'       => $shadow_text,
                'ecf-shadow-xl'      => $shadow_text,
                'ecf-shadow-inner'   => $shadow_text,
                'ecf-visually-hidden'=> __('Hidden visually, still readable for assistive tech.', 'ecf-framework'),
                'ecf-body-l'         => $default_text,
                'ecf-body-m'         => $default_text,
                'ecf-body-s'         => $default_text,
            ];
        }
        return $map[(string) $class_name] ?? __('Reading text for the live preview directly in the class list.', 'ecf-framework');
    }

    private function utility_class_preview_kind($class_name, $category) {
        static $map = null;
        if ($map === null) {
            $map = [
                'ecf-text-left'      => 'text',
                'ecf-text-center'    => 'text',
                'ecf-text-right'     => 'text',
                'ecf-text-balance'   => 'text',
                'ecf-text-pretty'    => 'text',
                'ecf-inline'         => 'layout',
                'ecf-inline-block'   => 'layout',
                'ecf-hidden'         => 'layout',
                'ecf-center-inline'  => 'layout',
                'ecf-cluster'        => 'layout',
                'ecf-shadow-xs'      => 'shadow',
                'ecf-shadow-s'       => 'shadow',
                'ecf-shadow-m'       => 'shadow',
                'ecf-shadow-l'       => 'shadow',
                'ecf-shadow-xl'      => 'shadow',
                'ecf-shadow-inner'   => 'shadow',
                'ecf-visually-hidden'=> 'accessibility',
            ];
        }
        return $map[(string) $class_name] ?? ($category === 'typography' ? 'typography' : 'text');
    }

    private function utility_class_size_label($class_name, $settings = null) {
        $step_map = [
            'ecf-heading-1' => '4xl',
            'ecf-heading-2' => '3xl',
            'ecf-heading-3' => '2xl',
            'ecf-heading-4' => 'xl',
            'ecf-heading-5' => 'l',
            'ecf-body-l' => 'l',
            'ecf-body-m' => 'm',
            'ecf-body-s' => 's',
            'ecf-caption' => 'xs',
            'ecf-overline' => 'xs',
        ];

        $step = $step_map[(string) $class_name] ?? '';
        if ($step === '') {
            return '';
        }

        if (!is_array($settings)) {
            $settings = $this->get_settings();
        }

        $scale = is_array($settings['typography']['scale'] ?? null)
            ? $settings['typography']['scale']
            : [];
        $steps = is_array($scale['steps'] ?? null) ? $scale['steps'] : ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'];
        $base_index = sanitize_key($scale['base_index'] ?? 'm');
        if ($base_index === '' || !in_array($base_index, $steps, true)) {
            $base_index = in_array('m', $steps, true) ? 'm' : (string) reset($steps);
        }

        $root_base_px = $this->get_root_font_base_px($settings);
        foreach ($this->build_type_scale_preview($scale + ['steps' => $steps, 'base_index' => $base_index], $root_base_px) as $item) {
            if (($item['step'] ?? '') !== $step) {
                continue;
            }

            $min_px = trim((string) ($item['min_px'] ?? ''));
            $max_px = trim((string) ($item['max_px'] ?? ''));
            if ($min_px === '' && $max_px === '') {
                return '';
            }
            $min_label = $this->utility_class_size_display_value($min_px);
            $max_label = $this->utility_class_size_display_value($max_px);

            if ($min_label !== '' && $max_label !== '' && $min_label !== $max_label) {
                return $min_label . '-' . $max_label . ' px';
            }

            return ($max_label !== '' ? $max_label : $min_label) . ' px';
        }

        return '';
    }

    private function typography_step_preview_item($step, $settings = null) {
        $step = sanitize_key((string) $step);
        if ($step === '') {
            return null;
        }

        if (!is_array($settings)) {
            $settings = $this->get_settings();
        }

        $scale = is_array($settings['typography']['scale'] ?? null)
            ? $settings['typography']['scale']
            : [];
        $steps = is_array($scale['steps'] ?? null) ? $scale['steps'] : ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'];
        $base_index = sanitize_key($scale['base_index'] ?? 'm');
        if ($base_index === '' || !in_array($base_index, $steps, true)) {
            $base_index = in_array('m', $steps, true) ? 'm' : (string) reset($steps);
        }

        $root_base_px = $this->get_root_font_base_px($settings);
        foreach ($this->build_type_scale_preview($scale + ['steps' => $steps, 'base_index' => $base_index], $root_base_px) as $item) {
            if (($item['step'] ?? '') === $step) {
                return $item;
            }
        }

        return null;
    }

    private function admin_parse_hex_color($value) {
        $hex = strtolower(trim((string) $value));
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-f]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function admin_relative_luminance($value) {
        $rgb = $this->admin_parse_hex_color($value);
        if ($rgb === null) {
            return null;
        }

        $channels = array_map(static function($channel) {
            $normalized = $channel / 255;
            return $normalized <= 0.03928
                ? $normalized / 12.92
                : pow(($normalized + 0.055) / 1.055, 2.4);
        }, $rgb);

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }

    private function admin_contrast_ratio($foreground, $background) {
        $foreground_luminance = $this->admin_relative_luminance($foreground);
        $background_luminance = $this->admin_relative_luminance($background);

        if ($foreground_luminance === null || $background_luminance === null) {
            return null;
        }

        $lighter = max($foreground_luminance, $background_luminance);
        $darker = min($foreground_luminance, $background_luminance);

        return round((($lighter + 0.05) / ($darker + 0.05)), 2);
    }

    private function admin_shadow_peak_alpha($shadows) {
        $highest = 0.0;

        foreach ((array) $shadows as $row) {
            $value = (string) ($row['value'] ?? '');
            if (preg_match_all('/rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*([0-9.]+)\s*\)/i', $value, $matches)) {
                foreach (($matches[1] ?? []) as $alpha) {
                    $highest = max($highest, (float) $alpha);
                }
            }
        }

        return $highest;
    }

    private function website_design_health_checks($settings) {
        $checks = [];
        $contrast_ratio = $this->admin_contrast_ratio($settings['base_text_color'] ?? '', $settings['base_background_color'] ?? '');
        if ($contrast_ratio === null) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Base contrast', 'ecf-framework'),
                'message' => __('The current base text and background colors could not be evaluated automatically. Please check readability in the preview.', 'ecf-framework'),
                'value' => __('Manual check', 'ecf-framework'),
            ];
        } elseif ($contrast_ratio >= 7) {
            $checks[] = [
                'status' => 'good',
                'title' => __('Base contrast', 'ecf-framework'),
                'message' => __('Your default text and background colors have strong contrast for normal reading text.', 'ecf-framework'),
                'value' => sprintf(__('%s:1', 'ecf-framework'), $this->format_preview_number($contrast_ratio, 2)),
            ];
        } elseif ($contrast_ratio >= 4.5) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Base contrast', 'ecf-framework'),
                'message' => __('The default text contrast is usable, but a little more contrast would feel safer for long reading text.', 'ecf-framework'),
                'value' => sprintf(__('%s:1', 'ecf-framework'), $this->format_preview_number($contrast_ratio, 2)),
            ];
        } else {
            $checks[] = [
                'status' => 'warn',
                'title' => __('Base contrast', 'ecf-framework'),
                'message' => __('Your default text and background colors look low in contrast. Increase readability before building more styles on top.', 'ecf-framework'),
                'value' => sprintf(__('%s:1', 'ecf-framework'), $this->format_preview_number($contrast_ratio, 2)),
            ];
        }

        $body_parts = $this->parse_css_size_parts($settings['base_body_text_size'] ?? '16px');
        $body_format = strtolower((string) ($body_parts['format'] ?? ''));
        $body_value = (float) str_replace(',', '.', (string) ($body_parts['value'] ?? '0'));
        $root_base_px = $this->get_root_font_base_px($settings);
        $body_px = $body_format === 'rem' || $body_format === 'em' ? ($body_value * $root_base_px) : $body_value;
        if ($body_px >= 16 && $body_px <= 19) {
            $checks[] = [
                'status' => 'good',
                'title' => __('Body text size', 'ecf-framework'),
                'message' => __('Your current body text size sits in a comfortable reading range for most websites.', 'ecf-framework'),
                'value' => sprintf(__('%spx', 'ecf-framework'), $this->format_preview_number($body_px, 1)),
            ];
        } elseif ($body_px >= 14 && $body_px <= 21) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Body text size', 'ecf-framework'),
                'message' => __('The body text size can work, but it is a little outside the most forgiving reading range.', 'ecf-framework'),
                'value' => sprintf(__('%spx', 'ecf-framework'), $this->format_preview_number($body_px, 1)),
            ];
        } else {
            $checks[] = [
                'status' => 'warn',
                'title' => __('Body text size', 'ecf-framework'),
                'message' => __('The body text size looks unusually small or large for normal reading text. Review it before scaling the rest of the site.', 'ecf-framework'),
                'value' => sprintf(__('%spx', 'ecf-framework'), $this->format_preview_number($body_px, 1)),
            ];
        }

        $base_font = trim((string) ($settings['base_font_family'] ?? ''));
        $heading_font = trim((string) ($settings['heading_font_family'] ?? ''));
        if ($base_font !== '' && $base_font === $heading_font) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Font hierarchy', 'ecf-framework'),
                'message' => __('Body and heading currently use the same font. That can work, but a stronger pairing often creates clearer hierarchy.', 'ecf-framework'),
                'value' => __('Single-font system', 'ecf-framework'),
            ];
        } else {
            $checks[] = [
                'status' => 'good',
                'title' => __('Font hierarchy', 'ecf-framework'),
                'message' => __('Body and heading fonts are separated, which usually gives headings more presence and improves scanning.', 'ecf-framework'),
                'value' => __('Two-font system', 'ecf-framework'),
            ];
        }

        $spacing = is_array($settings['spacing'] ?? null) ? $settings['spacing'] : [];
        $spacing_min_ratio = (float) ($spacing['min_ratio'] ?? 1.25);
        $spacing_max_ratio = (float) ($spacing['max_ratio'] ?? 1.414);
        if ($spacing_max_ratio > 1.48 || $spacing_min_ratio < 1.15) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Spacing rhythm', 'ecf-framework'),
                'message' => __('Your spacing scale is quite contrast-heavy. That can feel expressive, but it may also make sections jump more than necessary.', 'ecf-framework'),
                'value' => sprintf(__('%1$s-%2$s ratio', 'ecf-framework'), $this->format_preview_number($spacing_min_ratio, 3), $this->format_preview_number($spacing_max_ratio, 3)),
            ];
        } else {
            $checks[] = [
                'status' => 'good',
                'title' => __('Spacing rhythm', 'ecf-framework'),
                'message' => __('Your spacing ratios look balanced enough for a calm and coherent layout rhythm.', 'ecf-framework'),
                'value' => sprintf(__('%1$s-%2$s ratio', 'ecf-framework'), $this->format_preview_number($spacing_min_ratio, 3), $this->format_preview_number($spacing_max_ratio, 3)),
            ];
        }

        $peak_shadow_alpha = $this->admin_shadow_peak_alpha($settings['shadows'] ?? []);
        if ($peak_shadow_alpha > 0.18) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Shadow intensity', 'ecf-framework'),
                'message' => __('One or more shadows are quite strong. If the UI starts to feel heavy, soften the larger shadow levels first.', 'ecf-framework'),
                'value' => sprintf(__('Peak alpha %s', 'ecf-framework'), $this->format_preview_number($peak_shadow_alpha, 2)),
            ];
        } else {
            $checks[] = [
                'status' => 'good',
                'title' => __('Shadow intensity', 'ecf-framework'),
                'message' => __('Your current shadow tokens stay in a softer range that usually feels more premium and calm.', 'ecf-framework'),
                'value' => sprintf(__('Peak alpha %s', 'ecf-framework'), $this->format_preview_number($peak_shadow_alpha, 2)),
            ];
        }

        $active_class_snapshot = $this->get_active_class_snapshot($settings);
        $sync_payload          = $active_class_snapshot['sync_payload'] ?? [];
        $orphaned_labels       = $this->get_ecf_native_class_labels($sync_payload);
        $only_in_elementor     = count($orphaned_labels);
        if ($only_in_elementor > 0) {
            $class_list = implode(', ', array_map(fn($l) => '.' . $l, $orphaned_labels));
            $fix_confirm = sprintf(
                _n(
                    "Diese Klasse aus Elementor entfernen?\n\n%s\n\nSie kann danach neu synchronisiert werden.",
                    "Diese Klassen aus Elementor entfernen?\n\n%s\n\nSie können danach neu synchronisiert werden.",
                    $only_in_elementor,
                    'ecf-framework'
                ),
                $class_list
            );
            $checks[] = [
                'status'       => 'notice',
                'title'        => __('Class cleanup', 'ecf-framework'),
                'message'      => $only_in_elementor === 1
                    ? __('Eine Klasse existiert bereits nur in Elementor und gehört nicht zu deiner aktuellen Layrix-Auswahl. Prüfe sie, bevor sie veraltet.', 'ecf-framework')
                    : sprintf(__('%d Klassen existieren bereits nur in Elementor und gehören nicht zu deiner aktuellen Layrix-Auswahl. Prüfe sie, bevor sie veralten.', 'ecf-framework'), $only_in_elementor),
                'value'        => $only_in_elementor === 1
                    ? __('1 nur in Elementor', 'ecf-framework')
                    : sprintf(__('%d nur in Elementor', 'ecf-framework'), $only_in_elementor),
                'fix_action'   => 'ecf_class_cleanup',
                'fix_nonce'    => wp_create_nonce('ecf_class_cleanup'),
                'fix_label'    => __('Bereinigen', 'ecf-framework'),
                'fix_confirm'  => $fix_confirm,
            ];
        } else {
            $checks[] = [
                'status' => 'good',
                'title' => __('Class cleanup', 'ecf-framework'),
                'message' => __('Your current class selection and Elementor class state look aligned.', 'ecf-framework'),
                'value' => __('No extra Elementor classes', 'ecf-framework'),
            ];
        }

        $counts = ['good' => 0, 'notice' => 0, 'warn' => 0];
        foreach ($checks as $check) {
            $status = $check['status'] ?? 'notice';
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }

        return [
            'checks' => $checks,
            'counts' => $counts,
        ];
    }

    private function website_smart_recommendations($settings, $design_health_snapshot = []) {
        $recommendations = [];
        $contrast_ratio = $this->admin_contrast_ratio($settings['base_text_color'] ?? '', $settings['base_background_color'] ?? '');
        if ($contrast_ratio !== null && $contrast_ratio < 4.5) {
            $bg_luminance = $this->admin_relative_luminance($settings['base_background_color'] ?? '');
            $suggested_text = ($bg_luminance !== null && $bg_luminance > 0.46) ? '#111827' : '#f8fafc';
            $recommendations[] = [
                'tone' => __('Readability', 'ecf-framework'),
                'title' => __('Strengthen body contrast', 'ecf-framework'),
                'description' => __('Darken or lighten the default text color so longer paragraphs feel clearer and safer to read.', 'ecf-framework'),
                'impact' => __('Updates only the base text color.', 'ecf-framework'),
                'apply_label' => __('Apply contrast fix', 'ecf-framework'),
                'payload' => [
                    'general' => [
                        'base_text_color' => $suggested_text,
                    ],
                ],
            ];
        }

        $body_parts = $this->parse_css_size_parts($settings['base_body_text_size'] ?? '16px');
        $body_format = strtolower((string) ($body_parts['format'] ?? ''));
        $body_value = (float) str_replace(',', '.', (string) ($body_parts['value'] ?? '0'));
        $root_base_px = $this->get_root_font_base_px($settings);
        $body_px = $body_format === 'rem' || $body_format === 'em' ? ($body_value * $root_base_px) : $body_value;
        if ($body_px < 16 || $body_px > 19) {
            $recommended_body_size = $body_px < 16 ? '16px' : '18px';
            $recommendations[] = [
                'tone' => __('Rhythm', 'ecf-framework'),
                'title' => __('Normalize body size', 'ecf-framework'),
                'description' => __('Bring the body text back into a safer default reading range before refining the smaller details.', 'ecf-framework'),
                'impact' => sprintf(__('Sets body text to %s.', 'ecf-framework'), $recommended_body_size),
                'apply_label' => __('Use this body size', 'ecf-framework'),
                'payload' => [
                    'general' => [
                        'base_body_text_size' => $recommended_body_size,
                    ],
                ],
            ];
        }

        $base_font = trim((string) ($settings['base_font_family'] ?? ''));
        $heading_font = trim((string) ($settings['heading_font_family'] ?? ''));
        if ($base_font !== '' && $base_font === $heading_font) {
            $recommendations[] = [
                'tone' => __('Hierarchy', 'ecf-framework'),
                'title' => __('Separate headings from body copy', 'ecf-framework'),
                'description' => __('Let headings use the secondary font stack so the page scans more clearly without rebuilding the whole type system.', 'ecf-framework'),
                'impact' => __('Switches only the heading font family.', 'ecf-framework'),
                'apply_label' => __('Use the secondary heading font', 'ecf-framework'),
                'payload' => [
                    'general' => [
                        'heading_font_family' => 'var(--ecf-font-secondary)',
                    ],
                ],
            ];
        }

        $spacing = is_array($settings['spacing'] ?? null) ? $settings['spacing'] : [];
        $spacing_min_ratio = (float) ($spacing['min_ratio'] ?? 1.25);
        $spacing_max_ratio = (float) ($spacing['max_ratio'] ?? 1.414);
        if ($spacing_max_ratio > 1.48 || $spacing_min_ratio < 1.15) {
            $recommendations[] = [
                'tone' => __('Spacing', 'ecf-framework'),
                'title' => __('Calm the spacing rhythm', 'ecf-framework'),
                'description' => __('Bring the spacing scale back into a more balanced range so sections feel calmer and less jumpy.', 'ecf-framework'),
                'impact' => __('Resets the spacing ratios to a calmer baseline.', 'ecf-framework'),
                'apply_label' => __('Use calmer spacing', 'ecf-framework'),
                'payload' => [
                    'spacing' => [
                        'min_base' => (string) ($spacing['min_base'] ?? '16'),
                        'max_base' => (string) ($spacing['max_base'] ?? '24'),
                        'min_ratio' => '1.2',
                        'max_ratio' => '1.333',
                        'base_index' => (string) ($spacing['base_index'] ?? 'm'),
                        'fluid' => !empty($spacing['fluid']),
                        'min_vw' => (string) ($spacing['min_vw'] ?? '375'),
                        'max_vw' => (string) ($spacing['max_vw'] ?? '1280'),
                    ],
                ],
            ];
        }

        $peak_shadow_alpha = $this->admin_shadow_peak_alpha($settings['shadows'] ?? []);
        if ($peak_shadow_alpha > 0.18) {
            $recommendations[] = [
                'tone' => __('Shadows', 'ecf-framework'),
                'title' => __('Soften the shadow scale', 'ecf-framework'),
                'description' => __('Reduce the strongest shadow levels so cards and panels feel lighter, calmer and more premium.', 'ecf-framework'),
                'impact' => __('Applies softer values to the shadow tokens.', 'ecf-framework'),
                'apply_label' => __('Use softer shadows', 'ecf-framework'),
                'payload' => [
                    'shadows' => [
                        'xs' => '0 1px 2px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.04)',
                        's' => '0 2px 4px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06)',
                        'm' => '0 4px 8px rgba(0,0,0,.09), 0 8px 24px rgba(0,0,0,.08)',
                        'l' => '0 8px 16px rgba(0,0,0,.10), 0 16px 40px rgba(0,0,0,.10)',
                        'xl' => '0 16px 32px rgba(0,0,0,.12), 0 32px 64px rgba(0,0,0,.14)',
                        'inner' => 'inset 0 2px 4px rgba(0,0,0,.06)',
                    ],
                ],
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'tone' => __('Healthy base', 'ecf-framework'),
                'title' => __('Your current basics already look solid', 'ecf-framework'),
                'description' => __('There is nothing urgent to fix here right now. You can keep refining style choices or move on to section building.', 'ecf-framework'),
                'impact' => __('No quick fix needed at the moment.', 'ecf-framework'),
                'apply_label' => '',
                'payload' => [],
            ];
        }

        return array_slice($recommendations, 0, 4);
    }

    private function utility_class_size_display_value($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return (string) round((float) $value);
    }

    private function utility_shadow_slug($class_name) {
        $class_name = sanitize_key((string) $class_name);
        if (strpos($class_name, 'ecf-shadow-') !== 0) {
            return '';
        }

        return substr($class_name, strlen('ecf-shadow-'));
    }

    private function utility_shadow_value($class_name, $settings = null) {
        $slug = $this->utility_shadow_slug($class_name);
        if ($slug === '') {
            return '';
        }

        if (!is_array($settings)) {
            $settings = $this->get_settings();
        }

        foreach ((array) ($settings['shadows'] ?? []) as $row) {
            if (sanitize_key($row['name'] ?? '') === $slug) {
                return trim((string) ($row['value'] ?? ''));
            }
        }

        return '0 1px 2px rgba(0,0,0,0.05)';
    }

    private function utility_shadow_display_name($class_name) {
        $slug = $this->utility_shadow_slug($class_name);
        if ($slug === '') {
            return '';
        }

        return ucfirst(str_replace('-', ' ', $slug));
    }

    private function root_font_impact_preview_data($settings) {
        $root_base_px = $this->get_root_font_base_px($settings);

        $type_scale = is_array($settings['typography']['scale'] ?? null) ? $settings['typography']['scale'] : [];
        $type_steps = is_array($type_scale['steps'] ?? null) ? $type_scale['steps'] : ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'];
        $type_base = sanitize_key($type_scale['base_index'] ?? 'm');
        if ($type_base === '' || !in_array($type_base, $type_steps, true)) {
            $type_base = in_array('m', $type_steps, true) ? 'm' : (string) reset($type_steps);
        }
        $type_root_preview = $this->find_preview_item_by_step(
            $this->build_type_scale_preview($type_scale + ['steps' => $type_steps, 'base_index' => $type_base], $root_base_px),
            $type_base
        );

        $spacing_scale = is_array($settings['spacing'] ?? null) ? $settings['spacing'] : [];
        $spacing_steps = is_array($spacing_scale['steps'] ?? null) ? $spacing_scale['steps'] : ['2xs', 'xs', 's', 'm', 'l', 'xl', '2xl'];
        $spacing_base = sanitize_key($spacing_scale['base_index'] ?? 'm');
        if ($spacing_base === '' || !in_array($spacing_base, $spacing_steps, true)) {
            $spacing_base = in_array('m', $spacing_steps, true) ? 'm' : (string) reset($spacing_steps);
        }
        $spacing_root_preview = $this->find_preview_item_by_step(
            $this->build_spacing_scale_preview($spacing_scale + ['steps' => $spacing_steps, 'base_index' => $spacing_base], $root_base_px),
            $spacing_base
        );

        $radius_rows = is_array($settings['radius'] ?? null) ? $settings['radius'] : [];
        $radius_root_preview = $this->find_radius_preview_item($radius_rows);

        return [
            'root_base_px' => $root_base_px,
            'type' => $type_root_preview,
            'spacing' => $spacing_root_preview,
            'radius' => $radius_root_preview,
        ];
    }

}
