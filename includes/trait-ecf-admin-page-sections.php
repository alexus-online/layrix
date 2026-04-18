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
        switch ((string) $class_name) {
            case 'ecf-heading-1':
            case 'ecf-heading-2':
            case 'ecf-heading-3':
            case 'ecf-heading-4':
            case 'ecf-heading-5':
                return __('The quick fox jumps over the fence', 'ecf-framework');
            case 'ecf-caption':
                return __('Compact meta note for small hints', 'ecf-framework');
            case 'ecf-overline':
                return __('Section label', 'ecf-framework');
            case 'ecf-text-left':
                return __('Left aligned sample text in the preview.', 'ecf-framework');
            case 'ecf-text-center':
                return __('Centered sample text in the preview.', 'ecf-framework');
            case 'ecf-text-right':
                return __('Right aligned sample text in the preview.', 'ecf-framework');
            case 'ecf-text-balance':
                return __('Balanced line breaks make longer headings feel calmer.', 'ecf-framework');
            case 'ecf-text-pretty':
                return __('Pretty wrapping keeps paragraph breaks more natural.', 'ecf-framework');
            case 'ecf-inline':
                return __('Items stay inline with flexible spacing.', 'ecf-framework');
            case 'ecf-inline-block':
                return __('This element keeps its own box but stays inline.', 'ecf-framework');
            case 'ecf-hidden':
                return __('This helper hides the element visually.', 'ecf-framework');
            case 'ecf-center-inline':
                return __('Inline content is centered inside the available width.', 'ecf-framework');
            case 'ecf-cluster':
                return __('Small items group into a compact wrapping cluster.', 'ecf-framework');
            case 'ecf-shadow-xs':
            case 'ecf-shadow-s':
            case 'ecf-shadow-m':
            case 'ecf-shadow-l':
            case 'ecf-shadow-xl':
            case 'ecf-shadow-inner':
                return __('Shadow token preview for this utility class.', 'ecf-framework');
            case 'ecf-visually-hidden':
                return __('Hidden visually, still readable for assistive tech.', 'ecf-framework');
            case 'ecf-body-l':
            case 'ecf-body-m':
            case 'ecf-body-s':
            default:
                return __('Reading text for the live preview directly in the class list.', 'ecf-framework');
        }
    }

    private function utility_class_preview_kind($class_name, $category) {
        switch ((string) $class_name) {
            case 'ecf-text-left':
            case 'ecf-text-center':
            case 'ecf-text-right':
            case 'ecf-text-balance':
            case 'ecf-text-pretty':
                return 'text';
            case 'ecf-inline':
            case 'ecf-inline-block':
            case 'ecf-hidden':
            case 'ecf-center-inline':
            case 'ecf-cluster':
                return 'layout';
            case 'ecf-shadow-xs':
            case 'ecf-shadow-s':
            case 'ecf-shadow-m':
            case 'ecf-shadow-l':
            case 'ecf-shadow-xl':
            case 'ecf-shadow-inner':
                return 'shadow';
            case 'ecf-visually-hidden':
                return 'accessibility';
            default:
                return $category === 'typography' ? 'typography' : 'text';
        }
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
        $sync_payload_count = count((array) ($active_class_snapshot['sync_payload'] ?? []));
        $elementor_class_total = (int) $this->get_native_global_class_total_count();
        $only_in_elementor = max(0, $elementor_class_total - $sync_payload_count);
        if ($only_in_elementor > 0) {
            $checks[] = [
                'status' => 'notice',
                'title' => __('Class cleanup', 'ecf-framework'),
                'message' => __('Some classes already exist only in Elementor and are not part of your current Layrix selection. Review them before they become stale.', 'ecf-framework'),
                'value' => sprintf(__('%d only in Elementor', 'ecf-framework'), $only_in_elementor),
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
                        'xs' => '0 1px 2px rgba(15,23,42,0.05)',
                        's' => '0 6px 16px rgba(15,23,42,0.08)',
                        'm' => '0 12px 28px rgba(15,23,42,0.10)',
                        'l' => '0 20px 44px rgba(15,23,42,0.12)',
                        'xl' => '0 32px 72px rgba(15,23,42,0.14)',
                        'inner' => 'inset 0 2px 8px rgba(15,23,42,0.07)',
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

    private function render_root_font_impact_panel($settings) {
        $preview = $this->root_font_impact_preview_data($settings);
        $root_base_px = $preview['root_base_px'];
        $type_root_preview = $preview['type'];
        $spacing_root_preview = $preview['spacing'];
        $radius_root_preview = $preview['radius'];
        ?>
        <div class="ecf-root-font-impact"
             data-ecf-root-font-impact
             data-type-step="<?php echo esc_attr($type_root_preview['step'] ?? ($settings['typography']['scale']['base_index'] ?? 'm')); ?>"
             data-spacing-step="<?php echo esc_attr($spacing_root_preview['step'] ?? ($settings['spacing']['base_index'] ?? 'm')); ?>"
             data-radius-name="<?php echo esc_attr(sanitize_key($radius_root_preview['name'] ?? 'm')); ?>"
             data-label-type="<?php echo esc_attr__('Typography token', 'ecf-framework'); ?>"
             data-label-spacing="<?php echo esc_attr__('Spacing token', 'ecf-framework'); ?>"
             data-label-radius="<?php echo esc_attr__('Radius token', 'ecf-framework'); ?>"
             data-label-min="<?php echo esc_attr__('Minimum', 'ecf-framework'); ?>"
             data-label-max="<?php echo esc_attr__('Maximum', 'ecf-framework'); ?>"
             data-label-base="<?php echo esc_attr__('Current rem base', 'ecf-framework'); ?>">
            <div class="ecf-root-font-impact__header">
                <strong><?php echo esc_html__('Visible effect of the root font size', 'ecf-framework'); ?></strong>
                <span data-ecf-root-font-base><?php echo esc_html(sprintf(__('Currently: %spx = 1rem', 'ecf-framework'), $root_base_px)); ?></span>
            </div>
            <div class="ecf-root-font-impact__items">
                <div class="ecf-root-font-impact__item">
                    <span><?php echo esc_html__('Typography token', 'ecf-framework'); ?></span>
                    <div class="ecf-root-font-impact__token-row">
                        <code data-ecf-root-type-token><?php echo esc_html('--ecf-text-' . ($type_root_preview['step'] ?? ($settings['typography']['scale']['base_index'] ?? 'm'))); ?></code>
                        <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr__('Toggle clamp output', 'ecf-framework'); ?>">
                            <span class="dashicons dashicons-editor-code"></span>
                        </button>
                    </div>
                    <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-type-copy></button>
                    <div class="ecf-root-font-impact__range">
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-type-min-label><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-type-min><?php echo esc_html(($type_root_preview['min_px'] ?? $type_root_preview['minPx'] ?? '') . 'px'); ?></strong>
                            <em data-ecf-root-type-min-preview><?php echo esc_html($this->type_preview_text_for_step((string) ($type_root_preview['step'] ?? ($settings['typography']['scale']['base_index'] ?? 'm')), $settings)); ?></em>
                        </div>
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-type-max-label><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-type-max><?php echo esc_html(($type_root_preview['max_px'] ?? $type_root_preview['maxPx'] ?? '') . 'px'); ?></strong>
                            <em data-ecf-root-type-max-preview><?php echo esc_html($this->type_preview_text_for_step((string) ($type_root_preview['step'] ?? ($settings['typography']['scale']['base_index'] ?? 'm')), $settings)); ?></em>
                        </div>
                    </div>
                </div>
                <div class="ecf-root-font-impact__item">
                    <span><?php echo esc_html__('Spacing token', 'ecf-framework'); ?></span>
                    <div class="ecf-root-font-impact__token-row">
                        <code data-ecf-root-spacing-token><?php echo esc_html('--ecf-' . sanitize_key($settings['spacing']['prefix'] ?? 'space') . '-' . ($spacing_root_preview['step'] ?? ($settings['spacing']['base_index'] ?? 'm'))); ?></code>
                        <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr__('Toggle clamp output', 'ecf-framework'); ?>">
                            <span class="dashicons dashicons-editor-code"></span>
                        </button>
                    </div>
                    <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-spacing-copy></button>
                    <div class="ecf-root-font-impact__range">
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-spacing-min-label><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-spacing-min><?php echo esc_html(($spacing_root_preview['min_px'] ?? $spacing_root_preview['minPx'] ?? '') . 'px'); ?></strong>
                            <div class="ecf-root-font-impact__bar"><div class="ecf-root-font-impact__bar-fill" data-ecf-root-spacing-min-bar></div></div>
                        </div>
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-spacing-max-label><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-spacing-max><?php echo esc_html(($spacing_root_preview['max_px'] ?? $spacing_root_preview['maxPx'] ?? '') . 'px'); ?></strong>
                            <div class="ecf-root-font-impact__bar"><div class="ecf-root-font-impact__bar-fill" data-ecf-root-spacing-max-bar></div></div>
                        </div>
                    </div>
                </div>
                <div class="ecf-root-font-impact__item">
                    <span><?php echo esc_html__('Radius token', 'ecf-framework'); ?></span>
                    <div class="ecf-root-font-impact__token-row">
                        <code data-ecf-root-radius-token><?php echo esc_html('--ecf-radius-' . sanitize_key($radius_root_preview['name'] ?? 'm')); ?></code>
                        <button type="button" class="ecf-root-font-impact__copy-toggle" data-ecf-root-copy-toggle="<?php echo esc_attr__('Toggle clamp output', 'ecf-framework'); ?>">
                            <span class="dashicons dashicons-editor-code"></span>
                        </button>
                    </div>
                    <button type="button" class="ecf-root-font-impact__copy-pop" data-ecf-root-radius-copy></button>
                    <div class="ecf-root-font-impact__range">
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-radius-min-label><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-radius-min><?php echo esc_html($this->format_preview_number($radius_root_preview['min'] ?? 0) . 'px'); ?></strong>
                            <div class="ecf-root-font-impact__radius-preview" data-ecf-root-radius-min-preview></div>
                        </div>
                        <div class="ecf-root-font-impact__metric">
                            <span data-ecf-root-radius-max-label><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                            <strong data-ecf-root-radius-max><?php echo esc_html($this->format_preview_number($radius_root_preview['max'] ?? ($radius_root_preview['min'] ?? 0)) . 'px'); ?></strong>
                            <div class="ecf-root-font-impact__radius-preview" data-ecf-root-radius-max-preview></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_variables_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="variables">
            <div class="ecf-modal" data-ecf-search-edit-modal hidden>
                <div class="ecf-modal__backdrop" data-ecf-search-edit-close></div>
                <div class="ecf-modal__dialog ecf-search-edit-modal" role="dialog" aria-modal="true" aria-labelledby="ecf-search-edit-title">
                    <div class="ecf-modal__header">
                        <div>
                            <h2 id="ecf-search-edit-title"><?php echo esc_html__('Edit variable', 'ecf-framework'); ?></h2>
                            <p data-ecf-search-edit-subtitle><?php echo esc_html__('Adjust foreign Elementor variables directly from the search results.', 'ecf-framework'); ?></p>
                        </div>
                        <button type="button" class="ecf-modal__close" data-ecf-search-edit-close aria-label="<?php echo esc_attr__('Close', 'ecf-framework'); ?>">×</button>
                    </div>
                    <div class="ecf-modal__body">
                        <div class="ecf-search-edit-note" data-ecf-search-edit-note hidden></div>
                        <div class="ecf-search-edit-tech" data-ecf-search-edit-tech hidden></div>
                        <input type="hidden" data-ecf-search-edit-id>
                        <div class="ecf-form-grid ecf-form-grid--two">
                            <label>
                                <span><?php echo esc_html__('Variable name', 'ecf-framework'); ?></span>
                                <input type="text" data-ecf-search-edit-label>
                            </label>
                            <label>
                                <span><?php echo $this->tip_hover_label(__('Type', 'ecf-framework'), __('Choose Color for color values, Size for lengths like px/rem/clamp(...), and Text only for real text strings.', 'ecf-framework'), ''); ?></span>
                                <select data-ecf-search-edit-type>
                                    <option value="global-color-variable"><?php echo esc_html__('Color', 'ecf-framework'); ?></option>
                                    <option value="global-size-variable"><?php echo esc_html__('Size', 'ecf-framework'); ?></option>
                                    <option value="global-string-variable"><?php echo esc_html__('Text', 'ecf-framework'); ?></option>
                                </select>
                                <small class="ecf-search-edit-help" data-ecf-search-edit-type-help></small>
                            </label>
                            <label class="ecf-search-edit-color" data-ecf-search-edit-color-row>
                                <span><?php echo esc_html__('Color', 'ecf-framework'); ?></span>
                                <input type="color" data-ecf-search-edit-color value="#3b82f6">
                            </label>
                            <label class="ecf-search-edit-value">
                                <span><?php echo $this->tip_hover_label(__('Value', 'ecf-framework'), __('For Size, enter a simple number plus format like 24 + px. If the variable uses clamp(...), edit the Minimum and Maximum px values below instead.', 'ecf-framework'), ''); ?></span>
                                <div class="ecf-search-edit-clamp-fields" data-ecf-search-edit-clamp-fields hidden>
                                    <label>
                                        <span><?php echo $this->tip_hover_label(__('Minimum (px)', 'ecf-framework'), __('Smallest size of the clamp value, shown here in px for easier editing.', 'ecf-framework'), ''); ?></span>
                                        <input type="number" step="0.01" data-ecf-search-edit-clamp-min>
                                    </label>
                                    <label>
                                        <span><?php echo $this->tip_hover_label(__('Maximum (px)', 'ecf-framework'), __('Largest size of the clamp value, shown here in px for easier editing.', 'ecf-framework'), ''); ?></span>
                                        <input type="number" step="0.01" data-ecf-search-edit-clamp-max>
                                    </label>
                                </div>
                                <div class="ecf-search-edit-value-fields">
                                    <input type="text" data-ecf-search-edit-value>
                                    <select data-ecf-search-edit-format hidden>
                                        <option value="px">px</option>
                                        <option value="rem">rem</option>
                                        <option value="em">em</option>
                                        <option value="ch">ch</option>
                                        <option value="%">%</option>
                                        <option value="vw">vw</option>
                                        <option value="vh">vh</option>
                                        <option value="fx">f(x)</option>
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="ecf-modal__footer">
                        <button type="button" class="ecf-btn ecf-btn--ghost" data-ecf-search-edit-close><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span><?php echo esc_html__('Cancel', 'ecf-framework'); ?></span></button>
                        <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-search-edit-save><span class="dashicons dashicons-saved" aria-hidden="true"></span><span><?php echo esc_html__('Save', 'ecf-framework'); ?></span></button>
                    </div>
                </div>
            </div>
            <div class="ecf-modal" data-ecf-class-delete-modal hidden>
                <div class="ecf-modal__backdrop" data-ecf-class-delete-close></div>
                <div class="ecf-modal__dialog ecf-class-delete-modal" role="dialog" aria-modal="true" aria-labelledby="ecf-class-delete-title">
                    <div class="ecf-modal__header">
                        <div>
                            <h2 id="ecf-class-delete-title" data-ecf-class-delete-title><?php echo esc_html__('Used classes', 'ecf-framework'); ?></h2>
                            <p data-ecf-class-delete-subtitle><?php echo esc_html__('Some selected classes are still used on Elementor elements.', 'ecf-framework'); ?></p>
                        </div>
                        <button type="button" class="ecf-modal__close" data-ecf-class-delete-close aria-label="<?php echo esc_attr__('Close', 'ecf-framework'); ?>">×</button>
                    </div>
                    <div class="ecf-modal__body">
                        <p data-ecf-class-delete-message><?php echo esc_html__('Choose whether you want to delete all selected classes anyway or only the ones that are not currently used.', 'ecf-framework'); ?></p>
                        <div class="ecf-class-delete-summary">
                            <div>
                                <strong data-ecf-class-delete-used-count>0</strong>
                                <span><?php echo esc_html__('In use', 'ecf-framework'); ?></span>
                            </div>
                            <div>
                                <strong data-ecf-class-delete-unused-count>0</strong>
                                <span><?php echo esc_html__('Not in use', 'ecf-framework'); ?></span>
                            </div>
                        </div>
                        <div class="ecf-search-edit-note" data-ecf-class-delete-list></div>
                    </div>
                    <div class="ecf-modal__footer">
                        <button type="button" class="ecf-btn ecf-btn--ghost" data-ecf-class-delete-close><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span><?php echo esc_html__('Cancel', 'ecf-framework'); ?></span></button>
                        <button type="button" class="ecf-btn ecf-btn--secondary" data-ecf-class-delete-unused><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html__('Delete unused only', 'ecf-framework'); ?></span></button>
                        <button type="button" class="ecf-btn ecf-btn--danger" data-ecf-class-delete-all><span class="dashicons dashicons-warning" aria-hidden="true"></span><span><?php echo esc_html__('Delete all anyway', 'ecf-framework'); ?></span></button>
                    </div>
                </div>
            </div>
            <div class="ecf-modal" data-ecf-class-sync-prompt-modal hidden>
                <div class="ecf-modal__backdrop" data-ecf-class-sync-prompt-close></div>
                <div class="ecf-modal__dialog ecf-class-sync-prompt-modal" role="dialog" aria-modal="true" aria-labelledby="ecf-class-sync-prompt-title">
                    <div class="ecf-modal__header">
                        <div>
                            <h2 id="ecf-class-sync-prompt-title" data-ecf-class-sync-prompt-title><?php echo esc_html__('Sync to Elementor?', 'ecf-framework'); ?></h2>
                            <p data-ecf-class-sync-prompt-subtitle><?php echo esc_html__('Your changes were saved automatically.', 'ecf-framework'); ?></p>
                        </div>
                        <button type="button" class="ecf-modal__close" data-ecf-class-sync-prompt-close aria-label="<?php echo esc_attr__('Close', 'ecf-framework'); ?>">×</button>
                    </div>
                    <div class="ecf-modal__body">
                        <p data-ecf-class-sync-prompt-message><?php echo esc_html__('Some updated Layrix data is not yet available in Elementor. Do you want to sync it now?', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-modal__footer">
                        <button type="button" class="ecf-btn ecf-btn--ghost" data-ecf-class-sync-prompt-no><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span><?php echo esc_html__('No, maybe later', 'ecf-framework'); ?></span></button>
                        <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-class-sync-prompt-yes><span class="dashicons dashicons-update" aria-hidden="true"></span><span><?php echo esc_html__('Yes, sync now', 'ecf-framework'); ?></span></button>
                    </div>
                </div>
            </div>
            <div class="ecf-card ecf-starter-classes ecf-variable-library ecf-panel-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Variable library', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Manage Layrix and foreign Elementor variables here. This lets you see immediately what already exists, what comes from Layrix, and what can later be synced or cleaned up safely.', 'ecf-framework'); ?></p>
                <?php if ($show_elementor_status_cards): ?>
                    <div class="ecf-class-limit-card ecf-class-limit-card--compact ecf-class-limit-card--<?php echo esc_attr($elementor_variable_limit_status); ?> ecf-starter-classes__status">
                        <div class="ecf-class-limit-card__eyebrow"><?php echo esc_html__('Elementor Variables', 'ecf-framework'); ?></div>
                        <div class="ecf-class-limit-card__hero">
                            <div class="ecf-class-limit-card__headline">
                                <span class="ecf-class-limit-card__usage">
                                    <span id="ecf-total-variables"><?php echo esc_html((string) $native_variable_counts['total']); ?></span>
                                    <span class="ecf-class-limit-card__slash">/</span>
                                    <span id="ecf-limit-variables"><?php echo esc_html((string) $elementor_variable_limit); ?></span>
                                </span>
                                <span class="ecf-class-limit-card__context"><?php echo esc_html__('Variables currently available in Elementor', 'ecf-framework'); ?></span>
                            </div>
                        </div>
                        <ul class="ecf-class-limit-card__details ecf-class-limit-card__details--variables">
                            <li>
                                <span><?php echo esc_html__('From Layrix', 'ecf-framework'); ?></span>
                                <strong><span id="ecf-total-ecf-variables"><?php echo esc_html((string) $native_variable_counts['ecf']); ?></span></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html__('Only in Elementor', 'ecf-framework'); ?></span>
                                <strong><span id="ecf-total-foreign-variables"><?php echo esc_html((string) $native_variable_counts['foreign']); ?></span></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html__('Ready for sync', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-layrix-variable-count><?php echo esc_html((string) ($layrix_variable_count ?? 0)); ?></span></strong>
                            </li>
                        </ul>
                        <p class="ecf-class-limit-card__explain"><?php echo esc_html__('The limit comes from your installed Elementor version. Layrix shows what is already there, what belongs to Layrix and what is still only stored in Elementor.', 'ecf-framework'); ?></p>
                    </div>
                <?php endif; ?>
                <p class="ecf-panel-note"><?php echo esc_html__('Changes take effect immediately in Elementor. The cache is cleared automatically; open Elementor tabs should be reloaded once.', 'ecf-framework'); ?></p>
                <div class="ecf-grid ecf-grid--variables" data-ecf-layout-group="variables-main">
                    <div class="ecf-card" id="ecf-vars-ecf" data-ecf-layout-item="ecf-vars-ecf">
                        <div class="ecf-vargroup-header">
                            <div class="ecf-vargroup-title">
                                <h2><?php echo esc_html__('Layrix Variablen', 'ecf-framework'); ?> <span class="ecf-badge" id="ecf-badge-ecf">–</span></h2>
                                <p class="ecf-vargroup-subtitle"><?php echo esc_html__('Generated design tokens managed by Layrix and ready for sync or cleanup.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-vargroup-tools">
                                <div class="ecf-vargroup-actions">
                                    <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="ecf">
                                        <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                        <span><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                                    </button>
                                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="ecf" aria-label="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>" data-tip="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>">
                                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="ecf-global-search-card ecf-global-search-card--embedded">
                            <div class="ecf-global-search-card__intro">
                                <div>
                                    <h3><?php echo esc_html__('Find and review variables', 'ecf-framework'); ?></h3>
                                    <p><?php echo esc_html__('Search across Layrix and foreign Elementor variables, then jump straight into cleanup or direct editing.', 'ecf-framework'); ?></p>
                                </div>
                            </div>
                            <div class="ecf-global-search">
                                <label class="ecf-global-search__field">
                                    <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                    <input type="search" id="ecf-global-search-input" placeholder="<?php echo esc_attr__('Search variables…', 'ecf-framework'); ?>" autocomplete="off">
                                </label>
                                <div class="ecf-global-search__results" id="ecf-global-search-results" hidden></div>
                            </div>
                        </div>
                        <div id="ecf-varlist-ecf" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html__('Loading…', 'ecf-framework'); ?></p></div>
                    </div>
                    <div class="ecf-card" id="ecf-vars-foreign" data-ecf-layout-item="ecf-vars-foreign">
                        <div class="ecf-vargroup-header">
                            <div class="ecf-vargroup-title">
                                <h2><?php echo esc_html__('Foreign Variables', 'ecf-framework'); ?> <span class="ecf-badge" id="ecf-badge-foreign">–</span></h2>
                                <p class="ecf-vargroup-subtitle"><?php echo esc_html__('Variables that already exist in Elementor outside Layrix. Review them here before editing or deleting.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-vargroup-tools">
                                <div class="ecf-vargroup-actions">
                                    <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="foreign">
                                        <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                        <span><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                                    </button>
                                    <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="foreign" aria-label="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>" data-tip="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>">
                                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="ecf-varlist-foreign" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html__('Loading…', 'ecf-framework'); ?></p></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_tokens_panel($settings) {
        ?>
        <div class="ecf-panel is-active" data-panel="tokens">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="tokens-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Token library', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Manage the core design tokens for color and radius in one place before they flow into your previews, classes and Elementor sync.', 'ecf-framework'); ?></p>
                <div class="ecf-grid" data-ecf-layout-group="tokens-main" data-ecf-masonry-layout="1">
                    <div class="ecf-card" data-ecf-layout-item="tokens-colors">
                        <h2><?php echo esc_html__('Colors', 'ecf-framework'); ?></h2>
                        <?php $this->render_rows('colors', $settings['colors']); ?>
                    </div>
                    <div class="ecf-card" data-ecf-layout-item="tokens-radius">
                        <h2><?php echo esc_html__('Radius', 'ecf-framework'); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <?php $this->render_rows('radius', $settings['radius']); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_shadows_panel($settings) {
        ?>
        <div class="ecf-panel" data-panel="shadows">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="shadows-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Shadows', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Adjust your box-shadow tokens and review the matching utility classes in one shared workspace.', 'ecf-framework'); ?></p>
                <div class="ecf-shadow-layout" data-ecf-layout-group="shadows-main">
                    <div class="ecf-card ecf-shadow-preview-card"
                     data-ecf-layout-item="shadows-main-card"
                     data-ecf-shadow-preview
                     data-active-shadow="<?php echo esc_attr(sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?>"
            data-preview-word="<?php echo esc_attr__('Shadow', 'ecf-framework'); ?>"
            data-preview-helper="<?php echo esc_attr__('Click a shadow row below to jump directly into the editable value.', 'ecf-framework'); ?>">
                    <div class="ecf-shadow-preview-header">
                        <div>
          <h2><?php echo esc_html__('Box Shadow Variables & Classes', 'ecf-framework'); ?></h2>
          <p><?php echo esc_html__('Edit your shadow tokens directly here. The matching classes like ecf-shadow-xs or ecf-shadow-l can now be enabled in the Utility classes sync.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <div class="ecf-shadow-focus" data-ecf-shadow-focus>
                        <div class="ecf-shadow-focus__meta">
            <span class="ecf-preview-pill"><?php echo esc_html__('Preview', 'ecf-framework'); ?></span>
                            <strong data-ecf-shadow-token><?php echo esc_html('--ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></strong>
                            <div class="ecf-shadow-focus__class">
                                <span><?php echo esc_html__('Class', 'ecf-framework'); ?></span>
                                <code data-ecf-shadow-class><?php echo esc_html('ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></code>
                            </div>
            <p data-ecf-shadow-helper><?php echo esc_html__('You can edit the shadow value directly in the row below.', 'ecf-framework'); ?></p>
            <?php
            $current_shadow_slug = sanitize_key($settings['shadows'][0]['name'] ?? 'xs');
            $this->render_field_token_pills([
                ['type' => __('Variable', 'ecf-framework'), 'value' => '--ecf-shadow-' . $current_shadow_slug],
                ['type' => __('Class', 'ecf-framework'), 'value' => 'ecf-shadow-' . $current_shadow_slug],
            ]);
            ?>
                        </div>
                        <div class="ecf-shadow-focus__sample ecf-shadow-preview-bg">
                            <div class="ecf-shadow-focus__surface ecf-shadow-preview-bg" data-ecf-shadow-surface style="box-shadow:<?php echo esc_attr($settings['shadows'][0]['value'] ?? '0 1px 2px rgba(0,0,0,0.05)'); ?>;">
                                <span class="ecf-shadow-preview-label" data-ecf-shadow-label><?php echo esc_html('--ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></span>
                                <strong data-ecf-shadow-name><?php echo esc_html(ucfirst(sanitize_key($settings['shadows'][0]['name'] ?? 'xs'))); ?></strong>
                                <small data-ecf-shadow-css><?php echo esc_html($settings['shadows'][0]['value'] ?? '0 1px 2px rgba(0,0,0,0.05)'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="ecf-shadow-preview-list" data-ecf-shadow-preview-list>
                        <?php foreach ($settings['shadows'] as $index => $row): ?>
                            <?php $shadow_name = sanitize_key($row['name']); ?>
                            <button type="button" class="ecf-shadow-row<?php echo $index === 0 ? ' is-active' : ''; ?>" data-ecf-shadow-step="<?php echo esc_attr($shadow_name); ?>" data-ecf-shadow-index="<?php echo esc_attr((string) $index); ?>">
                                <div class="ecf-shadow-row__token"><?php echo esc_html('--ecf-shadow-' . $shadow_name); ?></div>
                                <div class="ecf-shadow-row__value"><code><?php echo esc_html($row['value']); ?></code></div>
                                <div class="ecf-shadow-row__sample ecf-shadow-preview-bg">
                                    <div class="ecf-shadow-row__mini" style="box-shadow:<?php echo esc_attr($row['value']); ?>;"></div>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-shadow-editor">
                        <div class="ecf-shadow-editor__header">
                            <h3><?php echo esc_html__('Edit shadow values', 'ecf-framework'); ?></h3>
                            <p><?php echo wp_kses(__('Values in CSS box-shadow syntax, e.g. <code>0 4px 16px rgba(0,0,0,0.1)</code>.', 'ecf-framework'), ['code' => []]); ?></p>
                        </div>
                        <?php $this->render_rows('shadows', $settings['shadows']); ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_utilities_panel($args) {
        extract($args, EXTR_SKIP);
        $active_class_snapshot = $this->get_active_class_snapshot($settings);
        $active_basic_classes = $active_class_snapshot['basic'];
        $active_extra_classes = $active_class_snapshot['extras'];
        $active_custom_classes = $active_class_snapshot['custom'];
        $active_utility_classes = $active_class_snapshot['utility'];
        $active_helper_classes = $active_class_snapshot['helper'];
        $active_selected_classes = $active_class_snapshot['selected'];
        $active_sync_payload_classes = $active_class_snapshot['sync_payload'];
        $starter_basic_total = count($starter_class_library['basic'] ?? []);
        $starter_extras_total = count($starter_class_library['advanced'] ?? []);
        $utility_total = 0;
        foreach ($utility_class_library as $utility_items) {
            $utility_total += count((array) $utility_items);
        }
        $custom_total = 0;
        foreach (($settings['starter_classes']['custom'] ?? []) as $row) {
            if (trim((string) ($row['name'] ?? '')) !== '') {
                $custom_total++;
            }
        }
        $class_usage_percent = $elementor_class_limit > 0 ? (int) round(($elementor_total_class_count / $elementor_class_limit) * 100) : 0;
        $class_usage_percent = max(0, min(100, $class_usage_percent));
        $starter_tab_icons = [
            'all' => 'dashicons-screenoptions',
            'website_sections' => 'dashicons-admin-site-alt3',
            'layout_content' => 'dashicons-layout',
            'interaction' => 'dashicons-button',
            'custom' => 'dashicons-edit',
        ];
        $utility_tab_icons = [
            'all' => 'dashicons-screenoptions',
            'typography' => 'dashicons-editor-textcolor',
            'text' => 'dashicons-editor-paragraph',
            'layout' => 'dashicons-layout',
            'shadows' => 'dashicons-admin-appearance',
            'accessibility' => 'dashicons-universal-access',
        ];
        $starter_library_features = [
            __('Starter classes for common page elements like header, hero, buttons or footer.', 'ecf-framework'),
            __('Consistent, semantic naming instead of improvised labels per page.', 'ecf-framework'),
            __('Keeps naming and Elementor sync manageable while staying below the 100-class limit.', 'ecf-framework'),
        ];
        $utility_library_features = [
            __('Curated helper styles for headings, text and a few safe layout patterns.', 'ecf-framework'),
            __('Useful when you want a small reusable utility layer without reviving the old full utility flood.', 'ecf-framework'),
            __('Utilities also count toward Elementor’s 100 global classes and should stay intentionally compact.', 'ecf-framework'),
        ];
        $bem_generator_presets = [
            'header' => [
                'label' => __('Header', 'ecf-framework'),
                'category' => 'navigation',
                'help' => __('For brand, navigation and actions in the top area.', 'ecf-framework'),
                'elements' => ['inner', 'brand', 'nav', 'actions'],
                'modifiers' => ['sticky', 'dark', 'transparent'],
            ],
            'hero' => [
                'label' => __('Hero', 'ecf-framework'),
                'category' => 'hero',
                'help' => __('For the main intro section with copy, media and CTAs.', 'ecf-framework'),
                'elements' => ['content', 'eyebrow', 'title', 'text', 'media', 'actions'],
                'modifiers' => ['dark', 'accent', 'split'],
            ],
            'content' => [
                'label' => __('Content', 'ecf-framework'),
                'category' => 'content',
                'help' => __('For normal content blocks like text, media, lists or side content.', 'ecf-framework'),
                'elements' => ['title', 'text', 'media', 'meta', 'list', 'item', 'actions'],
                'modifiers' => ['highlight', 'compact', 'wide'],
            ],
            'section' => [
                'label' => __('Section', 'ecf-framework'),
                'category' => 'sections',
                'help' => __('For larger page sections and themed wrappers.', 'ecf-framework'),
                'elements' => ['inner', 'header', 'body', 'footer'],
                'modifiers' => ['dark', 'accent', 'spacious'],
            ],
            'card' => [
                'label' => __('Card', 'ecf-framework'),
                'category' => 'cards',
                'help' => __('For grouped content surfaces like cards, teasers or tiles.', 'ecf-framework'),
                'elements' => ['media', 'body', 'title', 'text', 'meta', 'actions'],
                'modifiers' => ['featured', 'compact', 'outlined'],
            ],
            'button' => [
                'label' => __('Button', 'ecf-framework'),
                'category' => 'buttons',
                'help' => __('For CTA buttons with icons, labels and variants.', 'ecf-framework'),
                'elements' => ['icon', 'label'],
                'modifiers' => ['primary', 'secondary', 'ghost', 'large'],
            ],
            'form' => [
                'label' => __('Form', 'ecf-framework'),
                'category' => 'forms',
                'help' => __('For forms, groups, fields and actions.', 'ecf-framework'),
                'elements' => ['group', 'field', 'label', 'hint', 'actions'],
                'modifiers' => ['inline', 'compact', 'stacked'],
            ],
            'footer' => [
                'label' => __('Footer', 'ecf-framework'),
                'category' => 'footer',
                'help' => __('For the lower website area with columns, links and meta info.', 'ecf-framework'),
                'elements' => ['inner', 'brand', 'nav', 'meta', 'actions'],
                'modifiers' => ['dark', 'minimal', 'split'],
            ],
            'custom' => [
                'label' => __('Custom section', 'ecf-framework'),
                'category' => 'custom',
                'help' => __('Use your own block name and build a small BEM family around it.', 'ecf-framework'),
                'elements' => ['title', 'text', 'media', 'actions'],
                'modifiers' => ['primary', 'secondary', 'dark'],
            ],
        ];
        $starter_library_tooltip = '• ' . implode("\n• ", $starter_library_features);
        $utility_library_tooltip = '• ' . implode("\n• ", $utility_library_features);
        $custom_class_suggestions = [
            __('Marketing', 'ecf-framework') => ['banner', 'cta', 'promo', 'offer'],
            __('Content', 'ecf-framework') => ['teaser', 'feature', 'highlight', 'story'],
            __('Trust', 'ecf-framework') => ['testimonial', 'review', 'logos', 'proof'],
            __('Commerce', 'ecf-framework') => ['pricing', 'plan', 'faq', 'contact'],
        ];
        ?>
        <div class="ecf-panel" data-panel="utilities">
            <div class="ecf-grid" data-ecf-layout-group="utilities-main">
                <div class="ecf-card ecf-starter-classes ecf-panel-shell"
                     data-ecf-layout-item="utilities-library"
                     data-ecf-starter-classes
                    data-ecf-class-current="<?php echo esc_attr((string) $elementor_total_class_count); ?>"
                     data-ecf-class-limit="<?php echo esc_attr((string) $elementor_class_limit); ?>"
                     data-ecf-existing-labels="<?php echo esc_attr(wp_json_encode($elementor_existing_class_labels)); ?>">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html__('Class library', 'ecf-framework'); ?></h2>
                    </div>
                    <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Use starter classes for semantic naming and utility classes for a compact curated helper set. Both count toward Elementor’s 100-class limit.', 'ecf-framework'); ?></p>
                    <div class="ecf-class-limit-card ecf-class-limit-card--compact ecf-class-limit-card--<?php echo esc_attr($elementor_class_limit_status); ?> ecf-starter-classes__status" data-ecf-starter-status>
                        <div class="ecf-class-limit-card__eyebrow"><?php echo esc_html__('Class usage overview', 'ecf-framework'); ?></div>
                        <div class="ecf-class-limit-card__hero">
                            <div class="ecf-class-limit-card__headline">
                                <span class="ecf-class-limit-card__usage">
                                    <span data-ecf-starter-projected><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    <span><?php echo esc_html__('of', 'ecf-framework'); ?></span>
                                    <span data-ecf-starter-limit><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                </span>
                                <span><?php echo esc_html__('classes used', 'ecf-framework'); ?></span>
                            </div>
                        </div>
                        <div class="ecf-class-limit-card__progress" aria-hidden="true">
                            <span data-ecf-starter-progress style="width:<?php echo esc_attr((string) $class_usage_percent); ?>%"></span>
                        </div>
                        <ul class="ecf-class-limit-card__details">
                            <li>
                                <span><?php echo esc_html__('Ready from Layrix', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-starter-selected><?php echo esc_html((string) count($active_sync_payload_classes)); ?></span></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html__('Only in Elementor', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-existing-foreign-summary-count><?php echo esc_html((string) max(0, $elementor_class_total_count - count($active_sync_payload_classes))); ?></span></strong>
                            </li>
                        </ul>
                        <p class="ecf-class-limit-card__explain"><?php echo esc_html__('This shows the total class load inside Elementor. Below you can decide which classes come from Layrix and which only remain inside Elementor.', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-var-tabs ecf-class-tier-tabs" data-ecf-class-tier-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-class-tier="all" data-ecf-tier-title="<?php echo esc_attr__('Active classes', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Review which selected Layrix classes are currently part of your sync and what still only exists in Elementor.', 'ecf-framework'); ?>">
                            <?php echo esc_html__('All', 'ecf-framework'); ?>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="basic" data-ecf-tier-title="<?php echo esc_attr__('Basic classes', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Use foundational starter classes for semantic page structure, layout wrappers and the core building blocks of your site.', 'ecf-framework'); ?>" <?php echo empty($active_basic_classes) ? 'hidden' : ''; ?>>
                            <?php echo esc_html__('Basic', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-basic-count><?php echo esc_html(count($active_basic_classes) . '/' . $starter_basic_total); ?></span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="extras" data-ecf-tier-title="<?php echo esc_attr__('Extra classes', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Browse extended starter classes for additional sections, forms, trust blocks, commerce patterns and richer page structures.', 'ecf-framework'); ?>" <?php echo empty($active_extra_classes) ? 'hidden' : ''; ?>>
                            <?php echo esc_html__('Extras', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-extras-count><?php echo esc_html(count($active_extra_classes) . '/' . $starter_extras_total); ?></span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="utility" data-ecf-tier-title="<?php echo esc_attr__('Utility classes', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Enable small helper classes for text, alignment, spacing and a few safe visual shortcuts without bloating your class system.', 'ecf-framework'); ?>" <?php echo empty($active_utility_classes) ? 'hidden' : ''; ?>>
                            <?php echo esc_html__('Utility', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-utility-summary-count><?php echo esc_html(count($active_utility_classes) . '/' . $utility_total); ?></span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="custom" data-ecf-tier-title="<?php echo esc_attr__('Own classes', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Add your own semantic class names for project-specific naming that does not fit the predefined starter library.', 'ecf-framework'); ?>" <?php echo empty($active_custom_classes) ? 'hidden' : ''; ?>>
                            <?php echo esc_html__('Custom', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-custom-count><?php echo esc_html(count($active_custom_classes) . '/' . $custom_total); ?></span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="existing-foreign" data-ecf-tier-title="<?php echo esc_attr__('Only in Elementor', 'ecf-framework'); ?>" data-ecf-tier-copy="<?php echo esc_attr__('Review classes that already exist in Elementor but are not part of your current Layrix selection.', 'ecf-framework'); ?>" <?php echo ($elementor_class_total_count - count($active_sync_payload_classes)) <= 0 ? 'hidden' : ''; ?>>
                            <?php echo esc_html__('Only in Elementor', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-existing-foreign-summary-count><?php echo esc_html((string) max(0, $elementor_class_total_count - count($active_sync_payload_classes))); ?></span>
                        </button>
                    </div>
                    <?php wp_nonce_field('ecf_class_library_sync', '_ecf_class_library_sync_nonce'); ?>
                    <div class="ecf-library-section" data-ecf-library-section="active">
                        <div class="ecf-class-workspace__header ecf-class-workspace__header--active">
                            <h3 data-ecf-active-workspace-title><?php echo esc_html__('Active classes', 'ecf-framework'); ?></h3>
                            <p data-ecf-active-workspace-copy><?php echo esc_html__('Review which selected Layrix classes are currently part of your sync and what still only exists in Elementor.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-active-class-summary" data-ecf-active-class-summary>
                            <div class="ecf-active-class-summary__grid">
                                <div class="ecf-active-class-summary__item ecf-active-class-summary__item--total" data-ecf-active-summary-item="total" data-tip="<?php echo esc_attr__('Total number of classes that Layrix would sync from the current selection.', 'ecf-framework'); ?>">
                                    <span><?php echo esc_html__('Layrix sync total', 'ecf-framework'); ?></span>
                                    <strong data-ecf-active-total-count><?php echo esc_html((string) count($active_sync_payload_classes)); ?></strong>
                                    <p class="ecf-active-class-summary__copy" data-ecf-active-summary-copy="total"><?php echo esc_html__('These classes are currently ready to come from Layrix into Elementor during the next sync.', 'ecf-framework'); ?></p>
                                </div>
                                <div class="ecf-active-class-summary__item" data-ecf-active-summary-item="existing-foreign" data-tip="<?php echo esc_attr__('Classes that already exist in Elementor but are not part of your current Layrix selection.', 'ecf-framework'); ?>" <?php echo ($elementor_class_total_count - count($active_sync_payload_classes)) <= 0 ? 'hidden' : ''; ?>>
                                    <span><?php echo esc_html__('Only in Elementor', 'ecf-framework'); ?></span>
                                    <strong data-ecf-active-existing-foreign-count><?php echo esc_html((string) max(0, $elementor_class_total_count - count($active_sync_payload_classes))); ?></strong>
                                    <p class="ecf-active-class-summary__copy"><?php echo esc_html__('These classes already exist there, but they are not currently selected in Layrix.', 'ecf-framework'); ?></p>
                                </div>
                            </div>
                            <p class="ecf-class-library-actions__hint" data-ecf-active-class-hint>
                                <?php
                                echo esc_html(
                                    !empty($active_helper_classes)
                                        ? __('The sync total includes the automatic helper class ecf-container-boxed, because a boxed Elementor width is currently active.', 'ecf-framework')
                                        : __('The sync total matches your currently selected starter, utility and own classes.', 'ecf-framework')
                                );
                                ?>
                            </p>
                            <div class="ecf-active-class-groups" data-ecf-active-class-groups>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="basic" <?php echo empty($active_basic_classes) ? 'hidden' : ''; ?> open>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Basic active classes', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge"><?php echo esc_html((string) count($active_basic_classes)); ?></span>
                                    </summary>
                                    <ul class="ecf-active-class-list" data-ecf-active-class-list="basic">
                                        <?php foreach ($active_basic_classes as $class_name): ?>
                                            <li class="ecf-active-class-item"><?php echo esc_html($class_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="extras" <?php echo empty($active_extra_classes) ? 'hidden' : ''; ?>>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Extra active classes', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge"><?php echo esc_html((string) count($active_extra_classes)); ?></span>
                                    </summary>
                                    <ul class="ecf-active-class-list" data-ecf-active-class-list="extras">
                                        <?php foreach ($active_extra_classes as $class_name): ?>
                                            <li class="ecf-active-class-item"><?php echo esc_html($class_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="utility" <?php echo empty($active_utility_classes) ? 'hidden' : ''; ?><?php echo !empty($active_basic_classes) ? '' : ' open'; ?>>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Active utility classes', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge"><?php echo esc_html((string) count($active_utility_classes)); ?></span>
                                    </summary>
                                    <ul class="ecf-active-class-list" data-ecf-active-class-list="utility">
                                        <?php foreach ($active_utility_classes as $class_name): ?>
                                            <li class="ecf-active-class-item"><?php echo esc_html($class_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="custom" <?php echo empty($active_custom_classes) ? 'hidden' : ''; ?>>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Own active classes', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge"><?php echo esc_html((string) count($active_custom_classes)); ?></span>
                                    </summary>
                                    <ul class="ecf-active-class-list" data-ecf-active-class-list="custom">
                                        <?php foreach ($active_custom_classes as $class_name): ?>
                                            <li class="ecf-active-class-item"><?php echo esc_html($class_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="helper" <?php echo empty($active_helper_classes) ? 'hidden' : ''; ?>>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Automatic helper classes', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge"><?php echo esc_html((string) count($active_helper_classes)); ?></span>
                                    </summary>
                                    <ul class="ecf-active-class-list" data-ecf-active-class-list="helper">
                                        <?php foreach ($active_helper_classes as $class_name): ?>
                                            <li class="ecf-active-class-item ecf-active-class-item--helper"><?php echo esc_html($class_name); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                                <details class="ecf-active-class-group" data-ecf-active-class-group="existing-foreign" hidden>
                                    <summary class="ecf-active-class-group__summary">
                                        <span><?php echo esc_html__('Only in Elementor', 'ecf-framework'); ?></span>
                                        <span class="ecf-badge" data-ecf-active-existing-foreign-badge><?php echo esc_html((string) max(0, $elementor_class_total_count - count($active_sync_payload_classes))); ?></span>
                                    </summary>
                                    <p class="ecf-class-library-actions__hint"><?php echo esc_html__('These classes already exist in Elementor, but are not part of your current Layrix selection.', 'ecf-framework'); ?></p>
                                    <div class="ecf-active-class-list" data-ecf-active-class-list="existing-foreign"></div>
                                </details>
                            </div>
                        </div>
                    </div>
                    <div class="ecf-library-section" data-ecf-library-section="starter" hidden>
                    <div class="ecf-class-workspace ecf-class-workspace--starter">
                    <div class="ecf-class-workspace__header">
                        <h3 data-ecf-class-workspace-title><?php echo esc_html__('Basic', 'ecf-framework'); ?></h3>
                        <p data-ecf-class-workspace-copy><?php echo esc_html__('Use starter classes for semantic naming and utility classes for a compact curated helper set. Both count toward Elementor’s 100-class limit.', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-class-filterbar" data-ecf-starter-filterbar>
                        <label class="ecf-class-filterbar__field">
                            <span class="ecf-class-filterbar__label"><?php echo esc_html__('Area', 'ecf-framework'); ?></span>
                            <select data-ecf-starter-select data-tip="<?php echo esc_attr__('Filter the starter classes by area.', 'ecf-framework'); ?>">
                                <?php foreach ($starter_class_tabs as $tab_key => $tab): ?>
                                    <?php if ($tab_key === 'custom') continue; ?>
                                    <option value="<?php echo esc_attr($tab_key); ?>" <?php selected($tab_key, 'all'); ?>><?php echo esc_html($tab['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="ecf-class-library-actions">
                        <button type="button" class="ecf-btn ecf-btn--secondary ecf-class-select-all" data-ecf-class-select-all>
                            <span class="ecf-select-all__icon" data-ecf-class-select-all-icon aria-hidden="true"></span>
                            <span data-ecf-class-select-all-label><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                        </button>
                        <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--class-sync" data-ecf-class-sync-button data-ecf-class-sync-url="<?php echo esc_url(admin_url('admin-post.php?action=ecf_class_library_sync')); ?>">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <span><?php echo esc_html__('Sync with Elementor', 'ecf-framework'); ?></span>
                        </button>
                    </div>
                    <p class="ecf-class-library-actions__hint"><?php echo esc_html__('Start the sync to apply the currently selected classes to Elementor.', 'ecf-framework'); ?></p>
                    <div class="ecf-global-search ecf-class-search-card">
                        <label class="ecf-global-search__field">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <input type="search" data-ecf-class-search placeholder="<?php echo esc_attr__('Search classes…', 'ecf-framework'); ?>" autocomplete="off">
                        </label>
                    </div>
                    <div class="ecf-bem-generator" data-ecf-bem-generator data-ecf-bem-presets="<?php echo esc_attr(wp_json_encode($bem_generator_presets)); ?>">
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html__('BEM class generator', 'ecf-framework'); ?></h3>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html__('Choose a section, add elements or modifiers, and generate clean ECF BEM names for your own classes.', 'ecf-framework'); ?></p>
                        <div class="ecf-bem-generator__grid">
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html__('Area', 'ecf-framework'); ?></span>
                                <select data-ecf-bem-preset>
                                    <?php foreach ($bem_generator_presets as $preset_key => $preset): ?>
                                        <option value="<?php echo esc_attr($preset_key); ?>"><?php echo esc_html($preset['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-class-filterbar__field" data-ecf-bem-block-field>
                                <span class="ecf-class-filterbar__label"><?php echo esc_html__('Own block name', 'ecf-framework'); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-block placeholder="<?php echo esc_attr__('optional, e.g. testimonials', 'ecf-framework'); ?>">
                            </label>
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html__('Additional elements', 'ecf-framework'); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-extra-elements placeholder="<?php echo esc_attr__('e.g. subtitle, badge', 'ecf-framework'); ?>">
                            </label>
                            <label class="ecf-class-filterbar__field">
                                <span class="ecf-class-filterbar__label"><?php echo esc_html__('Additional modifiers', 'ecf-framework'); ?></span>
                                <input type="text" class="ecf-input" data-ecf-bem-extra-modifiers placeholder="<?php echo esc_attr__('e.g. dark, compact', 'ecf-framework'); ?>">
                            </label>
                        </div>
                        <p class="ecf-class-library-actions__hint" data-ecf-bem-help></p>
                        <div class="ecf-bem-generator__pickers">
                            <div class="ecf-bem-generator__picker">
                                <strong><?php echo esc_html__('Elements', 'ecf-framework'); ?></strong>
                                <div class="ecf-bem-generator__options" data-ecf-bem-elements></div>
                            </div>
                            <div class="ecf-bem-generator__picker">
                                <strong><?php echo esc_html__('Modifiers', 'ecf-framework'); ?></strong>
                                <div class="ecf-bem-generator__options" data-ecf-bem-modifiers></div>
                            </div>
                        </div>
                        <div class="ecf-bem-generator__preview">
                            <strong><?php echo esc_html__('Preview', 'ecf-framework'); ?></strong>
                            <div class="ecf-bem-generator__preview-list" data-ecf-bem-preview></div>
                        </div>
                        <div class="ecf-class-library-actions ecf-class-library-actions--generator">
                            <button type="button" class="ecf-btn ecf-btn--secondary" data-ecf-bem-reset>
                                <span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
                                <span><?php echo esc_html__('Reset', 'ecf-framework'); ?></span>
                            </button>
                            <button type="button" class="ecf-btn ecf-btn--primary" data-ecf-bem-add>
                                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                                <span><?php echo esc_html__('Add as custom classes', 'ecf-framework'); ?></span>
                            </button>
                        </div>
                        <p class="ecf-class-library-actions__hint" data-ecf-bem-feedback></p>
                    </div>
                    <div class="ecf-starter-class-list">
                        <?php foreach ($starter_class_library as $tier => $classes): ?>
                            <?php foreach ($classes as $class): ?>
                                <?php $class_name = $class['name']; ?>
                                <?php $class_tab = $this->starter_class_tab_for_category($class['category']); ?>
                                <label class="ecf-starter-class-item"
                                       data-ecf-starter-item
                                       data-tier="<?php echo esc_attr($tier); ?>"
                                       data-category="<?php echo esc_attr($class['category']); ?>"
                                       data-tabgroup="<?php echo esc_attr($class_tab); ?>"
                                       data-class-name="<?php echo esc_attr($class_name); ?>"
                                       data-tip="<?php echo esc_attr($this->starter_class_tooltip($class_name, $class['category'], $tier)); ?>">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($this->option_name); ?>[starter_classes][enabled][<?php echo esc_attr($class_name); ?>]"
                                           value="1"
                                           class="ecf-starter-class-toggle"
                                           <?php checked(!empty($settings['starter_classes']['enabled'][$class_name])); ?>>
                                    <span class="ecf-starter-class-item__badge ecf-starter-class-item__badge--<?php echo esc_attr($tier); ?>"><?php echo esc_html(ucfirst($tier)); ?></span>
                                    <span class="ecf-starter-class-item__name"><?php echo esc_html($class_name); ?></span>
                                    <span class="ecf-starter-class-item__meta"><?php echo esc_html($starter_class_categories[$class['category']] ?? ucfirst($class['category'])); ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-starter-custom" data-ecf-starter-custom-section>
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html__('Custom classes', 'ecf-framework'); ?></h3>
                        </div>
                        <div class="ecf-custom-suggestions" data-ecf-custom-suggestions>
                            <p class="ecf-class-library-actions__hint"><?php echo esc_html__('Suggestions for quick, clean custom names. Click a chip to insert it into a free row.', 'ecf-framework'); ?></p>
                            <?php foreach ($custom_class_suggestions as $suggestion_group => $suggestions): ?>
                                <div class="ecf-custom-suggestions__group">
                                    <strong><?php echo esc_html($suggestion_group); ?></strong>
                                    <div class="ecf-custom-suggestions__chips">
                                        <?php foreach ($suggestions as $suggestion): ?>
                                            <button type="button" class="ecf-custom-suggestion-chip" data-ecf-custom-suggestion="<?php echo esc_attr('ecf-' . $suggestion); ?>">
                                                <?php echo esc_html('ecf-' . $suggestion); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ecf-starter-custom-rows" data-ecf-starter-custom-rows>
                            <?php foreach (($settings['starter_classes']['custom'] ?? []) as $index => $row): ?>
                                <div class="ecf-starter-custom-row">
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][enabled]" value="1" class="ecf-custom-starter-enabled" <?php checked(!empty($row['enabled'])); ?>>
                                        <span><?php echo esc_html__('Active', 'ecf-framework'); ?></span>
                                    </label>
                                    <input type="text" data-ecf-slug-field="token" name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr($row['name'] ?? ''); ?>" placeholder="ecf-banner" class="ecf-custom-starter-name">
                                    <select name="<?php echo esc_attr($this->option_name); ?>[starter_classes][custom][<?php echo esc_attr((string) $index); ?>][category]" class="ecf-custom-starter-category">
                                        <?php foreach ($starter_class_categories as $category_key => $category_label): ?>
                                            <?php if ($category_key === 'all') continue; ?>
                                            <option value="<?php echo esc_attr($category_key); ?>" <?php selected(($row['category'] ?? 'custom'), $category_key); ?>><?php echo esc_html($category_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ecf-row-controls ecf-row-controls--bottom">
                            <button type="button" class="ecf-step-btn" data-ecf-starter-custom-add data-tip="<?php echo esc_attr__('Add', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Add', 'ecf-framework'); ?>">+</button>
                            <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-starter-custom-remove data-tip="<?php echo esc_attr__('Remove last', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Remove last', 'ecf-framework'); ?>">−</button>
                        </div>
                    </div>
                    </div>
                    </div>
                    <div class="ecf-library-section" data-ecf-library-section="utility" hidden>
                        <div class="ecf-class-workspace ecf-class-workspace--utility">
                        <div class="ecf-class-workspace__header">
                            <h3><?php echo esc_html__('Utility classes', 'ecf-framework'); ?></h3>
                            <p><?php echo esc_html__('Small optional helpers for text styles, alignment, and a few safe layout utilities. They are intentionally limited so the class system stays manageable.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-utility-explainer">
                            <strong><?php echo esc_html__('What are utility classes?', 'ecf-framework'); ?></strong>
                            <p><?php echo esc_html__('Utility classes are small reusable helper classes for common patterns like text sizes, heading styles, alignment, compact layout helpers, and shadow presets. Use them when you want fast repeatable styling without inventing a new semantic class for every small pattern.', 'ecf-framework'); ?></p>
                            <p><?php echo esc_html__('Good rule: use starter classes for page structure and semantic naming, then add utility classes only where a small reusable helper really saves time. Every enabled utility class also counts toward Elementor’s class limit.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-var-tabs ecf-starter-class-tabs" data-ecf-utility-tabs>
                            <?php foreach ($utility_class_categories as $category_key => $category_label): ?>
                                <?php
                                $category_total = 0;
                                $category_active = 0;
                                if ($category_key === 'all') {
                                    foreach ($utility_class_library as $utility_items) {
                                        $category_total += count((array) $utility_items);
                                    }
                                    $category_active = count($active_utility_classes);
                                } else {
                                    $category_total = count((array) ($utility_class_library[$category_key] ?? []));
                                    foreach ((array) ($utility_class_library[$category_key] ?? []) as $utility_item) {
                                        if (in_array((string) ($utility_item['name'] ?? ''), $active_utility_classes, true)) {
                                            $category_active++;
                                        }
                                    }
                                }
                                ?>
                                <button type="button" class="ecf-var-tab<?php echo $category_key === 'all' ? ' is-active' : ''; ?>" data-ecf-utility-tab="<?php echo esc_attr($category_key); ?>" data-ecf-help="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>" data-tip="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>">
                                    <span class="dashicons <?php echo esc_attr($utility_tab_icons[$category_key] ?? 'dashicons-category'); ?>" aria-hidden="true"></span>
                                    <?php echo esc_html($category_label); ?>
                                    <span class="ecf-var-tab__count" data-ecf-utility-tab-count="<?php echo esc_attr($category_key); ?>"><?php echo esc_html($category_active . '/' . $category_total); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="ecf-tab-help" data-ecf-category-help="utility"><?php echo esc_html($utility_class_help_texts['all']); ?></p>
                        <div class="ecf-class-library-actions">
                            <button type="button" class="ecf-btn ecf-btn--secondary ecf-class-select-all" data-ecf-class-select-all>
                                <span class="ecf-select-all__icon" data-ecf-class-select-all-icon aria-hidden="true"></span>
                                <span data-ecf-class-select-all-label><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                            </button>
                            <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--class-sync" data-ecf-class-sync-button data-ecf-class-sync-url="<?php echo esc_url(admin_url('admin-post.php?action=ecf_class_library_sync')); ?>">
                                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                <span><?php echo esc_html__('Sync with Elementor', 'ecf-framework'); ?></span>
                            </button>
                        </div>
                        <p class="ecf-class-library-actions__hint"><?php echo esc_html__('Sync only the utility classes that are currently enabled here.', 'ecf-framework'); ?></p>
                        <div class="ecf-global-search ecf-class-search-card">
                            <label class="ecf-global-search__field">
                                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                <input type="search" data-ecf-class-search placeholder="<?php echo esc_attr__('Search classes…', 'ecf-framework'); ?>" autocomplete="off">
                            </label>
                        </div>
                        <div class="ecf-starter-class-list">
                            <?php foreach ($utility_class_library as $category_key => $classes): ?>
                                <?php foreach ($classes as $class): ?>
                                    <?php $class_name = $class['name']; ?>
                                    <label class="ecf-starter-class-item ecf-utility-class-item"
                                           data-ecf-utility-item
                                           data-category="<?php echo esc_attr($category_key); ?>"
                                           data-class-name="<?php echo esc_attr($class_name); ?>">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr($this->option_name); ?>[utility_classes][enabled][<?php echo esc_attr($class_name); ?>]"
                                               value="1"
                                               class="ecf-utility-class-toggle"
                                               <?php checked(!empty($settings['utility_classes']['enabled'][$class_name])); ?>>
                                        <span class="ecf-starter-class-item__badge ecf-starter-class-item__badge--utility"><?php echo esc_html__('Utility', 'ecf-framework'); ?></span>
                                        <span class="ecf-starter-class-item__main">
                                            <span class="ecf-starter-class-item__name-row">
                                                <span class="ecf-starter-class-item__name"><?php echo esc_html($class_name); ?></span>
                                                <?php $size_label = $this->utility_class_size_label($class_name, $settings); ?>
                                                <?php if ($size_label !== ''): ?>
                                                    <span class="ecf-starter-class-item__size"><?php echo esc_html($size_label); ?></span>
                                                <?php endif; ?>
                                                <button type="button" class="ecf-starter-class-item__help ecf-new-dot" data-tip="<?php echo esc_attr($this->utility_class_tooltip($class_name, $category_key)); ?>" aria-label="<?php echo esc_attr($this->utility_class_tooltip($class_name, $category_key)); ?>">?</button>
                                            </span>
                                            <span class="ecf-utility-class-preview"
                                                  data-ecf-utility-preview-kind="<?php echo esc_attr($this->utility_class_preview_kind($class_name, $category_key)); ?>"
                                                  data-ecf-utility-preview-class="<?php echo esc_attr($class_name); ?>">
                                                <span class="ecf-utility-class-preview__sample">
                                                    <?php echo esc_html($this->utility_class_preview_text($class_name)); ?>
                                                </span>
                                                <?php if ($category_key === 'layout'): ?>
                                                    <span class="ecf-utility-class-preview__demo" aria-hidden="true">
                                                        <span></span>
                                                        <span></span>
                                                        <span></span>
                                                        <span></span>
                                                        <span class="ecf-utility-class-preview__demo-label"><?php echo esc_html__('Text flow', 'ecf-framework'); ?></span>
                                                    </span>
                                                <?php elseif ($category_key === 'shadows'): ?>
                                                    <?php
                                                    $shadow_slug = $this->utility_shadow_slug($class_name);
                                                    $shadow_value = $this->utility_shadow_value($class_name, $settings);
                                                    $shadow_name = $this->utility_shadow_display_name($class_name);
                                                    ?>
                                                    <span class="ecf-utility-class-preview__shadow ecf-shadow-preview-bg" aria-hidden="true">
                                                        <span class="ecf-shadow-focus__surface" style="box-shadow:<?php echo esc_attr($shadow_value); ?>;">
                                                            <span class="ecf-shadow-preview-label"><?php echo esc_html('--ecf-shadow-' . $shadow_slug); ?></span>
                                                            <strong><?php echo esc_html($shadow_name); ?></strong>
                                                            <small><?php echo esc_html($shadow_value); ?></small>
                                                        </span>
                                                    </span>
                                                <?php elseif ($category_key === 'accessibility'): ?>
                                                    <span class="ecf-utility-class-preview__note"><?php echo esc_html__('For screen readers', 'ecf-framework'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                        <span class="ecf-starter-class-item__meta"><?php echo esc_html($utility_class_categories[$category_key] ?? ucfirst($category_key)); ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                    </div>
            </div>
        </div>
        <?php
    }

    private function render_components_panel($args) {
        extract($args, EXTR_SKIP);
        $boxed_width_parts = $this->parse_css_size_parts($settings['elementor_boxed_width'] ?? '1140px');
        $content_width_parts = $this->parse_css_size_parts($settings['content_max_width'] ?? '72ch');
        $boxed_format_options = [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Example: 1140 becomes 1140px.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value. Example: 90 becomes 90%.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Example: 72 becomes 72rem.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Example: 72 becomes 72em.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Example: 90 becomes 90vw.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Example: 80 becomes 80vh.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(100% - 2rem, 1140px), calc(...) or clamp(...).', 'ecf-framework')],
        ];
        $content_format_options = [
            'px'     => ['label' => 'px',  'tip' => __('Simple pixel value. Good for strict content widths like 720px.', 'ecf-framework')],
            'ch'     => ['label' => 'ch',  'tip' => __('Character-based width. Great for readable text columns like 65ch or 72ch.', 'ecf-framework')],
            '%'      => ['label' => '%',   'tip' => __('Percentage value if the content width should stay fluid.', 'ecf-framework')],
            'rem'    => ['label' => 'rem', 'tip' => __('Root-based unit. Useful if content width should scale with your root font size.', 'ecf-framework')],
            'em'     => ['label' => 'em',  'tip' => __('Element-based unit. Rarely needed, but possible for content wrappers.', 'ecf-framework')],
            'vw'     => ['label' => 'vw',  'tip' => __('Viewport width unit. Useful for fluid readable widths.', 'ecf-framework')],
            'vh'     => ['label' => 'vh',  'tip' => __('Viewport height unit. Usually uncommon here, but available if needed.', 'ecf-framework')],
            'custom' => ['label' => 'f(x)', 'tip' => __('Full CSS expression. Use values like min(72ch, 100% - 2rem), calc(...) or clamp(...).', 'ecf-framework')],
        ];
        $boxed_selected_format = isset($boxed_format_options[$boxed_width_parts['format']]) ? $boxed_width_parts['format'] : 'px';
        $content_selected_format = isset($content_format_options[$content_width_parts['format']]) ? $content_width_parts['format'] : 'ch';
        $elementor_limit_snapshot = [
            'classes_total' => $this->get_native_global_class_total_count(),
            'classes_limit' => $this->get_native_global_class_limit(),
            'variables_total' => (int) ($this->get_native_variable_counts()['total'] ?? 0),
            'variables_limit' => $this->get_native_global_variable_limit(),
        ];
        $elementor_debug_snapshot = $this->get_elementor_debug_snapshot();
        $debug_history = $this->debug_history_entries();
        $generated_css = $this->build_generated_css($settings, true);
        $generated_css_download = 'data:text/css;charset=utf-8,' . rawurlencode($generated_css);
        $design_health_snapshot = $this->website_design_health_checks($settings);
        $design_health_checks = $design_health_snapshot['checks'] ?? [];
        $design_health_counts = $design_health_snapshot['counts'] ?? ['good' => 0, 'notice' => 0, 'warn' => 0];
        $smart_recommendations = $this->website_smart_recommendations($settings, $design_health_snapshot);
        $style_presets = [
            [
                'slug' => 'glass-product',
                'tone' => __('Modern', 'ecf-framework'),
                'title' => __('Glass Product', 'ecf-framework'),
                'description' => __('A crisp product look with cool indigo accents, airy neutrals and soft rounded surfaces.', 'ecf-framework'),
                'heading_sample' => __('Product pages that feel clear', 'ecf-framework'),
                'body_sample' => __('Great for SaaS, product marketing and clean interface-driven brands.', 'ecf-framework'),
                'heading_font_stack' => 'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif',
                'body_font_stack' => 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'preview' => [
                    'background' => '#f8fafc',
                    'surface' => '#ffffff',
                    'primary' => '#4f46e5',
                    'accent' => '#14b8a6',
                    'text' => '#0f172a',
                ],
                'preset' => [
                    'general' => [
                        'root_font_size' => '62.5',
                        'base_body_text_size' => '16px',
                        'base_body_font_weight' => '400',
                        'base_font_family' => 'var(--ecf-font-primary)',
                        'heading_font_family' => 'var(--ecf-font-secondary)',
                        'content_max_width' => ['value' => '72', 'format' => 'ch'],
                        'elementor_boxed_width' => ['value' => '1240', 'format' => 'px'],
                        'base_text_color' => '#0f172a',
                        'base_background_color' => '#f8fafc',
                        'link_color' => '#4f46e5',
                        'focus_color' => '#0ea5e9',
                    ],
                    'colors' => [
                        'primary' => '#4f46e5',
                        'secondary' => '#64748b',
                        'accent' => '#14b8a6',
                        'surface' => '#ffffff',
                        'text' => '#0f172a',
                    ],
                    'radius' => [
                        'xs' => ['min' => '6px', 'max' => '6px'],
                        's' => ['min' => '10px', 'max' => '12px'],
                        'm' => ['min' => '14px', 'max' => '16px'],
                        'l' => ['min' => '20px', 'max' => '24px'],
                        'xl' => ['min' => '30px', 'max' => '36px'],
                        'full' => ['min' => '999px', 'max' => '999px'],
                    ],
                    'shadows' => [
                        'xs' => '0 1px 2px rgba(15,23,42,0.06)',
                        's' => '0 10px 24px rgba(15,23,42,0.08)',
                        'm' => '0 20px 44px rgba(15,23,42,0.10)',
                        'l' => '0 30px 70px rgba(15,23,42,0.12)',
                        'xl' => '0 44px 96px rgba(15,23,42,0.14)',
                        'inner' => 'inset 0 1px 0 rgba(255,255,255,0.55), inset 0 -1px 0 rgba(15,23,42,0.05)',
                    ],
                    'spacing' => [
                        'min_base' => '16',
                        'max_base' => '28',
                        'min_ratio' => '1.25',
                        'max_ratio' => '1.414',
                        'base_index' => 'm',
                        'fluid' => true,
                        'min_vw' => '375',
                        'max_vw' => '1280',
                    ],
                    'fonts' => [
                        'primary' => 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        'secondary' => 'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif',
                    ],
                ],
            ],
            [
                'slug' => 'warm-editorial',
                'tone' => __('Editorial', 'ecf-framework'),
                'title' => __('Warm Editorial', 'ecf-framework'),
                'description' => __('Creamy surfaces, elegant serif headlines and softer shadows for storytelling and premium content.', 'ecf-framework'),
                'heading_sample' => __('Stories with more atmosphere', 'ecf-framework'),
                'body_sample' => __('A gentler direction for brands, magazines and long-form reading experiences.', 'ecf-framework'),
                'heading_font_stack' => 'Iowan Old Style, "Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif',
                'body_font_stack' => 'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif',
                'preview' => [
                    'background' => '#f5efe7',
                    'surface' => '#fffaf3',
                    'primary' => '#7c3aed',
                    'accent' => '#c2410c',
                    'text' => '#1f2937',
                ],
                'preset' => [
                    'general' => [
                        'root_font_size' => '62.5',
                        'base_body_text_size' => '17px',
                        'base_body_font_weight' => '400',
                        'base_font_family' => 'var(--ecf-font-primary)',
                        'heading_font_family' => 'var(--ecf-font-secondary)',
                        'content_max_width' => ['value' => '68', 'format' => 'ch'],
                        'elementor_boxed_width' => ['value' => '1180', 'format' => 'px'],
                        'base_text_color' => '#1f2937',
                        'base_background_color' => '#f5efe7',
                        'link_color' => '#7c3aed',
                        'focus_color' => '#c2410c',
                    ],
                    'colors' => [
                        'primary' => '#7c3aed',
                        'secondary' => '#6b7280',
                        'accent' => '#c2410c',
                        'surface' => '#fffaf3',
                        'text' => '#1f2937',
                    ],
                    'radius' => [
                        'xs' => ['min' => '4px', 'max' => '4px'],
                        's' => ['min' => '8px', 'max' => '10px'],
                        'm' => ['min' => '12px', 'max' => '14px'],
                        'l' => ['min' => '18px', 'max' => '22px'],
                        'xl' => ['min' => '28px', 'max' => '34px'],
                        'full' => ['min' => '999px', 'max' => '999px'],
                    ],
                    'shadows' => [
                        'xs' => '0 1px 2px rgba(31,41,55,0.05)',
                        's' => '0 6px 16px rgba(31,41,55,0.08)',
                        'm' => '0 12px 28px rgba(31,41,55,0.10)',
                        'l' => '0 20px 46px rgba(31,41,55,0.12)',
                        'xl' => '0 32px 72px rgba(31,41,55,0.14)',
                        'inner' => 'inset 0 2px 8px rgba(31,41,55,0.07)',
                    ],
                    'spacing' => [
                        'min_base' => '17',
                        'max_base' => '30',
                        'min_ratio' => '1.2',
                        'max_ratio' => '1.333',
                        'base_index' => 'm',
                        'fluid' => true,
                        'min_vw' => '375',
                        'max_vw' => '1280',
                    ],
                    'fonts' => [
                        'primary' => 'Avenir Next, Avenir, "Helvetica Neue", Arial, sans-serif',
                        'secondary' => 'Iowan Old Style, "Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif',
                    ],
                ],
            ],
            [
                'slug' => 'quiet-luxury',
                'tone' => __('Premium', 'ecf-framework'),
                'title' => __('Quiet Luxury', 'ecf-framework'),
                'description' => __('Dark graphite text, rich plum accents and balanced rounding for a polished premium foundation.', 'ecf-framework'),
                'heading_sample' => __('Refined without feeling loud', 'ecf-framework'),
                'body_sample' => __('A calm premium base when you want elegance, contrast and a little more depth.', 'ecf-framework'),
                'heading_font_stack' => 'Georgia, "Times New Roman", serif',
                'body_font_stack' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'preview' => [
                    'background' => '#faf7fb',
                    'surface' => '#ffffff',
                    'primary' => '#6d28d9',
                    'accent' => '#0f766e',
                    'text' => '#111827',
                ],
                'preset' => [
                    'general' => [
                        'root_font_size' => '62.5',
                        'base_body_text_size' => '16px',
                        'base_body_font_weight' => '500',
                        'base_font_family' => 'var(--ecf-font-primary)',
                        'heading_font_family' => 'var(--ecf-font-secondary)',
                        'content_max_width' => ['value' => '70', 'format' => 'ch'],
                        'elementor_boxed_width' => ['value' => '1200', 'format' => 'px'],
                        'base_text_color' => '#111827',
                        'base_background_color' => '#faf7fb',
                        'link_color' => '#6d28d9',
                        'focus_color' => '#0f766e',
                    ],
                    'colors' => [
                        'primary' => '#6d28d9',
                        'secondary' => '#475569',
                        'accent' => '#0f766e',
                        'surface' => '#ffffff',
                        'text' => '#111827',
                    ],
                    'radius' => [
                        'xs' => ['min' => '5px', 'max' => '5px'],
                        's' => ['min' => '9px', 'max' => '11px'],
                        'm' => ['min' => '13px', 'max' => '16px'],
                        'l' => ['min' => '20px', 'max' => '24px'],
                        'xl' => ['min' => '32px', 'max' => '38px'],
                        'full' => ['min' => '999px', 'max' => '999px'],
                    ],
                    'shadows' => [
                        'xs' => '0 1px 2px rgba(17,24,39,0.05)',
                        's' => '0 8px 18px rgba(17,24,39,0.07)',
                        'm' => '0 18px 38px rgba(17,24,39,0.10)',
                        'l' => '0 28px 62px rgba(17,24,39,0.12)',
                        'xl' => '0 40px 86px rgba(17,24,39,0.14)',
                        'inner' => 'inset 0 1px 0 rgba(255,255,255,0.65), inset 0 -1px 0 rgba(17,24,39,0.04)',
                    ],
                    'spacing' => [
                        'min_base' => '16',
                        'max_base' => '26',
                        'min_ratio' => '1.2',
                        'max_ratio' => '1.333',
                        'base_index' => 'm',
                        'fluid' => true,
                        'min_vw' => '375',
                        'max_vw' => '1280',
                    ],
                    'fonts' => [
                        'primary' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                        'secondary' => 'Georgia, "Times New Roman", serif',
                    ],
                ],
            ],
        ];
        $section_recipes = [
            [
                'tone' => __('Hero', 'ecf-framework'),
                'title' => __('Hero Intro', 'ecf-framework'),
                'description' => __('A clean first screen with headline, supporting copy, one main call to action and optional media.', 'ecf-framework'),
                'headline' => __('Launch with a clear promise', 'ecf-framework'),
                'body' => __('Use this when the first section should explain the offer quickly and feel intentional, not crowded.', 'ecf-framework'),
                'classes' => 'ecf-section ecf-hero ecf-hero__content ecf-stack-l',
                'tokens' => '--ecf-content-max-width --ecf-space-xl --ecf-radius-l',
            ],
            [
                'tone' => __('Grid', 'ecf-framework'),
                'title' => __('Feature Grid', 'ecf-framework'),
                'description' => __('A balanced block for benefits, services or product highlights with clean cards and steady rhythm.', 'ecf-framework'),
                'headline' => __('Show the core strengths fast', 'ecf-framework'),
                'body' => __('Great for three to six cards when you want a scannable section with one shared visual system.', 'ecf-framework'),
                'classes' => 'ecf-section ecf-section__inner ecf-layout ecf-card ecf-card__body ecf-stack-m',
                'tokens' => '--ecf-space-l --ecf-shadow-s --ecf-radius-m',
            ],
            [
                'tone' => __('CTA', 'ecf-framework'),
                'title' => __('CTA Band', 'ecf-framework'),
                'description' => __('A compact call-to-action block that closes a section with one message, one button and strong contrast.', 'ecf-framework'),
                'headline' => __('Guide the next click clearly', 'ecf-framework'),
                'body' => __('Use it after content sections when you want one obvious next step instead of adding another long paragraph.', 'ecf-framework'),
                'classes' => 'ecf-section ecf-card ecf-card__body ecf-button ecf-button--primary ecf-stack-m',
                'tokens' => '--ecf-primary --ecf-space-m --ecf-shadow-xs',
            ],
        ];
        ?>
        <div class="ecf-panel" data-panel="components">
            <div class="ecf-grid">
                <div class="ecf-card">
                    <div class="ecf-general-settings__header">
      <h2><?php echo esc_html__('Base Settings', 'ecf-framework'); ?></h2>
                        <div class="ecf-format-picker__tooltip ecf-format-picker__tooltip--header" data-ecf-format-tooltip hidden><?php echo esc_html($boxed_format_options[$boxed_selected_format]['tip']); ?></div>
                    </div>
                    <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Manage website basics, editor behavior and system settings from one shared control area.', 'ecf-framework'); ?></p>
                    <div class="ecf-var-tabs ecf-general-tabs" data-ecf-general-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-general-tab="website" data-tip="<?php echo esc_attr__('Website-wide basics like root size, widths, body font and base colors.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-home" aria-hidden="true"></span><?php echo esc_html__('Website', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="interface" data-tip="<?php echo esc_attr__('Elementor editor helpers, plugin language and the ECF backend appearance.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span><?php echo esc_html__('Interface', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="system" data-tip="<?php echo esc_attr__('System status, update checks, limits and integrated help.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html__('System', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="favorites" data-ecf-new-key="general-favorites" data-tip="<?php echo esc_attr__('Pinned quick settings from Website and Plugin basics.', 'ecf-framework'); ?>"><span class="dashicons dashicons-heart" aria-hidden="true"></span><?php echo esc_html__('Favorites', 'ecf-framework'); ?></button>
                    </div>
                    <div class="ecf-general-section is-active ecf-general-section--website" data-ecf-general-section="website" data-ecf-layout-group="components-website">
                        <div class="ecf-start-banner" data-ecf-start-banner>
                            <div class="ecf-start-banner__copy">
                                <strong><?php echo esc_html__('Getting started', 'ecf-framework'); ?></strong>
                                <p><?php echo esc_html__('Start with website basics first: choose a style preset or font pairing, set colors and widths, then sync only the pieces you really want in Elementor.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-start-banner__actions">
                                <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--sm" data-ecf-start-focus="style-presets"><?php echo esc_html__('Show presets', 'ecf-framework'); ?></button>
                                <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm" data-ecf-start-dismiss><?php echo esc_html__('Got it', 'ecf-framework'); ?></button>
                            </div>
                        </div>
                        <div class="ecf-general-overview-card ecf-general-overview-card--compact" data-ecf-layout-item="website-overview">
                            <div>
                                <strong><?php echo esc_html__('Website basics', 'ecf-framework'); ?></strong>
                                <p><?php echo esc_html__('Site-wide font, layout and base color settings for the whole website.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-general-overview-card__tags" aria-hidden="true">
                                <span><?php echo esc_html__('Type', 'ecf-framework'); ?></span>
                                <span><?php echo esc_html__('Widths', 'ecf-framework'); ?></span>
                                <span><?php echo esc_html__('Colors', 'ecf-framework'); ?></span>
                            </div>
                        </div>
                        <div class="ecf-card ecf-style-presets-card" data-ecf-layout-item="website-style-presets">
                            <div class="ecf-style-presets-card__header">
                                <div>
                                    <h2><?php echo esc_html__('Style Presets', 'ecf-framework'); ?></h2>
                                    <p><?php echo esc_html__('Apply one complete visual direction when you want a faster start for type, colors, radii, shadows and website basics.', 'ecf-framework'); ?></p>
                                </div>
                            </div>
                            <div class="ecf-style-presets-grid">
                                <?php foreach ($style_presets as $preset): ?>
                                    <article class="ecf-style-preset">
                                        <div class="ecf-style-preset__meta">
                                            <span class="ecf-preview-pill"><?php echo esc_html($preset['tone']); ?></span>
                                            <h3><?php echo esc_html($preset['title']); ?></h3>
                                            <p><?php echo esc_html($preset['description']); ?></p>
                                        </div>
                                        <div class="ecf-style-preset__preview" style="<?php echo esc_attr('--ecf-style-preset-bg:' . $preset['preview']['background'] . ';--ecf-style-preset-surface:' . $preset['preview']['surface'] . ';--ecf-style-preset-primary:' . $preset['preview']['primary'] . ';--ecf-style-preset-accent:' . $preset['preview']['accent'] . ';--ecf-style-preset-text:' . $preset['preview']['text'] . ';'); ?>">
                                            <div class="ecf-style-preset__swatches" aria-hidden="true">
                                                <span></span><span></span><span></span><span></span>
                                            </div>
                                            <div class="ecf-style-preset__surface">
                                                <strong style="font-family: <?php echo esc_attr($preset['heading_font_stack']); ?>;"><?php echo esc_html($preset['heading_sample']); ?></strong>
                                                <p style="font-family: <?php echo esc_attr($preset['body_font_stack']); ?>;"><?php echo esc_html($preset['body_sample']); ?></p>
                                                <div class="ecf-style-preset__actions" aria-hidden="true">
                                                    <span></span><span></span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="ecf-btn ecf-btn--secondary ecf-style-preset__apply"
                                                data-ecf-style-preset-apply
                                                data-ecf-style-preset="<?php echo esc_attr(wp_json_encode($preset['preset'])); ?>">
                                            <?php echo esc_html__('Use this style preset', 'ecf-framework'); ?>
                                        </button>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ecf-card ecf-design-health-card" data-ecf-layout-item="website-design-health">
                            <div class="ecf-design-health-card__header">
                                <div>
                                    <h2><?php echo esc_html__('Design Health Check', 'ecf-framework'); ?></h2>
                                    <p><?php echo esc_html__('A quick review of readability, hierarchy, rhythm and cleanup signals before you refine the details.', 'ecf-framework'); ?></p>
                                </div>
                                <div class="ecf-design-health-card__summary">
                                    <?php if (!empty($design_health_counts['warn'])): ?>
                                        <span class="ecf-design-health-badge ecf-design-health-badge--warn"><?php echo esc_html(sprintf(__('%d to fix', 'ecf-framework'), (int) $design_health_counts['warn'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($design_health_counts['notice'])): ?>
                                        <span class="ecf-design-health-badge ecf-design-health-badge--notice"><?php echo esc_html(sprintf(__('%d to review', 'ecf-framework'), (int) $design_health_counts['notice'])); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($design_health_counts['good'])): ?>
                                        <span class="ecf-design-health-badge ecf-design-health-badge--good"><?php echo esc_html(sprintf(__('%d look good', 'ecf-framework'), (int) $design_health_counts['good'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ecf-design-health-list">
                                <?php foreach ($design_health_checks as $check): ?>
                                    <article class="ecf-design-health-item ecf-design-health-item--<?php echo esc_attr($check['status'] ?? 'notice'); ?>">
                                        <div class="ecf-design-health-item__status" aria-hidden="true"></div>
                                        <div class="ecf-design-health-item__copy">
                                            <div class="ecf-design-health-item__topline">
                                                <strong><?php echo esc_html($check['title'] ?? ''); ?></strong>
                                                <?php if (!empty($check['value'])): ?>
                                                    <span><?php echo esc_html($check['value']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p><?php echo esc_html($check['message'] ?? ''); ?></p>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ecf-card ecf-smart-recommendations-card" data-ecf-layout-item="website-smart-recommendations">
                            <div class="ecf-smart-recommendations-card__header">
                                <div>
                                    <h2><?php echo esc_html__('Smart Recommendations', 'ecf-framework'); ?></h2>
                                    <p><?php echo esc_html__('Use a few small one-click improvements when you want Layrix to smooth out the most common design friction for you.', 'ecf-framework'); ?></p>
                                </div>
                            </div>
                            <div class="ecf-smart-recommendations-grid">
                                <?php foreach ($smart_recommendations as $recommendation): ?>
                                    <article class="ecf-smart-recommendation">
                                        <div class="ecf-smart-recommendation__meta">
                                            <span class="ecf-preview-pill"><?php echo esc_html($recommendation['tone'] ?? ''); ?></span>
                                            <h3><?php echo esc_html($recommendation['title'] ?? ''); ?></h3>
                                            <p><?php echo esc_html($recommendation['description'] ?? ''); ?></p>
                                        </div>
                                        <div class="ecf-smart-recommendation__impact">
                                            <strong><?php echo esc_html__('Why this helps', 'ecf-framework'); ?></strong>
                                            <p><?php echo esc_html($recommendation['impact'] ?? ''); ?></p>
                                        </div>
                                        <?php if (!empty($recommendation['payload']) && !empty($recommendation['apply_label'])): ?>
                                            <button type="button"
                                                    class="ecf-btn ecf-btn--secondary ecf-smart-recommendation__apply"
                                                    data-ecf-smart-recommendation-apply
                                                    data-ecf-smart-recommendation="<?php echo esc_attr(wp_json_encode($recommendation['payload'])); ?>">
                                                <?php echo esc_html($recommendation['apply_label']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ecf-card ecf-section-recipes-card" data-ecf-layout-item="website-section-recipes">
                            <div class="ecf-section-recipes-card__header">
                                <div>
                                    <h2><?php echo esc_html__('Section Recipes', 'ecf-framework'); ?></h2>
                                    <p><?php echo esc_html__('Start with a few proven section patterns when you want a faster structure instead of building every block from scratch.', 'ecf-framework'); ?></p>
                                </div>
                            </div>
                            <div class="ecf-section-recipes-grid">
                                <?php foreach ($section_recipes as $recipe): ?>
                                    <article class="ecf-section-recipe">
                                        <div class="ecf-section-recipe__meta">
                                            <span class="ecf-preview-pill"><?php echo esc_html($recipe['tone']); ?></span>
                                            <h3><?php echo esc_html($recipe['title']); ?></h3>
                                            <p><?php echo esc_html($recipe['description']); ?></p>
                                        </div>
                                        <div class="ecf-section-recipe__preview" aria-hidden="true">
                                            <strong><?php echo esc_html($recipe['headline']); ?></strong>
                                            <p><?php echo esc_html($recipe['body']); ?></p>
                                            <div class="ecf-section-recipe__preview-actions">
                                                <span></span><span></span>
                                            </div>
                                        </div>
                                        <div class="ecf-section-recipe__fields">
                                            <div class="ecf-section-recipe__field">
                                                <div class="ecf-section-recipe__field-topline">
                                                    <strong><?php echo esc_html__('Suggested classes', 'ecf-framework'); ?></strong>
                                                    <button type="button" class="ecf-copy-pill" data-copy="<?php echo esc_attr($recipe['classes']); ?>"><?php echo esc_html__('Copy', 'ecf-framework'); ?></button>
                                                </div>
                                                <code><?php echo esc_html($recipe['classes']); ?></code>
                                            </div>
                                            <div class="ecf-section-recipe__field">
                                                <div class="ecf-section-recipe__field-topline">
                                                    <strong><?php echo esc_html__('Helpful tokens', 'ecf-framework'); ?></strong>
                                                    <button type="button" class="ecf-copy-pill" data-copy="<?php echo esc_attr($recipe['tokens']); ?>"><?php echo esc_html__('Copy', 'ecf-framework'); ?></button>
                                                </div>
                                                <code><?php echo esc_html($recipe['tokens']); ?></code>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ecf-var-tabs ecf-website-subtabs" data-ecf-website-tabs>
                            <button type="button" class="ecf-var-tab is-active" data-ecf-website-tab="type" aria-pressed="true"><span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span><?php echo esc_html__('Font', 'ecf-framework'); ?></button>
                            <button type="button" class="ecf-var-tab" data-ecf-website-tab="layout" aria-pressed="false"><span class="dashicons dashicons-align-wide" aria-hidden="true"></span><?php echo esc_html__('Layout', 'ecf-framework'); ?></button>
                            <button type="button" class="ecf-var-tab" data-ecf-website-tab="colors" aria-pressed="false"><span class="dashicons dashicons-art" aria-hidden="true"></span><?php echo esc_html__('Colors', 'ecf-framework'); ?></button>
                            <button type="button" class="ecf-var-tab" data-ecf-website-tab="advanced" aria-pressed="false"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span><?php echo esc_html__('Advanced', 'ecf-framework'); ?></button>
                        </div>
                        <div class="ecf-website-subsection is-active" data-ecf-website-section="type">
                        <div class="ecf-settings-group" data-ecf-layout-item="website-type-size">
                            <div class="ecf-settings-group__header">
                                <h3><?php echo esc_html__('Type & Size', 'ecf-framework'); ?></h3>
                                <p><?php echo esc_html__('Set the root rem size plus the default body type choices for your site.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-layout-columns-toolbar ecf-layout-columns-toolbar--inner ecf-layout-columns-toolbar--toggle" data-ecf-layout-columns-toolbar data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Columns', 'ecf-framework'); ?>">
                                <span class="ecf-layout-columns-toolbar__label"><?php echo esc_html__('Layout', 'ecf-framework'); ?></span>
                                <div class="ecf-layout-columns-toolbar__options" role="group" aria-label="<?php echo esc_attr__('Columns', 'ecf-framework'); ?>">
                                    <button type="button" class="ecf-layout-columns-btn is-active" data-ecf-layout-columns-btn="1" data-ecf-layout-columns="2" data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Switch to 2 columns', 'ecf-framework'); ?>" data-tip="<?php echo esc_attr__('2 columns side by side', 'ecf-framework'); ?>">
                                        <span class="ecf-layout-columns-btn__icon ecf-layout-columns-btn__icon--2" aria-hidden="true">
                                            <span></span><span></span>
                                        </span>
                                        <span class="ecf-layout-columns-btn__label"><?php echo esc_html__('2 columns', 'ecf-framework'); ?></span>
                                    </button>
                                    <button type="button" class="ecf-layout-columns-btn" data-ecf-layout-columns-btn="1" data-ecf-layout-columns="3" data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Switch to 3 columns', 'ecf-framework'); ?>" data-tip="<?php echo esc_attr__('3 columns side by side', 'ecf-framework'); ?>">
                                        <span class="ecf-layout-columns-btn__icon ecf-layout-columns-btn__icon--3" aria-hidden="true">
                                            <span></span><span></span><span></span>
                                        </span>
                                        <span class="ecf-layout-columns-btn__label"><?php echo esc_html__('3 columns', 'ecf-framework'); ?></span>
                                    </button>
                                </div>
                                <p class="ecf-layout-columns-toolbar__help"><?php echo esc_html__('Desktop stays in 2 or 3 columns. On smaller screens the cards stack automatically into one column.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-form-grid ecf-form-grid--single ecf-form-grid--website-type-size" data-ecf-layout-group="components-website-type-size" data-ecf-layout-columns-group="components-website-type-size" data-ecf-layout-columns="2" data-ecf-masonry-layout="1" style="--ecf-layout-columns:2;">
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-body">
                                    <?php $this->render_base_body_text_size_field($settings); ?>
                                </div>
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-body-weight">
                                    <?php $this->render_base_body_font_weight_field($settings); ?>
                                </div>
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-browser-reset">
                                    <?php $this->render_typography_browser_margin_reset_field($settings); ?>
                                </div>
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-root">
                                    <?php $this->render_root_font_size_select($settings, true); ?>
                                </div>
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-base-font">
                                    <?php $this->render_base_font_family_field($settings, false); ?>
                                </div>
                                <div class="ecf-type-size-card" data-ecf-layout-item="type-size-heading-font">
                                    <?php $this->render_heading_font_family_field($settings, false); ?>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="ecf-website-subsection" data-ecf-website-section="layout" hidden>
                        <div class="ecf-settings-group" data-ecf-layout-item="website-widths">
                            <div class="ecf-settings-group__header">
                                <h3><?php echo esc_html__('Widths', 'ecf-framework'); ?></h3>
                                <p><?php echo esc_html__('Keep readable content widths and wider layout containers together in one place.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-form-grid ecf-form-grid--two ecf-form-grid--website-widths">
                                <label data-ecf-general-field="content_max_width">
                                    <span class="ecf-general-label-with-favorite">
                                        <?php echo $this->general_setting_label(__('Content Max Width', 'ecf-framework'), 'Creates the CSS token --ecf-content-max-width for readable text/content wrappers. ch works especially well for article-like content widths.', 'align-wide'); ?>
                                        <?php $this->render_general_setting_favorite_toggle($settings, 'content_max_width'); ?>
                                    </span>
                                    <?php $this->render_field_token_pills([
                                        ['type' => __('Variable', 'ecf-framework'), 'value' => '--ecf-content-max-width'],
                                        ['type' => __('Class', 'ecf-framework'), 'value' => '.ecf-content-width'],
                                    ]); ?>
                                    <div class="ecf-inline-size-input">
                                        <input type="text" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_value]" value="<?php echo esc_attr($content_width_parts['value']); ?>" placeholder="72 oder min(72ch, 100% - 2rem)" data-tip="<?php echo esc_attr__('Enter either a simple value like 72 or, with f(x), a full CSS expression such as min(72ch, 100% - 2rem).', 'ecf-framework'); ?>">
                                        <div class="ecf-format-picker" data-ecf-format-picker>
                                            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_format]" value="<?php echo esc_attr($content_selected_format); ?>" data-ecf-format-input>
                                            <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" data-tip="<?php echo esc_attr__('Choose the unit for simple values. ch is usually best for readable text widths. Use f(x) for full CSS expressions.', 'ecf-framework'); ?>">
                                                <span data-ecf-format-current><?php echo esc_html($content_format_options[$content_selected_format]['label']); ?></span>
                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                            </button>
                                            <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                                                <div class="ecf-format-picker__options">
                                                    <?php foreach ($content_format_options as $format_value => $format_config): ?>
                                                        <button type="button"
                                                                class="ecf-format-picker__option<?php echo $format_value === $content_selected_format ? ' is-active' : ''; ?>"
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
                                </label>
                                <label data-ecf-general-field="elementor_boxed_width">
                                    <span class="ecf-general-label-with-favorite">
                                        <?php echo $this->general_setting_label(__('Elementor Boxed Width', 'ecf-framework'), 'Creates the CSS token --ecf-container-boxed and the helper class .ecf-container-boxed. Choose a format like px, %, rem or switch to f(x) for values like min(...), calc(...) or clamp(...).', 'screenoptions'); ?>
                                        <?php $this->render_general_setting_favorite_toggle($settings, 'elementor_boxed_width'); ?>
                                    </span>
                                    <?php $this->render_field_token_pills([
                                        ['type' => __('Variable', 'ecf-framework'), 'value' => '--ecf-container-boxed'],
                                        ['type' => __('Class', 'ecf-framework'), 'value' => '.ecf-container-boxed'],
                                    ]); ?>
                                    <div class="ecf-inline-size-input">
                                        <input type="text" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_value]" value="<?php echo esc_attr($boxed_width_parts['value']); ?>" placeholder="1140 oder clamp(20rem, 80vw, 1140px)" data-tip="<?php echo esc_attr__('Enter either a plain value like 1140 or, with f(x), a full CSS expression such as clamp(20rem, 80vw, 1140px).', 'ecf-framework'); ?>">
                                        <div class="ecf-format-picker" data-ecf-format-picker>
                                            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_format]" value="<?php echo esc_attr($boxed_selected_format); ?>" data-ecf-format-input>
                                            <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" data-tip="<?php echo esc_attr__('Choose the unit for simple values. Use f(x) for complete CSS expressions like min(...), calc(...) or clamp(...).', 'ecf-framework'); ?>">
                                                <span data-ecf-format-current><?php echo esc_html($boxed_format_options[$boxed_selected_format]['label']); ?></span>
                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                            </button>
                                            <div class="ecf-format-picker__menu" data-ecf-format-menu hidden>
                                                <div class="ecf-format-picker__options">
                                                    <?php foreach ($boxed_format_options as $format_value => $format_config): ?>
                                                        <button type="button"
                                                                class="ecf-format-picker__option<?php echo $format_value === $boxed_selected_format ? ' is-active' : ''; ?>"
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
                                </label>
                            </div>
                        </div>
                        </div>
                        <div class="ecf-website-subsection" data-ecf-website-section="colors" hidden>
                        <div class="ecf-settings-group" data-ecf-layout-item="website-base-colors">
                            <div class="ecf-settings-group__header">
                                <h3><?php echo esc_html__('Base Colors', 'ecf-framework'); ?></h3>
                                <p><?php echo esc_html__('These colors define the visual starting point before component-specific styles take over.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-form-grid ecf-form-grid--base-colors">
                                <?php $this->render_general_color_field($settings, 'base_text_color', 'Base Text Color', 'Basis-Textfarbe', 'Default body text color for the whole site.', 'Standard-Textfarbe für den Fließtext der ganzen Website.', 'editor-textcolor'); ?>
                                <?php $this->render_general_color_field($settings, 'base_background_color', 'Base Background Color', 'Basis-Hintergrundfarbe', 'Default page background for the website.', 'Standard-Seitenhintergrund für die Website.', 'art'); ?>
                                <?php $this->render_general_color_field($settings, 'link_color', 'Link Color', 'Link-Farbe', 'Default color for normal links.', 'Standardfarbe für normale Links.', 'admin-links'); ?>
                                <?php $this->render_general_color_field($settings, 'focus_color', 'Focus Color', 'Fokus-Farbe', 'Visible color for keyboard focus outlines and focus rings.', 'Sichtbare Farbe für Tastatur-Fokusrahmen und Focus-Rings.', 'visibility'); ?>
                                <div class="ecf-base-colors-focus">
                                    <div class="ecf-base-colors-focus__header">
                                        <strong><?php echo esc_html__('Focus Outline', 'ecf-framework'); ?></strong>
                                        <p><?php echo esc_html__('Width and offset stay together so the keyboard focus ring is easier to tune as one setting.', 'ecf-framework'); ?></p>
                                    </div>
                                    <div class="ecf-base-colors-focus__fields">
                                        <label data-ecf-general-field="focus_outline_width">
                                            <span class="ecf-general-label-with-favorite">
                                                <?php echo $this->general_setting_label(__('Focus Outline Width', 'ecf-framework'), 'Visible width of the keyboard focus outline.', 'editor-expand'); ?>
                                                <?php $this->render_general_setting_favorite_toggle($settings, 'focus_outline_width'); ?>
                                            </span>
                                            <?php $this->render_field_token_pills([['type' => __('Variable', 'ecf-framework'), 'value' => '--ecf-focus-outline-width']]); ?>
                                            <?php $this->render_general_size_field_inline($settings, 'focus_outline_width', $settings['focus_outline_width'] ?? '2px', $this->focus_outline_format_options(), 'px', '2', __('Visible width of the keyboard focus outline.', 'ecf-framework')); ?>
                                        </label>
                                        <label data-ecf-general-field="focus_outline_offset">
                                            <span class="ecf-general-label-with-favorite">
                                                <?php echo $this->general_setting_label(__('Focus Outline Offset', 'ecf-framework'), 'Distance between the element edge and the keyboard focus outline.', 'move'); ?>
                                                <?php $this->render_general_setting_favorite_toggle($settings, 'focus_outline_offset'); ?>
                                            </span>
                                            <?php $this->render_field_token_pills([['type' => __('Variable', 'ecf-framework'), 'value' => '--ecf-focus-outline-offset']]); ?>
                                            <?php $this->render_general_size_field_inline($settings, 'focus_outline_offset', $settings['focus_outline_offset'] ?? '2px', $this->focus_outline_format_options(), 'px', '2', __('Distance between the element edge and the keyboard focus outline.', 'ecf-framework')); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="ecf-website-subsection" data-ecf-website-section="advanced" hidden>
                        <details class="ecf-settings-group ecf-settings-group--details" data-ecf-layout-item="website-scale-impact">
                            <summary class="ecf-settings-group__summary">
                                <span>
                                    <strong><?php echo esc_html__('Scale Impact', 'ecf-framework'); ?></strong>
                                    <small><?php echo esc_html__('See how the current root size affects typography, spacing and radius tokens.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <?php $this->render_root_font_impact_panel($settings); ?>
                        </details>
                        <details class="ecf-settings-group ecf-settings-group--details" data-ecf-layout-item="website-generated-css">
                            <summary class="ecf-settings-group__summary">
                                <span>
                                    <strong><?php echo esc_html__('ecf_generated_css_title', 'ecf-framework'); ?></strong>
                                    <small><?php echo esc_html__('ecf_generated_css_description', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-muted-copy" style="margin:0 0 12px 0;">
                                <strong><?php echo esc_html__('ecf_generated_css_why_title', 'ecf-framework'); ?></strong>
                                <?php echo esc_html__('ecf_generated_css_why_text', 'ecf-framework'); ?>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 12px 0;">
                                <button type="button" class="button button-secondary" data-ecf-copy-target="ecf-generated-css-field"><?php echo esc_html__('ecf_generated_css_copy', 'ecf-framework'); ?></button>
                                <a class="button button-secondary" href="<?php echo esc_attr($generated_css_download); ?>" download="layrix-generated.css"><?php echo esc_html__('ecf_generated_css_export', 'ecf-framework'); ?></a>
                            </div>
                            <textarea id="ecf-generated-css-field" readonly rows="18" style="width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,monospace;white-space:pre;"><?php echo esc_textarea($generated_css); ?></textarea>
                            <p class="ecf-muted-copy"><?php echo esc_html__('ecf_generated_css_note', 'ecf-framework'); ?></p>
                        </details>
                        </div>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="interface" hidden>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="show_elementor_status_cards">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[show_elementor_status_cards]" value="1" <?php checked(!empty($settings['show_elementor_status_cards'])); ?>>
                                <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Show status cards in Variables & Sync', 'ecf-framework'), 'Shows small overview cards in the Variables and Sync areas so you can see current Elementor usage and limits at a glance.', 'chart-bar'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'show_elementor_status_cards'); ?></span>
                            </label>
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="elementor_variable_type_filter">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter'])); ?>>
                                <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Limit Elementor variables by field type', 'ecf-framework'), 'Only shows matching variables in Elementor fields. Example: color fields get color variables, spacing fields get spacing variables.', 'filter'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'elementor_variable_type_filter'); ?></span>
                            </label>
                            <details class="ecf-filter-scope-box">
                                <summary class="ecf-filter-scope-box__summary">
                                    <div class="ecf-filter-scope-box__title"><?php echo $this->general_setting_label(__('Filter for', 'ecf-framework'), 'Choose which variable groups should be filtered by matching Elementor field types.', 'filter'); ?></div>
                                    <span class="dashicons dashicons-arrow-down-alt2 ecf-filter-scope-box__arrow" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-form-grid ecf-form-grid--two ecf-filter-scope-grid">
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][color]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['color'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Colors', 'ecf-framework'), __('Filters color variables like brand, text, border or background colors to color-compatible Elementor fields.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][text]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['text'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Typography', 'ecf-framework'), __('Filters typography variables like text sizes so they appear only in matching typography-related Elementor controls.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][space]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['space'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Spacing', 'ecf-framework'), __('Filters spacing variables like gaps, padding or margins into spacing-compatible Elementor fields.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][radius]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['radius'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Radius', 'ecf-framework'), __('Filters border-radius style variables into matching radius fields.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][shadow]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['shadow'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Shadows', 'ecf-framework'), __('Filters shadow variables into matching box-shadow or shadow-related Elementor fields.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][string]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['string'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Other text values', 'ecf-framework'), __('Filters remaining string-based values that are neither size nor color, for example free text-like CSS values.', 'ecf-framework'), ''); ?></span>
                                    </label>
                                </div>
                            </details>
                            <div class="ecf-form-grid ecf-form-grid--three ecf-interface-basics-grid">
                                <?php $this->render_interface_language_field($settings); ?>
                                <?php $this->render_admin_content_font_size_field($settings); ?>
                                <?php $this->render_admin_menu_font_size_field($settings); ?>
                            </div>
                            <div class="ecf-form-grid ecf-form-grid--two ecf-interface-sync-grid">
                                <label class="ecf-form-grid__checkbox" data-ecf-general-field="autosave_enabled">
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[autosave_enabled]" value="1" <?php checked(!empty($settings['autosave_enabled'])); ?> data-ecf-autosave-enabled>
                                    <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Save Layrix changes automatically', 'ecf-framework'), 'Automatically saves your Layrix settings after a short pause. If this is off, you need to use the save button manually.', 'saved'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'autosave_enabled'); ?></span>
                                </label>
                                <label class="ecf-form-grid__checkbox" data-ecf-general-field="elementor_auto_sync_enabled">
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_auto_sync_enabled]" value="1" <?php checked(!empty($settings['elementor_auto_sync_enabled'])); ?> data-ecf-elementor-auto-sync-enabled>
                                    <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Also sync changes to Elementor automatically', 'ecf-framework'), 'After Layrix autosave, also sync the generated variables and classes to Elementor automatically. This can update Elementor data and clear Elementor caches.', 'update'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'elementor_auto_sync_enabled'); ?></span>
                                </label>
                                <label class="ecf-form-grid__checkbox" data-ecf-general-field="elementor_auto_sync_variables">
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_auto_sync_variables]" value="1" <?php checked(!empty($settings['elementor_auto_sync_variables'])); ?>>
                                    <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Auto-sync variables', 'ecf-framework'), 'Syncs colors, shades, tints, spacing, radius, typography, shadows and layout variables into Elementor after autosave.', 'art'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'elementor_auto_sync_variables'); ?></span>
                                </label>
                                <label class="ecf-form-grid__checkbox" data-ecf-general-field="elementor_auto_sync_classes">
                                    <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_auto_sync_classes]" value="1" <?php checked(!empty($settings['elementor_auto_sync_classes'])); ?>>
                                    <span class="ecf-general-label-with-favorite"><?php echo $this->general_setting_label(__('Auto-sync classes', 'ecf-framework'), 'Syncs Layrix helper classes into Elementor after autosave. Enable this only if you want class changes to be pushed automatically.', 'editor-code'); ?><?php $this->render_general_setting_favorite_toggle($settings, 'elementor_auto_sync_classes'); ?></span>
                                </label>
                            </div>
                            <?php $this->render_admin_design_field($settings); ?>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html__('These options affect the Elementor editor, plugin language and the ECF backend appearance, not your frontend design.', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="system" hidden data-ecf-layout-group="components-system">
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label class="ecf-form-grid__checkbox" data-ecf-general-field="github_update_checks_enabled">
                                <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[github_update_checks_enabled]" value="1" <?php checked(!empty($settings['github_update_checks_enabled'])); ?>>
                                <span class="ecf-general-label-with-favorite">
                                    <?php echo $this->general_setting_label(__('GitHub update checks', 'ecf-framework'), 'Allows ECF to check GitHub for plugin updates. This sends your server to GitHub only for update metadata and downloads.', 'update'); ?>
                                    <?php $this->render_general_setting_favorite_toggle($settings, 'github_update_checks_enabled'); ?>
                                </span>
                            </label>
                        </div>
                        <div class="ecf-system-limit-card" id="ecf-elementor-limits" data-ecf-elementor-limits-card data-ecf-layout-item="system-updater-privacy">
                            <div class="ecf-system-limit-card__header">
          <strong><?php echo esc_html__('Updater & privacy', 'ecf-framework'); ?></strong>
                            </div>
                            <div class="ecf-system-limit-card__grid">
                                <div class="ecf-system-limit-card__item">
            <span class="ecf-system-limit-card__label"><?php echo esc_html__('Remote service', 'ecf-framework'); ?></span>
                                    <strong>GitHub</strong>
                                </div>
                                <div class="ecf-system-limit-card__item">
            <span class="ecf-system-limit-card__label"><?php echo esc_html__('Current status', 'ecf-framework'); ?></span>
            <strong data-ecf-github-status><?php echo esc_html(!empty($settings['github_update_checks_enabled']) ? __('Enabled', 'ecf-framework') : __('Disabled', 'ecf-framework')); ?></strong>
                                </div>
                            </div>
          <p class="ecf-system-limit-card__hint"><?php echo esc_html__('When enabled, ECF can contact api.github.com and codeload.github.com from your server to check update metadata and download plugin updates. If an ECF_GITHUB_TOKEN is configured, it is sent only to GitHub for authenticated update requests.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-system-limit-card" data-ecf-elementor-limits-card data-ecf-layout-item="system-elementor-limits">
                            <div class="ecf-system-limit-card__header">
          <strong><?php echo esc_html__('Elementor limits', 'ecf-framework'); ?></strong>
                                <button type="button" class="ecf-btn ecf-btn--secondary ecf-btn--compact" data-ecf-refresh-system-info>
                                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
            <span><?php echo esc_html__('Reload', 'ecf-framework'); ?></span>
                                </button>
                            </div>
                            <div class="ecf-system-limit-card__grid">
                                <div class="ecf-system-limit-card__item">
            <span class="ecf-system-limit-card__label"><?php echo esc_html__('Global Classes', 'ecf-framework'); ?></span>
                                    <strong><span data-ecf-classes-total><?php echo esc_html((string) $elementor_limit_snapshot['classes_total']); ?></span> / <span data-ecf-classes-limit><?php echo esc_html((string) $elementor_limit_snapshot['classes_limit']); ?></span></strong>
                                </div>
                                <div class="ecf-system-limit-card__item">
            <span class="ecf-system-limit-card__label"><?php echo esc_html__('Variables', 'ecf-framework'); ?></span>
                                    <strong><span data-ecf-variables-total><?php echo esc_html((string) $elementor_limit_snapshot['variables_total']); ?></span> / <span data-ecf-variables-limit><?php echo esc_html((string) $elementor_limit_snapshot['variables_limit']); ?></span></strong>
                                </div>
                            </div>
          <p class="ecf-system-limit-card__hint"><?php echo esc_html__('Detected from the installed Elementor version on this website. Use Reload to fetch the current values again.', 'ecf-framework'); ?></p>
                        </div>
                        <details class="ecf-system-debug-card" data-ecf-new-key="system-debug" data-ecf-layout-item="system-debug">
                            <summary class="ecf-system-debug-card__summary">
                                <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
            <span><?php echo esc_html__('Debug', 'ecf-framework'); ?></span>
                                <span class="ecf-new-dot" data-ecf-new-badge hidden data-tip="<?php echo esc_attr__('New: Debug shows Elementor Core and Pro detection, active modules and the detected class and variable limits.', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('New: Debug shows Elementor Core and Pro detection, active modules and the detected class and variable limits.', 'ecf-framework'); ?>"></span>
                                <span class="dashicons dashicons-arrow-down-alt2 ecf-system-debug-card__arrow" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-system-debug-card__grid">
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Elementor Core recognized', 'ecf-framework'), __('Checks whether the Elementor core plugin is loaded and available to ECF on this website.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-core-state><?php echo esc_html($elementor_debug_snapshot['core_recognized'] ? __('Yes', 'ecf-framework') : __('No', 'ecf-framework')); ?></strong>
                                    <?php if ($elementor_debug_snapshot['core_version'] !== ''): ?>
                                        <small data-ecf-debug-core-version><?php echo esc_html(sprintf(__('Version %s', 'ecf-framework'), $elementor_debug_snapshot['core_version'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Elementor Pro recognized', 'ecf-framework'), __('Checks whether Elementor Pro is loaded. Some variables, sync and editor features can depend on it.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-pro-state><?php echo esc_html($elementor_debug_snapshot['pro_recognized'] ? __('Yes', 'ecf-framework') : __('No', 'ecf-framework')); ?></strong>
                                    <?php if ($elementor_debug_snapshot['pro_version'] !== ''): ?>
                                        <small data-ecf-debug-pro-version><?php echo esc_html(sprintf(__('Version %s', 'ecf-framework'), $elementor_debug_snapshot['pro_version'])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Variables module active', 'ecf-framework'), __('Checks whether Elementor\'s Variables module is available, which is required for ECF variable sync and picker integration.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-variables-state><?php echo esc_html($elementor_debug_snapshot['variables_active'] ? __('Yes', 'ecf-framework') : __('No', 'ecf-framework')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Global Classes active', 'ecf-framework'), __('Checks whether Elementor\'s Global Classes module is available for ECF class sync.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-classes-state><?php echo esc_html($elementor_debug_snapshot['global_classes_active'] ? __('Yes', 'ecf-framework') : __('No', 'ecf-framework')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Design System Sync active', 'ecf-framework'), __('Checks whether Elementor\'s Design System Sync module is available. This can affect caches and synchronization behavior.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-sync-state><?php echo esc_html($elementor_debug_snapshot['design_system_sync_active'] ? __('Yes', 'ecf-framework') : __('No', 'ecf-framework')); ?></strong>
                                </div>
                                <div class="ecf-system-debug-card__item">
                                    <span class="ecf-system-debug-card__label"><?php echo $this->tip_hover_label(__('Detected limits', 'ecf-framework'), __('Shows the class and variable limits ECF currently assumes from the installed Elementor setup.', 'ecf-framework'), ''); ?></span>
                                    <strong data-ecf-debug-limits><?php echo esc_html(sprintf(__('%1$s classes / %2$s variables', 'ecf-framework'), (string) $elementor_debug_snapshot['classes_limit'], (string) $elementor_debug_snapshot['variables_limit'])); ?></strong>
                                    <small data-ecf-debug-limit-sources><?php echo esc_html(sprintf(__('Source: classes via %1$s, variables via %2$s', 'ecf-framework'), $elementor_debug_snapshot['classes_limit_source'], $elementor_debug_snapshot['variables_limit_source'])); ?></small>
                                </div>
                            </div>
                            <p class="ecf-system-debug-card__hint"><?php echo esc_html__('Useful for checking whether Elementor and its design-system modules are available before debugging sync or editor issues.', 'ecf-framework'); ?></p>
                            <div class="ecf-system-debug-card__history">
                                <div class="ecf-system-debug-card__history-header">
                                    <strong><?php echo esc_html__('Recent debug history', 'ecf-framework'); ?></strong>
                                    <button type="submit" form="ecf-clear-debug-history-form" class="ecf-btn ecf-btn--secondary ecf-btn--tiny"><?php echo esc_html__('Clear', 'ecf-framework'); ?></button>
                                </div>
                                <?php if (!empty($debug_history)): ?>
                                    <div class="ecf-system-debug-card__history-list">
                                        <?php foreach (array_slice($debug_history, 0, 12) as $entry): ?>
                                            <?php
                                            $entry_type = sanitize_key((string) ($entry['type'] ?? 'system'));
                                            $entry_type_label_map = [
                                                'sync' => __('Sync', 'ecf-framework'),
                                                'import' => __('Import/Export', 'ecf-framework'),
                                                'update' => __('Updates', 'ecf-framework'),
                                                'settings' => __('Settings', 'ecf-framework'),
                                                'system' => __('System', 'ecf-framework'),
                                            ];
                                            $entry_type_label = $entry_type_label_map[$entry_type] ?? $entry_type_label_map['system'];
                                            $entry_copy_parts = array_filter([
                                                (string) ($entry['time'] ?? ''),
                                                '[' . $entry_type_label . ']',
                                                (string) ($entry['message'] ?? ''),
                                                !empty($entry['context']) ? (string) $entry['context'] : '',
                                            ]);
                                            ?>
                                            <div class="ecf-system-debug-card__history-item">
                                                <div class="ecf-system-debug-card__history-line">
                                                    <time class="ecf-debug-timestamp"><?php echo esc_html((string) ($entry['time'] ?? '')); ?></time>
                                                    <span class="ecf-debug-type ecf-debug-type--<?php echo esc_attr($entry_type); ?>"><?php echo esc_html($entry_type_label); ?></span>
                                                    <span class="ecf-system-debug-card__history-message ecf-debug-message"><?php echo esc_html((string) ($entry['message'] ?? '')); ?></span>
                                                    <button type="button" class="ecf-debug-copy" data-ecf-copy-text="<?php echo esc_attr(implode(' ', $entry_copy_parts)); ?>"><?php echo esc_html__('Copy', 'ecf-framework'); ?></button>
                                                </div>
                                                <?php if (!empty($entry['context'])): ?>
                                                    <div class="ecf-system-debug-card__history-context">
                                                        <code><?php echo esc_html((string) $entry['context']); ?></code>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="ecf-muted-copy"><?php echo esc_html__('No debug history recorded yet. Entries appear here when WP_DEBUG is enabled and ECF logs an internal event.', 'ecf-framework'); ?></p>
                                <?php endif; ?>
                            </div>
                        </details>
                        <p class="ecf-muted-copy"><?php echo esc_html__('System status, updater behavior and Elementor limits live here. Guidance and changelog details are grouped in the Help area.', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-general-section" data-ecf-general-section="favorites" hidden>
                        <?php $this->render_general_favorites_section($settings); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_typography_panel($args) {
        extract($args, EXTR_SKIP);
        $current_body_font = $this->font_family_field_current_label(
            $settings,
            $this->normalize_font_family_field_current_value($settings, (string) ($settings['base_font_family'] ?? 'var(--ecf-font-primary)')),
            'var(--ecf-font-primary)'
        );
        $current_heading_font = $this->font_family_field_current_label(
            $settings,
            $this->normalize_font_family_field_current_value($settings, (string) ($settings['heading_font_family'] ?? 'var(--ecf-font-primary)')),
            'var(--ecf-font-primary)'
        );
        $body_size_value = $this->derived_base_body_text_size($settings);
        $body_size_parts = $this->parse_css_size_parts($body_size_value);
        $font_pairing_body_step = $this->typography_step_preview_item('m', $settings);
        $font_pairing_heading_step = $this->typography_step_preview_item('2xl', $settings);
        $font_pairing_body_max = trim((string) ($font_pairing_body_step['max_px'] ?? '18'));
        $font_pairing_heading_max = trim((string) ($font_pairing_heading_step['max_px'] ?? '28'));
        $font_pairing_body_label = '--ecf-text-m' . ' · ' . $font_pairing_body_max . 'px';
        $font_pairing_heading_label = '--ecf-text-2xl' . ' · ' . $font_pairing_heading_max . 'px';
        $font_pairings = [
            [
                'slug' => 'editorial-contrast',
                'tone_key' => 'editorial',
                'tone' => __('Editorial', 'ecf-framework'),
                'title' => __('Editorial Contrast', 'ecf-framework'),
                'description' => __('High-contrast serif headlines with a quiet sans body font for polished marketing pages.', 'ecf-framework'),
                'heading_family' => 'DM Serif Display',
                'body_family' => 'Inter',
                'heading_sample' => __('Design that feels premium at first glance', 'ecf-framework'),
                'body_sample' => __('Readable body copy keeps longer pages calm while the headline brings character.', 'ecf-framework'),
            ],
            [
                'slug' => 'editorial-modernist',
                'tone_key' => 'editorial',
                'tone' => __('Editorial', 'ecf-framework'),
                'title' => __('Modern Editorial', 'ecf-framework'),
                'description' => __('Sharp magazine-like headlines with a clean editorial body font for stories, launches and standout sections.', 'ecf-framework'),
                'heading_family' => 'Cormorant Garamond',
                'body_family' => 'Plus Jakarta Sans',
                'heading_sample' => __('Story-first design with a modern edge', 'ecf-framework'),
                'body_sample' => __('This pairing feels more cultured and expressive while the body copy stays crisp and contemporary.', 'ecf-framework'),
            ],
            [
                'slug' => 'calm-premium',
                'tone_key' => 'premium',
                'tone' => __('Premium', 'ecf-framework'),
                'title' => __('Calm Premium', 'ecf-framework'),
                'description' => __('Elegant serif headlines paired with a modern sans for brands that should feel refined, soft, and trustworthy.', 'ecf-framework'),
                'heading_family' => 'Playfair Display',
                'body_family' => 'Manrope',
                'heading_sample' => __('Quiet luxury without feeling distant', 'ecf-framework'),
                'body_sample' => __('This pairing gives headings warmth while keeping the reading experience contemporary and clear.', 'ecf-framework'),
            ],
            [
                'slug' => 'premium-boutique',
                'tone_key' => 'premium',
                'tone' => __('Premium', 'ecf-framework'),
                'title' => __('Boutique Premium', 'ecf-framework'),
                'description' => __('Refined display headlines with a smooth sans body for premium brands, studios and service businesses.', 'ecf-framework'),
                'heading_family' => 'Bodoni Moda',
                'body_family' => 'Outfit',
                'heading_sample' => __('Elegant detail with clear modern support', 'ecf-framework'),
                'body_sample' => __('The contrast feels luxurious, while the body font keeps the interface grounded and easy to scan.', 'ecf-framework'),
            ],
            [
                'slug' => 'modern-systems',
                'tone_key' => 'modern',
                'tone' => __('Modern', 'ecf-framework'),
                'title' => __('Modern Systems', 'ecf-framework'),
                'description' => __('A contemporary sans headline with a neutral body font for product, SaaS, and interface-heavy sites.', 'ecf-framework'),
                'heading_family' => 'Space Grotesk',
                'body_family' => 'Source Sans 3',
                'heading_sample' => __('A crisp system for modern product pages', 'ecf-framework'),
                'body_sample' => __('Balanced proportions and strong readability make this pair easy to use across many sections.', 'ecf-framework'),
            ],
            [
                'slug' => 'modern-friendly',
                'tone_key' => 'modern',
                'tone' => __('Modern', 'ecf-framework'),
                'title' => __('Friendly Modern', 'ecf-framework'),
                'description' => __('Soft contemporary headlines with an approachable body font for products, startups and service pages.', 'ecf-framework'),
                'heading_family' => 'Sora',
                'body_family' => 'Public Sans',
                'heading_sample' => __('Modern without feeling cold or technical', 'ecf-framework'),
                'body_sample' => __('This version feels simpler and warmer while still working well for interfaces and long-form content.', 'ecf-framework'),
            ],
            [
                'slug' => 'classic-reader',
                'tone_key' => 'classic',
                'tone' => __('Classic', 'ecf-framework'),
                'title' => __('Classic Reader', 'ecf-framework'),
                'description' => __('A familiar reading-focused serif headline with a clean sans body for content-rich websites.', 'ecf-framework'),
                'heading_family' => 'Merriweather',
                'body_family' => 'Work Sans',
                'heading_sample' => __('Built for articles, stories, and long reads', 'ecf-framework'),
                'body_sample' => __('The serif creates confidence in headings while the body font stays practical for longer text blocks.', 'ecf-framework'),
            ],
            [
                'slug' => 'classic-journal',
                'tone_key' => 'classic',
                'tone' => __('Classic', 'ecf-framework'),
                'title' => __('Classic Journal', 'ecf-framework'),
                'description' => __('Traditional literary headlines with a restrained body font for trusted, reading-heavy websites.', 'ecf-framework'),
                'heading_family' => 'Libre Baskerville',
                'body_family' => 'Lato',
                'heading_sample' => __('A timeless reading rhythm for thoughtful content', 'ecf-framework'),
                'body_sample' => __('It feels familiar, trustworthy and calm, especially on editorial pages or structured long reads.', 'ecf-framework'),
            ],
        ];
        $font_pairing_groups = [];
        foreach ($font_pairings as $pairing) {
            $tone_key = (string) ($pairing['tone_key'] ?? 'default');
            if (!isset($font_pairing_groups[$tone_key])) {
                $font_pairing_groups[$tone_key] = [
                    'tone' => $pairing['tone'],
                    'items' => [],
                ];
            }
            $font_pairing_groups[$tone_key]['items'][] = $pairing;
        }
        ?>
        <div class="ecf-panel" data-panel="typography">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="typography-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Typography', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Build your type scale, assign body and heading fonts, and review the generated text tokens in one consistent typography workspace.', 'ecf-framework'); ?></p>
                <div class="ecf-var-tabs ecf-typography-tabs" data-ecf-typography-tabs>
                    <button type="button" class="ecf-var-tab is-active" data-ecf-typography-tab="fonts" aria-pressed="true"><?php echo esc_html__('Fonts', 'ecf-framework'); ?></button>
                    <button type="button" class="ecf-var-tab" data-ecf-typography-tab="scale" aria-pressed="false"><?php echo esc_html__('Live Preview', 'ecf-framework'); ?></button>
                </div>
                <div class="ecf-typography-tab-panel is-active" data-ecf-typography-section="fonts">
                    <div class="ecf-card ecf-font-pairings-card">
                        <div class="ecf-font-pairings-card__header">
                            <div>
                                <h2><?php echo esc_html__('Recommended Font Pairings', 'ecf-framework'); ?></h2>
                                <p><?php echo esc_html__('Choose a ready-made headline and body combination when you want a faster, more confident typography start.', 'ecf-framework'); ?></p>
                            </div>
                        </div>
                        <div class="ecf-font-pairings-grid">
                            <?php foreach ($font_pairing_groups as $group_key => $group): ?>
                                <?php $pairing = $group['items'][0]; ?>
                                <article class="ecf-font-pairing" data-ecf-font-pairing-card data-ecf-font-pairing-current="<?php echo esc_attr($pairing['slug']); ?>">
                                    <div class="ecf-font-pairing__meta">
                                        <div class="ecf-font-pairing__topline">
                                            <span class="ecf-preview-pill" data-ecf-font-pairing-tone><?php echo esc_html($pairing['tone']); ?></span>
                                            <div class="ecf-font-pairing__actions">
                                                <button type="button"
                                                        class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-font-pairing__icon"
                                                        data-ecf-font-pairing-reset
                                                        data-ecf-font-pairing-default="<?php echo esc_attr(wp_json_encode($group['items'][0])); ?>"
                                                        aria-label="<?php echo esc_attr__('Reset to the default pairing', 'ecf-framework'); ?>"
                                                        title="<?php echo esc_attr__('Reset to the default pairing', 'ecf-framework'); ?>">
                                                    <span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
                                                </button>
                                            <button type="button"
                                                    class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-font-pairing__random"
                                                    data-ecf-font-pairing-random
                                                    data-ecf-font-pairing-options="<?php echo esc_attr(wp_json_encode($group['items'])); ?>">
                                                <?php echo esc_html__('Random in this category', 'ecf-framework'); ?>
                                            </button>
                                            </div>
                                        </div>
                                        <h3 data-ecf-font-pairing-title><?php echo esc_html($pairing['title']); ?></h3>
                                        <p data-ecf-font-pairing-description><?php echo esc_html($pairing['description']); ?></p>
                                    </div>
                                    <div class="ecf-font-pairing__preview">
                                        <div class="ecf-font-pairing__preview-head" data-ecf-font-pairing-preview-head style="font-family: '<?php echo esc_attr($pairing['heading_family']); ?>', serif; font-size: <?php echo esc_attr($font_pairing_heading_max); ?>px;">
                                            <?php echo esc_html($pairing['heading_sample']); ?>
                                        </div>
                                        <div class="ecf-font-pairing__preview-body" data-ecf-font-pairing-preview-body style="font-family: '<?php echo esc_attr($pairing['body_family']); ?>', sans-serif; font-size: <?php echo esc_attr($font_pairing_body_max); ?>px;">
                                            <?php echo esc_html($pairing['body_sample']); ?>
                                        </div>
                                    </div>
                                    <dl class="ecf-font-pairing__families">
                                        <div>
                                            <dt><?php echo esc_html__('Headings', 'ecf-framework'); ?></dt>
                                            <dd data-ecf-font-pairing-heading-family><?php echo esc_html($pairing['heading_family']); ?></dd>
                                            <small class="ecf-font-pairing__token"><?php echo esc_html($font_pairing_heading_label); ?></small>
                                        </div>
                                        <div>
                                            <dt><?php echo esc_html__('Body', 'ecf-framework'); ?></dt>
                                            <dd data-ecf-font-pairing-body-family><?php echo esc_html($pairing['body_family']); ?></dd>
                                            <small class="ecf-font-pairing__token"><?php echo esc_html($font_pairing_body_label); ?></small>
                                        </div>
                                    </dl>
                                    <button type="button"
                                            class="ecf-btn ecf-btn--secondary ecf-font-pairing__apply"
                                            data-ecf-font-pairing-apply
                                            data-ecf-font-pairing-slug="<?php echo esc_attr($pairing['slug']); ?>"
                                            data-ecf-font-pairing-heading="<?php echo esc_attr($pairing['heading_family']); ?>"
                                            data-ecf-font-pairing-body="<?php echo esc_attr($pairing['body_family']); ?>">
                                        <?php echo esc_html__('Use this pairing', 'ecf-framework'); ?>
                                    </button>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="ecf-typography-font-grid">
                        <details class="ecf-card ecf-typography-font-card">
                            <summary class="ecf-typography-font-card__summary">
                                <span>
                                    <strong><?php echo esc_html__('Body Font', 'ecf-framework'); ?></strong>
                                    <span class="ecf-typography-font-card__meta">
                                        <span data-ecf-typography-body-current><?php echo esc_html__('Current:', 'ecf-framework'); ?> <?php echo esc_html($current_body_font); ?></span>
                                        <span data-ecf-typography-body-size><?php echo esc_html__('Font size:', 'ecf-framework'); ?> <?php echo esc_html($body_size_parts['value'] . ' ' . $body_size_parts['format']); ?></span>
                                    </span>
                                    <small><?php echo esc_html__('Default font for flowing text and normal site copy.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-typography-font-card__content">
                                <?php $this->render_base_font_family_field($settings, false); ?>
                            </div>
                        </details>
                        <details class="ecf-card ecf-typography-font-card">
                            <summary class="ecf-typography-font-card__summary">
                                <span>
                                    <strong><?php echo esc_html__('Heading Font', 'ecf-framework'); ?></strong>
                                    <span class="ecf-typography-font-card__meta">
                                        <span data-ecf-typography-heading-current><?php echo esc_html__('Current:', 'ecf-framework'); ?> <?php echo esc_html($current_heading_font); ?></span>
                                    </span>
                                    <small><?php echo esc_html__('Separate font family for h1 to h6 and heading-like elements.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-typography-font-card__content">
                                <?php $this->render_heading_font_family_field($settings, false); ?>
                            </div>
                        </details>
                    </div>
                    <div class="ecf-grid" data-ecf-layout-group="typography-font-details" data-ecf-masonry-layout="1">
                        <details class="ecf-card ecf-card--details" data-ecf-layout-item="typography-fonts" open>
                            <summary class="ecf-card__summary">
                                <span>
                                    <strong><?php echo esc_html__('Core Font Tokens', 'ecf-framework'); ?></strong>
                                    <small><?php echo esc_html__('Reusable typography stacks like Primary and Secondary.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-card__content">
                                <?php $this->render_rows('typography_fonts', array_slice((array) $settings['typography']['fonts'], 0, 2), $this->option_name.'[typography][fonts]'); ?>
                            </div>
                        </details>
                        <details class="ecf-card ecf-card--details" data-ecf-layout-item="typography-local-fonts" data-ecf-local-fonts-section>
                            <summary class="ecf-card__summary">
                                <span>
                                    <strong><?php echo esc_html__('Imported Local Fonts', 'ecf-framework'); ?></strong>
                                    <small><?php echo esc_html__('Manage the fonts that were imported locally from the library.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-card__content">
                                <p class="ecf-muted-copy"><?php echo esc_html__('The old manual upload flow is intentionally hidden here to keep the typography workflow focused and consistent.', 'ecf-framework'); ?></p>
                                <?php $this->render_imported_local_font_rows($settings['typography']['local_fonts'] ?? [], $this->option_name.'[typography][local_fonts]'); ?>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="ecf-typography-tab-panel" data-ecf-typography-section="scale" hidden>
                    <div class="ecf-typography-tab-intro">
                        <div>
                            <h2><?php echo esc_html__('Live Typography Preview', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Review your type scale and generated text tokens in a live side-by-side preview while you fine-tune the reading rhythm.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <div class="ecf-typography-scale-shell">
                        <div class="ecf-typography-sidebar" data-ecf-layout-item="typography-settings">
                            <div class="ecf-card">
                        <h2><?php echo esc_html__('Type Scale', 'ecf-framework'); ?></h2>
                        <p class="ecf-card-intro"><?php echo esc_html__('Shape the reading rhythm here first. The preview on the right mirrors the generated text tokens live while you edit.', 'ecf-framework'); ?></p>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--compact">
                            <div class="ecf-scale-group">
                                <strong class="ecf-scale-group__title"><?php echo esc_html__('Minimum', 'ecf-framework'); ?></strong>
                                <label><?php echo $this->tip_hover_label(__('Min Font Size (px)', 'ecf-framework'), __('Font size at the smallest viewport (mobile). The base step gets this size.', 'ecf-framework'), ''); ?>
                                    <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][min_base]" value="<?php echo esc_attr($settings['typography']['scale']['min_base'] ?? 16); ?>">
                                </label>
                                <label><?php echo $this->tip_hover_label(__('Min Scale Ratio', 'ecf-framework'), __('Multiplier between steps at mobile size. E.g. 1.125 means each step is 12.5% larger.', 'ecf-framework'), ''); ?>
                                    <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][min_ratio]', $settings['typography']['scale']['min_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.125)); ?>
                                </label>
                            </div>
                            <div class="ecf-scale-group ecf-scale-group--divider">
                                <strong class="ecf-scale-group__title"><?php echo esc_html__('Maximum', 'ecf-framework'); ?></strong>
                                <label><?php echo $this->tip_hover_label(__('Max Font Size (px)', 'ecf-framework'), __('Font size at the largest viewport (desktop). The base step gets this size.', 'ecf-framework'), ''); ?>
                                    <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][max_base]" value="<?php echo esc_attr($settings['typography']['scale']['max_base'] ?? 18); ?>">
                                </label>
                                <label><?php echo $this->tip_hover_label(__('Max Scale Ratio', 'ecf-framework'), __('Multiplier between steps at desktop size. A higher ratio creates more contrast between sizes.', 'ecf-framework'), ''); ?>
                                    <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][max_ratio]', $settings['typography']['scale']['max_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.25)); ?>
                                </label>
                            </div>
                            <label><?php echo $this->tip_hover_label(__('Base step', 'ecf-framework'), __('The step that equals your base font size. Steps above are larger, steps below are smaller.', 'ecf-framework'), ''); ?>
                                <select name="<?php echo $this->option_name; ?>[typography][scale][base_index]">
                                    <?php foreach ($settings['typography']['scale']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['typography']['scale']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-check ecf-check--inline">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[typography][scale][fluid]" value="1" <?php checked(!empty($settings['typography']['scale']['fluid'])); ?>>
                                <span><?php echo $this->tip_hover_label(__('Fluid (clamp)', 'ecf-framework'), __('Generates clamp() values that smoothly scale between min and max viewport width.', 'ecf-framework'), ''); ?></span>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min viewport (px)', 'ecf-framework'), __('Viewport width at which the minimum font sizes apply (typically 375px for mobile).', 'ecf-framework'), ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][min_vw]" value="<?php echo esc_attr($settings['typography']['scale']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max viewport (px)', 'ecf-framework'), __('Viewport width at which the maximum font sizes apply (typically 1280px for desktop).', 'ecf-framework'), ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][max_vw]" value="<?php echo esc_attr($settings['typography']['scale']['max_vw']); ?>">
                            </label>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html__('The preview updates live while you edit the scale settings.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-grid" data-ecf-layout-group="typography-secondary" data-ecf-masonry-layout="1">
                            <details class="ecf-settings-group ecf-settings-group--details" data-ecf-layout-item="website-scale-impact">
                                <summary class="ecf-card__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Visible effect of the root font size', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('See how the current root size affects the base text, spacing and radius output.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-card__content">
                                    <?php $this->render_root_font_impact_panel($settings); ?>
                                </div>
                            </details>
                            <details class="ecf-card ecf-card--details" data-ecf-layout-item="typography-weights" open>
                                <summary class="ecf-card__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Font Weights', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('Named weight tokens for text styles and component typography.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-card__content">
                                    <?php $this->render_rows('typography_weights', $settings['typography']['weights'], $this->option_name.'[typography][weights]'); ?>
                                </div>
                            </details>
                            <details class="ecf-card ecf-card--details" data-ecf-layout-item="typography-line-heights">
                                <summary class="ecf-card__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Line Heights', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('Vertical rhythm tokens for readable paragraphs and display text.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-card__content">
                                    <?php $this->render_rows('typography_leading', $settings['typography']['leading'], $this->option_name.'[typography][leading]'); ?>
                                </div>
                            </details>
                            <details class="ecf-card ecf-card--details" data-ecf-layout-item="typography-letter-spacing">
                                <summary class="ecf-card__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Letter Spacing', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('Tracking tokens for tighter headings or looser interface labels.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-card__content">
                                    <?php $this->render_rows('typography_tracking', $settings['typography']['tracking'], $this->option_name.'[typography][tracking]'); ?>
                                </div>
                            </details>
                        </div>
                        </div>
                        <div class="ecf-typography-main" data-ecf-layout-item="typography-preview">
                            <div class="ecf-card ecf-typography-preview-card"
                                 data-ecf-type-scale-preview
                                 style="--ecf-preview-font: <?php echo esc_attr($type_preview_font); ?>;"
                                 data-steps="<?php echo esc_attr(wp_json_encode($settings['typography']['scale']['steps'])); ?>"
                                 data-active-step="<?php echo esc_attr($settings['typography']['scale']['base_index']); ?>"
                                 data-preview-label-min="<?php echo esc_attr__('Minimum', 'ecf-framework'); ?>"
                                 data-preview-label-max="<?php echo esc_attr__('Maximum', 'ecf-framework'); ?>"
                                 data-preview-label-fixed="<?php echo esc_attr__('Static', 'ecf-framework'); ?>"
                                 data-preview-label-fluid="<?php echo esc_attr__('Fluid', 'ecf-framework'); ?>"
                                 data-preview-word="<?php echo esc_attr($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?>"
                                 data-preview-helper="<?php echo esc_attr__('Click a scale step to inspect it in detail.', 'ecf-framework'); ?>"
                                 data-preview-font="<?php echo esc_attr($type_preview_font); ?>">
                                <div class="ecf-typography-preview-header">
                                    <div>
                                        <h2><?php echo esc_html__('Live Type Preview', 'ecf-framework'); ?></h2>
                                        <p><?php echo esc_html__('Preview for your generated Elementor text variables.', 'ecf-framework'); ?></p>
                                    </div>
                                    <div class="ecf-preview-toolbar">
                                        <button type="button" class="ecf-preview-toggle" data-ecf-preview-view="min"><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></button>
                                        <button type="button" class="ecf-preview-toggle is-active" data-ecf-preview-view="fluid"><?php echo esc_html__('Fluid', 'ecf-framework'); ?></button>
                                        <button type="button" class="ecf-preview-toggle" data-ecf-preview-view="max"><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></button>
                                    </div>
                                </div>
                                <div class="ecf-typography-focus" data-ecf-type-scale-focus>
                                    <div class="ecf-typography-focus__meta">
                                        <span class="ecf-preview-pill" data-ecf-preview-mode><?php echo esc_html(!empty($settings['typography']['scale']['fluid']) ? __('Fluid', 'ecf-framework') : __('Static', 'ecf-framework')); ?></span>
                                        <strong data-ecf-focus-token>--ecf-text-<?php echo esc_html($settings['typography']['scale']['base_index']); ?></strong>
                                        <p data-ecf-focus-helper><?php echo esc_html__('Click a scale step to inspect it in detail.', 'ecf-framework'); ?></p>
                                    </div>
                                    <div class="ecf-typography-focus__sample">
                                        <div class="ecf-typography-focus__word" data-ecf-focus-word><?php echo esc_html($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?></div>
                                        <div class="ecf-typography-focus__stats">
                                            <div>
                                                <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                                                <div class="ecf-clamp-metric">
                                                    <strong data-ecf-focus-min><?php echo esc_html($base_type_preview['min_px'] ?? '16'); ?>px</strong>
                                                </div>
                                            </div>
                                            <div>
                                                <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                                                <div class="ecf-clamp-metric">
                                                    <strong data-ecf-focus-max><?php echo esc_html($base_type_preview['max_px'] ?? '16'); ?>px</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ecf-typography-focus__sizes">
                                            <div class="ecf-clamp-group ecf-clamp-group--focus">
                                                <button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="<?php echo esc_attr__('Show clamp value', 'ecf-framework'); ?>"><span class="dashicons dashicons-editor-code"></span></button>
                                                <button type="button" class="ecf-clamp-popover" data-ecf-focus-copy><?php echo esc_html($base_type_preview['css_value'] ?? ''); ?></button>
                                            </div>
                                            <div class="ecf-typography-focus__size-line">
                                                <span class="ecf-typography-focus__sample-text" data-ecf-focus-min-line><?php echo esc_html($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?></span>
                                                <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                                            </div>
                                            <div class="ecf-typography-focus__size-line ecf-typography-focus__size-line--max">
                                                <span class="ecf-typography-focus__sample-text" data-ecf-focus-max-line><?php echo esc_html($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?></span>
                                                <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="ecf-scale-steps-container">
                                <?php foreach ($settings['typography']['scale']['steps'] as $step): ?>
                                <input type="hidden" class="ecf-scale-step-input" name="<?php echo esc_attr($this->option_name); ?>[typography][scale][steps][]" value="<?php echo esc_attr($step); ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="ecf-step-controls ecf-step-controls--top">
                                <button type="button" class="ecf-step-btn" data-ecf-add-step="smaller" data-tip="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>">+</button>
                                <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="smaller" data-tip="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>">−</button>
                            </div>
                            <div class="ecf-typography-preview-list" data-ecf-type-scale-preview-list>
                                <?php foreach ($type_scale_preview as $item): ?>
                                    <div class="ecf-type-row<?php echo $item['step'] === $settings['typography']['scale']['base_index'] ? ' is-active' : ''; ?>" data-ecf-step="<?php echo esc_attr($item['step']); ?>" data-ecf-step-row tabindex="0" role="button" aria-pressed="<?php echo $item['step'] === $settings['typography']['scale']['base_index'] ? 'true' : 'false'; ?>" style="<?php echo esc_attr('--ecf-preview-size:' . $item['css_value'] . ';'); ?>">
                                    <div class="ecf-type-row__token">
                                        <div class="ecf-type-row__token-line">
                                            <span class="ecf-type-row__token-label"><?php echo esc_html($item['token']); ?></span>
                                            <button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="<?php echo esc_attr__('Show clamp value', 'ecf-framework'); ?>"><span class="dashicons dashicons-editor-code"></span></button>
                                            <span class="ecf-copy-pill" data-copy="<?php echo esc_attr($item['token']); ?>"><?php echo esc_html__('Copy', 'ecf-framework'); ?></span>
                                        </div>
                                        <button type="button" class="ecf-clamp-popover" data-copy="<?php echo esc_attr($item['css_value']); ?>"><?php echo esc_html($item['css_value']); ?></button>
                                    </div>
                                    <div class="ecf-type-row__meta">
                                        <div><span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span><strong><?php echo esc_html($item['min_px']); ?>px</strong></div>
                                        <div><span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span><strong><?php echo esc_html($item['max_px']); ?>px</strong></div>
                                    </div>
                                    <div class="ecf-type-row__sample">
                                        <div class="ecf-type-row__sample-line">
                                            <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                                            <span class="ecf-type-row__sample-text" style="font-size:<?php echo esc_attr($item['min_px']); ?>px;"><?php echo esc_html($this->type_preview_text_for_step((string) $item['step'], $settings)); ?></span>
                                        </div>
                                        <div class="ecf-type-row__sample-line ecf-type-row__sample-line--max">
                                            <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                                            <span class="ecf-type-row__sample-text" style="font-size:<?php echo esc_attr($item['max_px']); ?>px;"><?php echo esc_html($this->type_preview_text_for_step((string) $item['step'], $settings)); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="ecf-step-controls ecf-step-controls--bottom">
                                <button type="button" class="ecf-step-btn" data-ecf-add-step="larger" data-tip="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>">+</button>
                                <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="larger" data-tip="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>">−</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_spacing_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="spacing">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="spacing-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Spacing', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Define the spacing scale, container widths and the live spacing preview inside one shared layout shell.', 'ecf-framework'); ?></p>
                <div class="ecf-spacing-layout" data-ecf-layout-group="spacing-main">
                    <div class="ecf-spacing-sidebar" data-ecf-layout-item="spacing-settings">
                        <div class="ecf-card">
                        <h2><?php echo esc_html__('Base Settings', 'ecf-framework'); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label><?php echo $this->tip_hover_label(__('Naming Convention', 'ecf-framework'), __('Prefix used for CSS tokens, e.g. "space" → --ecf-space-m. Change to rename all tokens.', 'ecf-framework'), ''); ?>
                                <input type="text" name="<?php echo $this->option_name; ?>[spacing][prefix]" value="<?php echo esc_attr($settings['spacing']['prefix'] ?? 'space'); ?>" placeholder="space">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Size (px)', 'ecf-framework'), __('Base spacing size at the smallest viewport (mobile). All other steps scale relative to this.', 'ecf-framework'), ''); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][min_base]" value="<?php echo esc_attr($settings['spacing']['min_base'] ?? 14); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Scale Ratio', 'ecf-framework'), __('Multiplier between spacing steps on mobile. 1.25 means each step is 25% larger than the previous.', 'ecf-framework'), ''); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][min_ratio]', $settings['spacing']['min_ratio'] ?? 1.2); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Size (px)', 'ecf-framework'), __('Base spacing size at the largest viewport (desktop). Typically slightly larger than the min size.', 'ecf-framework'), ''); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][max_base]" value="<?php echo esc_attr($settings['spacing']['max_base'] ?? $settings['spacing']['base'] ?? 16); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Scale Ratio', 'ecf-framework'), __('Multiplier between spacing steps on desktop. A higher ratio creates more visual contrast between sizes.', 'ecf-framework'), ''); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][max_ratio]', $settings['spacing']['max_ratio'] ?? $settings['spacing']['ratio_up'] ?? 1.25); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Base Step', 'ecf-framework'), __('The step that equals your base spacing size. Steps above are larger, steps below are smaller.', 'ecf-framework'), ''); ?>
                                <select name="<?php echo $this->option_name; ?>[spacing][base_index]">
                                    <?php foreach ($settings['spacing']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['spacing']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-form-grid__checkbox">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[spacing][fluid]" value="1" <?php checked(!empty($settings['spacing']['fluid'])); ?>>
                                <?php echo $this->tip_hover_label(__('Fluid (clamp)', 'ecf-framework'), __('Generates clamp() values that smoothly scale between min and max viewport widths.', 'ecf-framework'), ''); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Viewport (px)', 'ecf-framework'), __('Screen width at which minimum spacing sizes apply. Usually 375px (iPhone).', 'ecf-framework'), ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[spacing][min_vw]" value="<?php echo esc_attr($settings['spacing']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Viewport (px)', 'ecf-framework'), __('Screen width at which maximum spacing sizes apply. Usually 1280px (desktop).', 'ecf-framework'), ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[spacing][max_vw]" value="<?php echo esc_attr($settings['spacing']['max_vw']); ?>">
                            </label>
                        </div>
                    </div>
                    <details class="ecf-card ecf-card--details" style="margin-top:14px;" open>
                        <summary class="ecf-card__summary">
                            <span>
                                <strong><?php echo esc_html__('Container Widths', 'ecf-framework'); ?></strong>
                                <small><?php echo esc_html__('Named width sizes for wrappers and layout containers.', 'ecf-framework'); ?></small>
                            </span>
                            <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                        </summary>
                        <div class="ecf-card__content">
                            <div class="ecf-form-grid ecf-form-grid--single">
                                <?php foreach (['sm','md','lg','xl'] as $size): ?>
                                    <label><?php echo esc_html(strtoupper($size)); ?>
                                        <input type="text" name="<?php echo $this->option_name; ?>[container][<?php echo esc_attr($size); ?>]" value="<?php echo esc_attr($settings['container'][$size]); ?>">
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </details>
                    </div>

                    <div class="ecf-card ecf-spacing-preview-card"
                     data-ecf-layout-item="spacing-preview"
                     data-ecf-spacing-preview
                     data-steps="<?php echo esc_attr(wp_json_encode($settings['spacing']['steps'])); ?>"
                     data-active-step="<?php echo esc_attr($settings['spacing']['base_index']); ?>"
                     data-preview-label-min="<?php echo esc_attr__('Minimum', 'ecf-framework'); ?>"
                     data-preview-label-max="<?php echo esc_attr__('Maximum', 'ecf-framework'); ?>">
                    <div class="ecf-spacing-preview-header">
                        <div>
                            <h2><?php echo esc_html__('Live Spacing Preview', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Preview of your generated spacing tokens.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <div id="ecf-spacing-steps-container">
                        <?php foreach ($settings['spacing']['steps'] as $step): ?>
                        <input type="hidden" class="ecf-spacing-step-input" name="<?php echo esc_attr($this->option_name); ?>[spacing][steps][]" value="<?php echo esc_attr($step); ?>">
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--top">
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="smaller" data-tip="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="smaller" data-tip="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>">−</button>
                    </div>
                    <div class="ecf-spacing-preview-list" data-ecf-spacing-preview-list>
                        <?php
                        foreach ($spacing_preview as $item):
                            $item_min = (float) ($item['min_px'] ?? $item['min'] ?? 0);
                            $item_max = (float) ($item['max_px'] ?? $item['max'] ?? 0);
                            if ($item_min > $item_max) {
                                [$item_min, $item_max] = [$item_max, $item_min];
                            }
                        ?>
                        <div class="ecf-space-row<?php echo $item['is_base'] ? ' is-base' : ''; ?>" data-ecf-space-step="<?php echo esc_attr($item['step']); ?>">
                            <div class="ecf-space-row__token"><span class="ecf-space-row__token-text ecf-spacing-token-name"><?php echo esc_html($item['token']); ?></span><span class="ecf-copy-pill" data-copy="<?php echo esc_attr($item['token']); ?>"><?php echo esc_html__('Copy', 'ecf-framework'); ?></span></div>
                            <div class="ecf-space-row__meta">
                                <div><span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span><strong><?php echo esc_html($this->format_preview_number($item_min, 3)); ?>px</strong></div>
                                <div><span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span><strong><?php echo esc_html($this->format_preview_number($item_max, 3)); ?>px</strong></div>
                            </div>
                            <div class="ecf-space-row__bar">
                                <div class="ecf-space-row__bar-fill" style="width:<?php echo esc_attr($this->format_preview_number($item_max, 3)); ?>px;height:<?php echo esc_attr(min(40, max(4, round($item_max)))); ?>px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--bottom">
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="larger" data-tip="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="larger" data-tip="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>">−</button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_sync_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="sync">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="sync-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Sync & Export', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Review what goes into Elementor, run the sync safely, and export or import your current setup from one place.', 'ecf-framework'); ?></p>
                <div class="ecf-grid" data-ecf-layout-group="sync-main" data-ecf-masonry-layout="1">
                    <div class="ecf-card ecf-sync-card ecf-sync-card--primary" data-ecf-layout-item="sync-native">
                    <div class="ecf-sync-card__header">
                        <div>
                            <h2><?php echo esc_html__('Native Elementor Sync', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Use this when your tokens and selected classes are ready. Layrix adds its data in merge mode without overwriting unrelated Elementor entries.', 'ecf-framework'); ?></p>
                        </div>
                        <div class="ecf-sync-card__chips" aria-hidden="true">
                            <span><?php echo esc_html__('Merge mode', 'ecf-framework'); ?></span>
                            <span><?php echo esc_html__('Safe first', 'ecf-framework'); ?></span>
                        </div>
                    </div>
                    <div class="ecf-notice ecf-notice--warning">
                        <?php echo esc_html__('⚠ Sync actions change your Elementor data. Please create a backup first.', 'ecf-framework'); ?>
                    </div>
                    <p class="ecf-sync-status">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Currently found in Elementor: %1$d ECF variables and %2$d Global Classes.', 'ecf-framework'),
                                $cleanup_variable_count,
                                $cleanup_class_count
                            )
                        );
                        ?>
                    </p>
                    <?php if ($show_elementor_status_cards): ?>
                        <div class="ecf-class-limit-card ecf-class-limit-card--compact ecf-class-limit-card--<?php echo esc_attr($elementor_class_limit_status); ?>" data-ecf-class-usage-card="compact" data-ecf-class-limit="<?php echo esc_attr((string) $elementor_class_limit); ?>">
                            <strong><?php echo esc_html__('Elementor Global Classes', 'ecf-framework'); ?></strong>
                            <p>
                                <?php if ($this->is_backend_german()): ?>
                                    Elementor nutzt aktuell
                                    <span class="ecf-total-global-classes-compact"><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    von
                                    <span class="ecf-limit-global-classes-compact"><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                    globale Klassen. Neue Klassen können nur angelegt werden, solange noch freie Plätze vorhanden sind.
                                <?php else: ?>
                                    Elementor currently uses
                                    <span class="ecf-total-global-classes-compact"><?php echo esc_html((string) $elementor_total_class_count); ?></span>
                                    of
                                    <span class="ecf-limit-global-classes-compact"><?php echo esc_html((string) $elementor_class_limit); ?></span>
                                    Global Classes. New classes can only be created while free slots remain.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <p class="ecf-sync-card__merge-note"><?php echo wp_kses(__('Works in <strong>merge mode</strong> — ECF adds to existing Elementor variables and Global Classes without overwriting.', 'ecf-framework'), ['strong' => []]); ?></p>
                    <div class="ecf-sync-summary">
                        <div class="ecf-sync-summary__item">
                            <span><?php echo esc_html__('Variables', 'ecf-framework'); ?></span>
                            <strong><code>ecf-color-*</code>, <code>ecf-space-*</code>, <code>ecf-radius-*</code>, <code>ecf-text-*</code></strong>
                        </div>
                        <div class="ecf-sync-summary__item">
                            <span><?php echo esc_html__('ecf_sync_layrix_variable_count_label', 'ecf-framework'); ?></span>
                            <strong data-ecf-layrix-variable-count><?php echo esc_html((string) ($layrix_variable_count ?? 0)); ?></strong>
                        </div>
                        <div class="ecf-sync-summary__item">
                            <span><?php echo esc_html__('Global Classes', 'ecf-framework'); ?></span>
                            <strong><?php echo esc_html__('selected starter classes and selected utility classes', 'ecf-framework'); ?></strong>
                        </div>
                    </div>
                    <div class="ecf-sync-actions">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=ecf_native_sync')); ?>">
                            <?php wp_nonce_field('ecf_native_sync'); ?>
                            <input type="hidden" name="action" value="ecf_native_sync">
                            <button type="submit" class="ecf-btn ecf-btn--primary"><span class="dashicons dashicons-update" aria-hidden="true"></span><span><?php echo esc_html__('Sync to Elementor (Merge)', 'ecf-framework'); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Do you really want to remove %1$d ECF Global Classes from Elementor so they can be synced again as empty classes?', 'ecf-framework'), $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_class_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_class_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--ghost" <?php disabled($cleanup_class_count === 0); ?> data-tip="<?php echo esc_attr($cleanup_class_count === 0 ? __('No ECF classes found in Elementor.', 'ecf-framework') : sprintf(__('Removes %1$d ECF classes from Elementor without touching variables.', 'ecf-framework'), $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html__('Cleanup ECF Classes', 'ecf-framework'); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Do you really want to remove %1$d ECF variables and %2$d Global Classes from Elementor?', 'ecf-framework'), $cleanup_variable_count, $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_native_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_native_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--danger" <?php disabled($cleanup_total_count === 0); ?> data-tip="<?php echo esc_attr($cleanup_total_count === 0 ? __('No ECF variables or classes found in Elementor.', 'ecf-framework') : sprintf(__('Removes %1$d variables and %2$d classes from Elementor.', 'ecf-framework'), $cleanup_variable_count, $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html__('Cleanup ECF from Elementor', 'ecf-framework'); ?></span></button>
                        </form>
                    </div>
                    </div>

                    <div class="ecf-card ecf-sync-card" data-ecf-layout-item="sync-import-export">
                    <div class="ecf-sync-card__header">
                        <div>
                            <h2><?php echo esc_html__('Export / Import', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Move settings between installations with one JSON file. Export first if you want a clean rollback point before larger changes.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <div class="ecf-import-actions">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('ecf_export'); ?>
                            <input type="hidden" name="action" value="ecf_export">
                            <button type="submit" class="ecf-btn ecf-btn--ghost"><span class="dashicons dashicons-download" aria-hidden="true"></span><span><?php echo esc_html__('Export JSON', 'ecf-framework'); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="ecf-import-form">
                            <?php wp_nonce_field('ecf_import'); ?>
                            <input type="hidden" name="action" value="ecf_import">
                            <input type="file" name="ecf_import_file" accept=".json" required class="ecf-file ecf-import-form__file" data-ecf-import-file>
                            <button type="submit" class="ecf-btn ecf-btn--ghost"><span class="dashicons dashicons-upload" aria-hidden="true"></span><span><?php echo esc_html__('Import', 'ecf-framework'); ?></span></button>
                        </form>
                    </div>
                    <div class="ecf-import-preview" data-ecf-import-preview hidden>
                        <strong data-ecf-import-preview-title><?php echo esc_html__('Import preview', 'ecf-framework'); ?></strong>
                        <div class="ecf-import-preview__meta" data-ecf-import-preview-meta></div>
                        <div class="ecf-import-preview__warning" data-ecf-import-preview-warning hidden></div>
                    </div>
                    </div>

                    <div class="ecf-card ecf-sync-card ecf-sync-card--aside" data-ecf-layout-item="sync-editor-panel">
                    <div class="ecf-sync-card__header">
                        <div>
                            <h2><?php echo esc_html__('After sync', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Keep the final Elementor step simple so freshly synced variables and classes appear where you expect them.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <ul class="ecf-sync-checklist">
                        <li><?php echo esc_html__('Reload open Elementor tabs once after a manual sync.', 'ecf-framework'); ?></li>
                        <li><?php echo esc_html__('Use Auto-Sync when you want Layrix to push changes right after autosave.', 'ecf-framework'); ?></li>
                        <li><?php echo esc_html__('If something still looks outdated, clear Elementor caches once and reopen the editor.', 'ecf-framework'); ?></li>
                    </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_help_panel($changelog_entries) {
        $debug_history = $this->debug_history_entries();
        $getting_started_items = $this->help_getting_started_items();
        $quick_help_items = $this->help_quick_help_items();
        ?>
        <div class="ecf-panel" data-panel="help">
            <div class="ecf-card ecf-panel-shell" data-ecf-layout-item="help-shell">
                <div class="ecf-vargroup-header">
                    <h2><?php echo esc_html__('Help', 'ecf-framework'); ?></h2>
                </div>
                <p class="ecf-muted-copy ecf-class-library-intro"><?php echo esc_html__('Find the fastest setup path, quick explanations and the latest diagnostics in one calm help area.', 'ecf-framework'); ?></p>
                <div class="ecf-grid" data-ecf-layout-group="help-main" data-ecf-masonry-layout="1">
                    <div class="ecf-card ecf-help-start-card" data-ecf-layout-item="help-start">
                    <h2><?php echo esc_html__('Getting started', 'ecf-framework'); ?></h2>
                    <p class="ecf-muted-copy"><?php echo esc_html__('The fastest setup path for a fresh project: define the basics first, then sync only the parts you really want in Elementor.', 'ecf-framework'); ?></p>
                    <ol class="ecf-help-start-list">
                        <?php foreach ($getting_started_items as $item): ?>
                            <li>
                                <strong><?php echo esc_html($item['title']); ?></strong>
                                <span><?php echo esc_html($item['description']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    </div>
                    <div class="ecf-card" data-ecf-layout-item="help-quick">
                    <h2><?php echo esc_html__('Quick help', 'ecf-framework'); ?></h2>
                    <div class="ecf-system-help-card__content">
                        <?php foreach ($quick_help_items as $item): ?>
                            <div class="ecf-system-help-card__item">
                                <strong><?php echo esc_html($item['title']); ?></strong>
                                <p><?php echo esc_html($item['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                    <div class="ecf-card" data-ecf-layout-item="help-changelog-link">
                    <div class="ecf-changelog-header">
                        <div>
                            <h2><?php echo esc_html__('Version Changelog', 'ecf-framework'); ?></h2>
                            <p><?php echo esc_html__('Open the changelog modal for the full release history instead of repeating the same entries inside Help.', 'ecf-framework'); ?></p>
                        </div>
                        <button type="button" class="ecf-btn ecf-btn--secondary" data-ecf-open-changelog-modal>
                            <span class="dashicons dashicons-backup" aria-hidden="true"></span>
                            <span><?php echo esc_html__('Open changelog', 'ecf-framework'); ?></span>
                        </button>
                    </div>
                    </div>
                    <div class="ecf-card" data-ecf-layout-item="help-diagnostics">
                    <h2><?php echo esc_html__('Diagnostics', 'ecf-framework'); ?></h2>
                    <p class="ecf-muted-copy"><?php echo esc_html__('Technical status, updater controls and live Elementor limits are available under Settings > System. The latest debug entries are mirrored here for quick support review.', 'ecf-framework'); ?></p>
                    <?php if (!empty($debug_history)): ?>
                        <div class="ecf-system-debug-card__history-list">
                            <?php foreach (array_slice($debug_history, 0, 5) as $entry): ?>
                                <?php
                                $entry_type = sanitize_key((string) ($entry['type'] ?? 'system'));
                                $entry_type_label_map = [
                                    'sync' => __('Sync', 'ecf-framework'),
                                    'import' => __('Import/Export', 'ecf-framework'),
                                    'update' => __('Updates', 'ecf-framework'),
                                    'settings' => __('Settings', 'ecf-framework'),
                                    'system' => __('System', 'ecf-framework'),
                                ];
                                $entry_type_label = $entry_type_label_map[$entry_type] ?? $entry_type_label_map['system'];
                                ?>
                                <div class="ecf-system-debug-card__history-item">
                                    <div class="ecf-system-debug-card__history-meta">
                                        <time class="ecf-debug-timestamp"><?php echo esc_html((string) ($entry['time'] ?? '')); ?></time>
                                        <span class="ecf-debug-type ecf-debug-type--<?php echo esc_attr($entry_type); ?>"><?php echo esc_html($entry_type_label); ?></span>
                                    </div>
                                    <strong class="ecf-debug-message"><?php echo esc_html((string) ($entry['message'] ?? '')); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="ecf-muted-copy"><?php echo esc_html__('No debug history recorded yet. Entries appear here when WP_DEBUG is enabled and ECF logs an internal event.', 'ecf-framework'); ?></p>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_changelog_modal($changelog_entries) {
        ?>
        <div class="ecf-modal" data-ecf-changelog-modal hidden>
            <div class="ecf-modal__backdrop" data-ecf-close-changelog-modal></div>
            <div class="ecf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ecf-changelog-modal-title">
                <div class="ecf-modal__header">
                    <div>
                        <h2 id="ecf-changelog-modal-title"><?php echo esc_html__('Version Changelog', 'ecf-framework'); ?></h2>
                        <p><?php echo esc_html__('Quick view of the latest documented plugin changes.', 'ecf-framework'); ?></p>
                    </div>
                    <button type="button" class="ecf-modal__close" data-ecf-close-changelog-modal aria-label="<?php echo esc_attr__('Close', 'ecf-framework'); ?>">×</button>
                </div>
                <div class="ecf-modal__body">
                    <?php if (empty($changelog_entries)): ?>
                        <p class="ecf-muted-copy"><?php echo esc_html__('No changelog entries found.', 'ecf-framework'); ?></p>
                    <?php else: ?>
                        <div class="ecf-changelog-list">
                            <?php foreach ($changelog_entries as $entry): ?>
                                <section class="ecf-changelog-entry">
                                    <h3><?php echo esc_html($entry['heading']); ?></h3>
                                    <?php foreach (($entry['sections'] ?? []) as $section_title => $items): ?>
                                        <div class="ecf-changelog-section">
                                            <strong class="ecf-changelog-badge ecf-changelog-badge--<?php echo esc_attr($this->changelog_section_badge_type($section_title)); ?>"><?php echo esc_html($section_title); ?></strong>
                                            <ul>
                                                <?php foreach ($items as $item): ?>
                                                    <li><?php echo esc_html($item); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_row_templates($starter_class_categories) {
        ?>
        <script type="text/template" id="ecf-row-template-color">
            <div class="ecf-row ecf-row--color" data-ecf-color-detail-row>
                <input type="text" class="ecf-color-field" value="#000000" placeholder="#000000" />
                <input type="hidden" class="ecf-color-value-input" name="__VALUE__" value="#000000" />
                <input type="text" data-ecf-slug-field="token" name="__NAME__" value="" placeholder="<?php echo esc_attr__('class name', 'ecf-framework'); ?>" />
                <input type="text" class="ecf-color-value-display" value="#000000" spellcheck="false" autocomplete="off" />
                <select class="ecf-color-format-select" name="__FORMAT__">
                    <option value="hex">HEX</option>
                    <option value="hexa">HEXA</option>
                    <option value="rgb">RGB</option>
                    <option value="rgba">RGBA</option>
                    <option value="hsl">HSL</option>
                    <option value="hsla">HSLA</option>
                </select>
                <input type="hidden" name="__NAME_BASE__[generate_shades]" value="0" data-ecf-color-template-hidden="generate_shades">
                <input type="hidden" name="__NAME_BASE__[generate_tints]" value="0" data-ecf-color-template-hidden="generate_tints">
                <button type="button" class="ecf-color-detail-toggle" aria-expanded="false" data-tip="<?php echo esc_attr__('ecf_color_generator_show_details', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('ecf_color_generator_show_details', 'ecf-framework'); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
                <button type="button" class="button ecf-remove-row">×</button>
                <div class="ecf-color-detail" hidden>
                    <div class="ecf-color-detail__controls">
                        <div class="ecf-color-generator-row">
                            <label class="ecf-color-generator-toggle"><input type="checkbox" name="__NAME_BASE__[generate_shades]" value="1" data-ecf-color-generate="shades" checked><span><?php echo esc_html__('ecf_color_generator_generate_shades', 'ecf-framework'); ?></span></label>
                            <div class="ecf-color-generator-count"><button type="button" data-ecf-color-count-minus="shades">−</button><input type="number" min="4" max="10" name="__NAME_BASE__[shade_count]" value="6" data-ecf-color-count="shades"><button type="button" data-ecf-color-count-plus="shades">+</button></div>
                        </div>
                        <div class="ecf-color-generator-row">
                            <label class="ecf-color-generator-toggle"><input type="checkbox" name="__NAME_BASE__[generate_tints]" value="1" data-ecf-color-generate="tints"><span><?php echo esc_html__('ecf_color_generator_generate_tints', 'ecf-framework'); ?></span></label>
                            <div class="ecf-color-generator-count"><button type="button" data-ecf-color-count-minus="tints">−</button><input type="number" min="4" max="10" name="__NAME_BASE__[tint_count]" value="6" data-ecf-color-count="tints"><button type="button" data-ecf-color-count-plus="tints">+</button></div>
                        </div>
                    </div>
                    <div class="ecf-color-detail__preview" style="--ecf-color-detail-base:#000000;"><span style="background:#000000;"></span></div>
                    <div class="ecf-color-detail__meta"><strong>--ecf-color-name</strong><code>#000000</code></div>
                    <div class="ecf-color-detail__shades"></div>
                </div>
            </div>
        </script>

        <script type="text/template" id="ecf-row-template-minmax">
            <div class="ecf-row ecf-row--minmax">
                <input type="text" data-ecf-slug-field="token" name="__NAME__" value="" placeholder="<?php echo esc_attr__('class name', 'ecf-framework'); ?>" />
                <input type="text" name="__MIN__" value="" placeholder="min" />
                <input type="text" name="__MAX__" value="" placeholder="max" />
                <button type="button" class="button ecf-remove-row">×</button>
            </div>
        </script>

        <script type="text/template" id="ecf-row-template-default">
            <div class="ecf-row">
                <input type="text" data-ecf-slug-field="token" name="__NAME__" value="" placeholder="<?php echo esc_attr__('class name', 'ecf-framework'); ?>" />
                <input type="text" name="__VALUE__" value="" placeholder="value" />
                <button type="button" class="button ecf-remove-row">×</button>
            </div>
        </script>
        <script type="text/template" id="ecf-starter-custom-row-template">
            <div class="ecf-starter-custom-row">
                <label class="ecf-form-grid__checkbox">
                    <input type="checkbox" name="__ENABLED__" value="1" class="ecf-custom-starter-enabled" checked>
                    <span><?php echo esc_html__('Active', 'ecf-framework'); ?></span>
                </label>
                <input type="text" data-ecf-slug-field="token" name="__NAME__" value="" placeholder="ecf-banner" class="ecf-custom-starter-name">
                <select name="__CATEGORY__" class="ecf-custom-starter-category">
                    <?php foreach ($starter_class_categories as $category_key => $category_label): ?>
                        <?php if ($category_key === 'all') continue; ?>
                        <option value="<?php echo esc_attr($category_key); ?>"><?php echo esc_html($category_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </script>
        <?php
    }
}
