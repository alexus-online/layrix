<?php

trait ECF_Framework_Config_Trait {
    private function defaults() {
        return [
            'root_font_size' => '62.5',
            'interface_language' => $this->wordpress_default_interface_language(),
            'admin_design_preset' => 'current',
            'admin_design_mode' => 'dark',
            'github_update_checks_enabled' => '1',
            'elementor_boxed_width' => '1140px',
            'content_max_width' => '72ch',
            'base_font_family' => 'var(--ecf-font-primary)',
            'base_body_text_size' => '16px',
            'base_text_color' => '#111827',
            'base_background_color' => '#ffffff',
            'link_color' => '#3b82f6',
            'focus_color' => '#6366f1',
            'show_elementor_status_cards' => '1',
            'elementor_variable_type_filter' => '1',
            'general_setting_favorites' => $this->default_general_setting_favorites(),
            'elementor_variable_type_filter_scopes' => [
                'color' => '1',
                'text' => '1',
                'space' => '1',
                'radius' => '1',
                'shadow' => '1',
                'string' => '1',
            ],
            'starter_classes' => [
                'enabled' => $this->default_starter_enabled_class_map(),
                'custom' => [],
                'seeded' => '1',
            ],
            'utility_classes' => [
                'enabled' => [],
            ],
            'colors' => [
                ['name' => 'primary', 'value' => '#3b82f6'],
                ['name' => 'secondary', 'value' => '#64748b'],
                ['name' => 'accent', 'value' => '#f97316'],
                ['name' => 'surface', 'value' => '#ffffff'],
                ['name' => 'text', 'value' => '#111827'],
            ],
            'radius' => [
                ['name' => 'xs', 'min' => '4px', 'max' => '4px'],
                ['name' => 's', 'min' => '6px', 'max' => '8px'],
                ['name' => 'm', 'min' => '10px', 'max' => '12px'],
                ['name' => 'l', 'min' => '16px', 'max' => '20px'],
                ['name' => 'xl', 'min' => '26px', 'max' => '32px'],
                ['name' => 'full', 'min' => '999px', 'max' => '999px'],
            ],
            'spacing' => $this->default_spacing_settings(),
            'shadows' => [
                ['name' => 'xs', 'value' => '0 1px 2px rgba(0,0,0,0.05)'],
                ['name' => 's', 'value' => '0 2px 6px rgba(0,0,0,0.08)'],
                ['name' => 'm', 'value' => '0 4px 16px rgba(0,0,0,0.10)'],
                ['name' => 'l', 'value' => '0 8px 30px rgba(0,0,0,0.12)'],
                ['name' => 'xl', 'value' => '0 20px 60px rgba(0,0,0,0.15)'],
                ['name' => 'inner', 'value' => 'inset 0 2px 6px rgba(0,0,0,0.08)'],
            ],
            'container' => [
                'sm' => '640px',
                'md' => '768px',
                'lg' => '1024px',
                'xl' => '1280px',
            ],
            'enabled_components' => [
                'layout' => '1',
                'buttons' => '1',
                'cards' => '1',
            ],
            'typography' => [
                'fonts' => [
                    ['name' => 'primary', 'value' => 'Inter, sans-serif'],
                    ['name' => 'secondary', 'value' => 'Georgia, serif'],
                    ['name' => 'mono', 'value' => 'JetBrains Mono, monospace'],
                ],
                'local_fonts' => [],
                'scale' => [
                    'min_base' => 16,
                    'max_base' => 18,
                    'min_ratio' => 1.125,
                    'max_ratio' => 1.25,
                    'steps' => ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'],
                    'base_index' => 'm',
                    'fluid' => true,
                    'min_vw' => 375,
                    'max_vw' => 1280,
                ],
                'weights' => [
                    ['name' => 'light', 'value' => '300'],
                    ['name' => 'normal', 'value' => '400'],
                    ['name' => 'medium', 'value' => '500'],
                    ['name' => 'semibold', 'value' => '600'],
                    ['name' => 'bold', 'value' => '700'],
                ],
                'leading' => [
                    ['name' => 'none', 'value' => '1'],
                    ['name' => 'tight', 'value' => '1.2'],
                    ['name' => 'snug', 'value' => '1.375'],
                    ['name' => 'normal', 'value' => '1.5'],
                    ['name' => 'relaxed', 'value' => '1.625'],
                    ['name' => 'loose', 'value' => '2'],
                ],
                'tracking' => [
                    ['name' => 'tighter', 'value' => '-0.05em'],
                    ['name' => 'tight', 'value' => '-0.025em'],
                    ['name' => 'normal', 'value' => '0em'],
                    ['name' => 'wide', 'value' => '0.025em'],
                    ['name' => 'wider', 'value' => '0.05em'],
                    ['name' => 'widest', 'value' => '0.1em'],
                ],
            ],
        ];
    }

    private function default_spacing_settings() {
        return [
            'prefix' => 'space',
            'min_base' => 16,
            'max_base' => 28,
            'min_ratio' => 1.25,
            'max_ratio' => 1.414,
            'steps' => ['3xs', '2xs', 'xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'],
            'base_index' => 'm',
            'fluid' => true,
            'min_vw' => 375,
            'max_vw' => 1280,
            'base' => 28,
            'ratio_up' => 1.414,
            'ratio_down' => 0.8,
            'scale_factor' => 0.5714,
        ];
    }

    private function legacy_default_spacing_settings() {
        return [
            'prefix' => 'space',
            'min_base' => 14,
            'max_base' => 16,
            'min_ratio' => 1.2,
            'max_ratio' => 1.25,
            'steps' => ['3xs', '2xs', 'xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl'],
            'base_index' => 'm',
            'fluid' => true,
            'min_vw' => 375,
            'max_vw' => 1280,
        ];
    }

    private function spacing_settings_match_defaults($settings, $defaults) {
        if (!is_array($settings) || !is_array($defaults)) {
            return false;
        }

        $keys = ['prefix', 'min_base', 'max_base', 'min_ratio', 'max_ratio', 'base_index', 'fluid', 'min_vw', 'max_vw'];
        foreach ($keys as $key) {
            $left = $settings[$key] ?? null;
            $right = $defaults[$key] ?? null;
            if (in_array($key, ['min_base', 'max_base', 'min_ratio', 'max_ratio'], true)) {
                if (round((float) $left, 4) !== round((float) $right, 4)) {
                    return false;
                }
                continue;
            }
            if ((string) $left !== (string) $right) {
                return false;
            }
        }

        $left_steps = array_values(array_map('strval', $settings['steps'] ?? []));
        $right_steps = array_values(array_map('strval', $defaults['steps'] ?? []));

        return $left_steps === $right_steps;
    }

    private function default_starter_enabled_classes() {
        return [
            'ecf-page',
            'ecf-page__main',
            'ecf-section',
            'ecf-section__inner',
            'ecf-container',
            'ecf-shell',
            'ecf-layout',
            'ecf-header',
            'ecf-header__inner',
            'ecf-nav',
            'ecf-hero',
            'ecf-hero__content',
            'ecf-card',
            'ecf-card__body',
            'ecf-card__title',
            'ecf-button',
            'ecf-button--primary',
            'ecf-button--secondary',
            'ecf-footer',
            'ecf-footer__inner',
        ];
    }

    private function legacy_default_starter_enabled_classes() {
        return [
            'ecf-page',
            'ecf-section',
            'ecf-section__inner',
            'ecf-container',
            'ecf-header',
            'ecf-nav',
            'ecf-hero',
            'ecf-card',
            'ecf-button',
            'ecf-footer',
        ];
    }

    private function default_starter_enabled_class_map() {
        $map = [];
        foreach ($this->default_starter_enabled_classes() as $class_name) {
            $map[$class_name] = '1';
        }
        return $map;
    }

    private function starter_enabled_map_from_list($class_names) {
        $map = [];
        foreach ((array) $class_names as $class_name) {
            $normalized = $this->normalize_starter_class_name($class_name);
            if ($normalized !== '') {
                $map[$normalized] = '1';
            }
        }
        return $map;
    }

    private function starter_enabled_maps_match($first, $second) {
        $first = array_keys(array_filter(is_array($first) ? $first : []));
        $second = array_keys(array_filter(is_array($second) ? $second : []));
        sort($first);
        sort($second);
        return $first === $second;
    }

    private function build_sync_summary_message($label_en, $label_de, $created, $updated) {
        $parts = [];

        if ((int) $created > 0) {
            $parts[] = sprintf(__('%1$d created', 'ecf-framework'), (int) $created);
        }

        if ((int) $updated > 0) {
            $parts[] = sprintf(__('%1$d updated', 'ecf-framework'), (int) $updated);
        }

        if (empty($parts)) {
            $parts[] = __('no changes', 'ecf-framework');
        }

        return sprintf(
            __('%1$s: %2$s.', 'ecf-framework'),
            __($label_en, 'ecf-framework'),
            implode(', ', $parts)
        );
    }

    public function get_settings() {
        $saved = get_option($this->option_name);
        if (!$saved || !is_array($saved)) {
            $defaults = $this->defaults();
            $defaults['base_body_text_size'] = $this->derived_base_body_text_size($defaults);
            return $defaults;
        }

        $settings = wp_parse_args($saved, $this->defaults());
        $settings['interface_language'] = in_array(($settings['interface_language'] ?? ''), ['de', 'en'], true)
            ? $settings['interface_language']
            : $this->wordpress_default_interface_language();
        if (!array_key_exists('admin_design_mode', $saved)) {
            $settings['admin_design_mode'] = 'dark';
            $saved['admin_design_mode'] = 'dark';
            update_option($this->option_name, $saved);
        }
        $default_general_favorites = $this->default_general_setting_favorites();
        $has_saved_general_favorites = array_key_exists('general_setting_favorites', $saved);
        $saved_general_favorites = is_array($saved['general_setting_favorites'] ?? null)
            ? $saved['general_setting_favorites']
            : [];
        if (!$has_saved_general_favorites) {
            $settings['general_setting_favorites'] = $default_general_favorites;
            $saved['general_setting_favorites'] = $default_general_favorites;
            update_option($this->option_name, $saved);
        } else {
            $settings['general_setting_favorites'] = $saved_general_favorites;
        }
        if (empty($settings['starter_classes']) || !is_array($settings['starter_classes'])) {
            $settings['starter_classes'] = $this->defaults()['starter_classes'];
        }
        $settings['starter_classes']['enabled'] = is_array($settings['starter_classes']['enabled'] ?? null) ? $settings['starter_classes']['enabled'] : [];
        $settings['starter_classes']['custom'] = is_array($settings['starter_classes']['custom'] ?? null) ? $settings['starter_classes']['custom'] : [];

        $needs_seed = empty($settings['starter_classes']['seeded'])
            && empty($settings['starter_classes']['enabled'])
            && empty($settings['starter_classes']['custom']);

        $needs_seed_repair = !empty($settings['starter_classes']['seeded'])
            && empty($settings['starter_classes']['enabled'])
            && empty($settings['starter_classes']['custom']);

        $needs_default_upgrade = !empty($settings['starter_classes']['seeded'])
            && empty($settings['starter_classes']['custom'])
            && $this->starter_enabled_maps_match(
                $settings['starter_classes']['enabled'],
                $this->starter_enabled_map_from_list($this->legacy_default_starter_enabled_classes())
            );

        if ($needs_seed || $needs_seed_repair) {
            $settings['starter_classes']['enabled'] = $this->default_starter_enabled_class_map();
            $settings['starter_classes']['seeded'] = '1';
            $saved['starter_classes'] = $settings['starter_classes'];
            update_option($this->option_name, $saved);
        } elseif ($needs_default_upgrade) {
            $settings['starter_classes']['enabled'] = $this->default_starter_enabled_class_map();
            $settings['starter_classes']['seeded'] = '1';
            $saved['starter_classes'] = $settings['starter_classes'];
            update_option($this->option_name, $saved);
        }

        $needs_spacing_upgrade = $this->spacing_settings_match_defaults(
            $settings['spacing'] ?? [],
            $this->legacy_default_spacing_settings()
        );

        if ($needs_spacing_upgrade) {
            $settings['spacing'] = $this->default_spacing_settings();
            $saved['spacing'] = $settings['spacing'];
            update_option($this->option_name, $saved);
        }

        $derived_base_body_text_size = $this->derived_base_body_text_size($settings);
        if ($this->should_upgrade_base_body_text_size($settings['base_body_text_size'] ?? '', $settings)) {
            $settings['base_body_text_size'] = $derived_base_body_text_size;
            $saved['base_body_text_size'] = $derived_base_body_text_size;
            update_option($this->option_name, $saved);
        }

        return $settings;
    }

    private function starter_class_library() {
        return [
            'basic' => [
                ['name' => 'ecf-page', 'category' => 'layout'],
                ['name' => 'ecf-page__main', 'category' => 'layout'],
                ['name' => 'ecf-section', 'category' => 'sections'],
                ['name' => 'ecf-section__inner', 'category' => 'sections'],
                ['name' => 'ecf-container', 'category' => 'layout'],
                ['name' => 'ecf-shell', 'category' => 'layout'],
                ['name' => 'ecf-layout', 'category' => 'layout'],
                ['name' => 'ecf-header', 'category' => 'navigation'],
                ['name' => 'ecf-header__inner', 'category' => 'navigation'],
                ['name' => 'ecf-nav', 'category' => 'navigation'],
                ['name' => 'ecf-hero', 'category' => 'hero'],
                ['name' => 'ecf-hero__content', 'category' => 'hero'],
                ['name' => 'ecf-card', 'category' => 'cards'],
                ['name' => 'ecf-card__body', 'category' => 'cards'],
                ['name' => 'ecf-card__title', 'category' => 'cards'],
                ['name' => 'ecf-button', 'category' => 'buttons'],
                ['name' => 'ecf-button--primary', 'category' => 'buttons'],
                ['name' => 'ecf-button--secondary', 'category' => 'buttons'],
                ['name' => 'ecf-footer', 'category' => 'footer'],
                ['name' => 'ecf-footer__inner', 'category' => 'footer'],
            ],
            'advanced' => [
                ['name' => 'ecf-header__brand', 'category' => 'navigation'],
                ['name' => 'ecf-header__actions', 'category' => 'navigation'],
                ['name' => 'ecf-nav__list', 'category' => 'navigation'],
                ['name' => 'ecf-nav__item', 'category' => 'navigation'],
                ['name' => 'ecf-nav__link', 'category' => 'navigation'],
                ['name' => 'ecf-nav__toggle', 'category' => 'navigation'],
                ['name' => 'ecf-hero__eyebrow', 'category' => 'hero'],
                ['name' => 'ecf-hero__media', 'category' => 'hero'],
                ['name' => 'ecf-hero__actions', 'category' => 'hero'],
                ['name' => 'ecf-card__media', 'category' => 'cards'],
                ['name' => 'ecf-card__meta', 'category' => 'cards'],
                ['name' => 'ecf-card__actions', 'category' => 'cards'],
                ['name' => 'ecf-button--ghost', 'category' => 'buttons'],
                ['name' => 'ecf-button--link', 'category' => 'buttons'],
                ['name' => 'ecf-button--large', 'category' => 'buttons'],
                ['name' => 'ecf-button__icon', 'category' => 'buttons'],
                ['name' => 'ecf-section--dark', 'category' => 'sections'],
                ['name' => 'ecf-section--accent', 'category' => 'sections'],
                ['name' => 'ecf-grid--2', 'category' => 'layout'],
                ['name' => 'ecf-grid--3', 'category' => 'layout'],
                ['name' => 'ecf-grid--4', 'category' => 'layout'],
                ['name' => 'ecf-stack--xs', 'category' => 'layout'],
                ['name' => 'ecf-stack--s', 'category' => 'layout'],
                ['name' => 'ecf-stack--m', 'category' => 'layout'],
                ['name' => 'ecf-stack--l', 'category' => 'layout'],
                ['name' => 'ecf-stack--xl', 'category' => 'layout'],
                ['name' => 'ecf-sidebar', 'category' => 'content'],
                ['name' => 'ecf-sidebar__inner', 'category' => 'content'],
                ['name' => 'ecf-content', 'category' => 'content'],
                ['name' => 'ecf-content__body', 'category' => 'content'],
                ['name' => 'ecf-badge', 'category' => 'content'],
                ['name' => 'ecf-badge--primary', 'category' => 'content'],
                ['name' => 'ecf-badge--secondary', 'category' => 'content'],
                ['name' => 'ecf-form', 'category' => 'forms'],
                ['name' => 'ecf-form__group', 'category' => 'forms'],
                ['name' => 'ecf-form__actions', 'category' => 'forms'],
                ['name' => 'ecf-modal', 'category' => 'dialogs'],
                ['name' => 'ecf-modal__content', 'category' => 'dialogs'],
                ['name' => 'ecf-modal__actions', 'category' => 'dialogs'],
            ],
        ];
    }

    private function utility_class_library() {
        return [
            'typography' => [
                ['name' => 'ecf-heading-1', 'label' => __('Heading 1', 'ecf-framework')],
                ['name' => 'ecf-heading-2', 'label' => __('Heading 2', 'ecf-framework')],
                ['name' => 'ecf-heading-3', 'label' => __('Heading 3', 'ecf-framework')],
                ['name' => 'ecf-heading-4', 'label' => __('Heading 4', 'ecf-framework')],
                ['name' => 'ecf-heading-5', 'label' => __('Heading 5', 'ecf-framework')],
                ['name' => 'ecf-body-l', 'label' => __('Body Large', 'ecf-framework')],
                ['name' => 'ecf-body-m', 'label' => __('Body Medium', 'ecf-framework')],
                ['name' => 'ecf-body-s', 'label' => __('Body Small', 'ecf-framework')],
                ['name' => 'ecf-caption', 'label' => __('Caption', 'ecf-framework')],
                ['name' => 'ecf-overline', 'label' => __('Overline', 'ecf-framework')],
            ],
            'text' => [
                ['name' => 'ecf-text-left', 'label' => __('Text Left', 'ecf-framework')],
                ['name' => 'ecf-text-center', 'label' => __('Text Center', 'ecf-framework')],
                ['name' => 'ecf-text-right', 'label' => __('Text Right', 'ecf-framework')],
                ['name' => 'ecf-text-balance', 'label' => __('Text Balance', 'ecf-framework')],
                ['name' => 'ecf-text-pretty', 'label' => __('Text Pretty', 'ecf-framework')],
            ],
            'layout' => [
                ['name' => 'ecf-inline', 'label' => __('Inline Flex', 'ecf-framework')],
                ['name' => 'ecf-inline-block', 'label' => __('Inline Block', 'ecf-framework')],
                ['name' => 'ecf-hidden', 'label' => __('Hidden', 'ecf-framework')],
                ['name' => 'ecf-center-inline', 'label' => __('Center Inline', 'ecf-framework')],
                ['name' => 'ecf-cluster', 'label' => __('Cluster', 'ecf-framework')],
            ],
            'accessibility' => [
                ['name' => 'ecf-visually-hidden', 'label' => __('Visually Hidden', 'ecf-framework')],
            ],
        ];
    }

    private function utility_class_category_labels() {
        return [
            'all' => __('All utilities', 'ecf-framework'),
            'typography' => __('Typography', 'ecf-framework'),
            'text' => __('Text', 'ecf-framework'),
            'layout' => __('Layout', 'ecf-framework'),
            'accessibility' => __('Accessibility', 'ecf-framework'),
        ];
    }

    private function starter_class_tooltip($class_name, $category, $tier) {
        $name = (string) $class_name;
        $category = (string) $category;
        $tier = (string) $tier;

        if (str_contains($name, '__inner')) return __('Inner wrapper for the parent section or component.', 'ecf-framework');
        if (str_contains($name, '__main')) return __('Main content area of the page layout.', 'ecf-framework');
        if (str_contains($name, '__content')) return __('Content area inside this component.', 'ecf-framework');
        if (str_contains($name, '__body')) return __('Main body area for text and content.', 'ecf-framework');
        if (str_contains($name, '__title')) return __('Title element inside this component.', 'ecf-framework');
        if (str_contains($name, '__actions')) return __('Action area for buttons or controls.', 'ecf-framework');
        if (str_contains($name, '__media')) return __('Media slot for image, video or illustration.', 'ecf-framework');
        if (str_contains($name, '__meta')) return __('Meta information such as date, category or label.', 'ecf-framework');
        if (str_contains($name, '__brand')) return __('Brand or logo area inside the header.', 'ecf-framework');
        if (str_contains($name, '__list')) return __('List container for repeated navigation or content items.', 'ecf-framework');
        if (str_contains($name, '__item')) return __('Single item inside a repeated list or navigation.', 'ecf-framework');
        if (str_contains($name, '__link')) return __('Link element inside navigation or content lists.', 'ecf-framework');
        if (str_contains($name, '__toggle')) return __('Toggle control, for example for mobile navigation.', 'ecf-framework');
        if (str_contains($name, '__group')) return __('Groups related fields or controls together.', 'ecf-framework');
        if (str_contains($name, '__icon')) return __('Icon element inside the parent component.', 'ecf-framework');

        if (str_contains($name, '--primary')) return __('Primary variant for the main emphasis action or element.', 'ecf-framework');
        if (str_contains($name, '--secondary')) return __('Secondary variant for quieter supporting actions or elements.', 'ecf-framework');
        if (str_contains($name, '--ghost')) return __('Ghost-style variant with minimal visual weight.', 'ecf-framework');
        if (str_contains($name, '--link')) return __('Button variant styled more like a text link.', 'ecf-framework');
        if (str_contains($name, '--large')) return __('Larger size variant for stronger emphasis.', 'ecf-framework');
        if (str_contains($name, '--dark')) return __('Dark variant for sections with a darker surface.', 'ecf-framework');
        if (str_contains($name, '--accent')) return __('Accent variant for highlighted sections or content blocks.', 'ecf-framework');

        switch ($category) {
            case 'layout':
                return $tier === 'advanced'
                    ? __('Advanced layout helper for page structure and composition.', 'ecf-framework')
                    : __('Base layout class for the overall page structure.', 'ecf-framework');
            case 'sections':
                return __('Section wrapper for grouping a complete content block.', 'ecf-framework');
            case 'navigation':
                return __('Navigation-related class for header or menu structure.', 'ecf-framework');
            case 'hero':
                return __('Hero section class for the main entry area of a page.', 'ecf-framework');
            case 'cards':
                return __('Card component class for grouped content inside a surface.', 'ecf-framework');
            case 'buttons':
                return __('Button component class for interactive call-to-actions.', 'ecf-framework');
            case 'footer':
                return __('Footer class for the bottom area of the website.', 'ecf-framework');
            case 'content':
                return __('Content class for inner page content or supportive blocks.', 'ecf-framework');
            case 'forms':
                return __('Form class for form layout, groups or actions.', 'ecf-framework');
            case 'dialogs':
                return __('Dialog or modal class for overlays and focused interactions.', 'ecf-framework');
            default:
                return __('Semantic starter class for consistent naming and structure.', 'ecf-framework');
        }
    }

    private function utility_class_tooltip($class_name, $category) {
        switch ((string) $class_name) {
            case 'ecf-heading-1': return __('Applies the strongest heading style for major titles.', 'ecf-framework');
            case 'ecf-heading-2': return __('Applies a strong heading style for section titles.', 'ecf-framework');
            case 'ecf-heading-3': return __('Applies a medium heading style for smaller sections.', 'ecf-framework');
            case 'ecf-heading-4': return __('Applies a compact heading style for subsections.', 'ecf-framework');
            case 'ecf-heading-5': return __('Applies the smallest heading style in the utility set.', 'ecf-framework');
            case 'ecf-body-l': return __('Large body text style for intros or highlighted copy.', 'ecf-framework');
            case 'ecf-body-m': return __('Default body text style for regular paragraphs.', 'ecf-framework');
            case 'ecf-body-s': return __('Smaller body text style for secondary information.', 'ecf-framework');
            case 'ecf-caption': return __('Small caption style for image notes or meta text.', 'ecf-framework');
            case 'ecf-overline': return __('Small uppercase overline style for labels above headings.', 'ecf-framework');
            case 'ecf-text-left': return __('Aligns text content to the left.', 'ecf-framework');
            case 'ecf-text-center': return __('Centers text content horizontally.', 'ecf-framework');
            case 'ecf-text-right': return __('Aligns text content to the right.', 'ecf-framework');
            case 'ecf-text-balance': return __('Balances text wrapping for more even headline lines.', 'ecf-framework');
            case 'ecf-text-pretty': return __('Improves text wrapping for more pleasant paragraph breaks.', 'ecf-framework');
            case 'ecf-inline': return __('Displays children inline with flexible spacing.', 'ecf-framework');
            case 'ecf-inline-block': return __('Makes an element behave like an inline block.', 'ecf-framework');
            case 'ecf-hidden': return __('Hides the element visually.', 'ecf-framework');
            case 'ecf-center-inline': return __('Centers inline content inside its available width.', 'ecf-framework');
            case 'ecf-cluster': return __('Groups items in a horizontal cluster with wrapping.', 'ecf-framework');
            case 'ecf-visually-hidden': return __('Keeps content accessible for screen readers while hiding it visually.', 'ecf-framework');
        }

        switch ((string) $category) {
            case 'typography':
                return __('Typography helper class for reusable text styles.', 'ecf-framework');
            case 'text':
                return __('Text utility for alignment or text-flow behavior.', 'ecf-framework');
            case 'layout':
                return __('Layout helper for simple spacing or positioning patterns.', 'ecf-framework');
            case 'accessibility':
                return __('Accessibility helper for screen-reader-friendly behavior.', 'ecf-framework');
            default:
                return __('Reusable utility class for common styling helpers.', 'ecf-framework');
        }
    }

    private function class_library_tab_help_texts() {
        return [
            'starter' => __('Starter classes give you a semantic naming system for common page areas and components such as headers, heroes, cards, buttons, and footers.', 'ecf-framework'),
            'utility' => __('Utility classes are a small curated helper set for text styles, alignment, and a few safe layout patterns. Use them sparingly because they also count toward Elementor’s class limit.', 'ecf-framework'),
        ];
    }

    private function starter_class_tab_groups() {
        return [
            'all' => [
                'label' => __('All classes', 'ecf-framework'),
                'categories' => ['layout', 'sections', 'navigation', 'hero', 'cards', 'buttons', 'footer', 'content', 'forms', 'dialogs', 'custom'],
            ],
            'website_sections' => [
                'label' => __('Website sections', 'ecf-framework'),
                'categories' => ['navigation', 'hero', 'footer'],
            ],
            'layout_content' => [
                'label' => __('Layout & content', 'ecf-framework'),
                'categories' => ['layout', 'sections', 'content', 'cards'],
            ],
            'interaction' => [
                'label' => __('Interaction', 'ecf-framework'),
                'categories' => ['buttons', 'forms', 'dialogs'],
            ],
            'custom' => [
                'label' => __('Custom', 'ecf-framework'),
                'categories' => ['custom'],
            ],
        ];
    }

    private function starter_class_tab_for_category($category) {
        foreach ($this->starter_class_tab_groups() as $tab_key => $tab) {
            if ($tab_key === 'all') {
                continue;
            }
            if (in_array($category, $tab['categories'], true)) {
                return $tab_key;
            }
        }
        return 'all';
    }

    private function starter_class_tab_help_texts() {
        return [
            'all' => __('Shows the complete starter library so you can enable a full naming system at once.', 'ecf-framework'),
            'website_sections' => __('Semantic classes for the big page sections such as header, navigation, hero, and footer.', 'ecf-framework'),
            'layout_content' => __('Structural and content-oriented classes for layout shells, sections, cards, and general content areas.', 'ecf-framework'),
            'interaction' => __('Interactive classes for buttons, forms, and dialog-based UI elements.', 'ecf-framework'),
            'custom' => __('Your own semantic class names. Use this area for project-specific naming that does not fit the predefined library.', 'ecf-framework'),
        ];
    }

    private function utility_class_category_help_texts() {
        return [
            'all' => __('Shows the complete utility set with all available text, alignment, layout, and accessibility helpers.', 'ecf-framework'),
            'typography' => __('Typography helpers for heading and body text styles that reuse your ECF text tokens.', 'ecf-framework'),
            'text' => __('Text helpers for alignment and text behavior such as balanced or pretty wrapping.', 'ecf-framework'),
            'layout' => __('Small layout helpers for inline, cluster, centering, and visibility behavior.', 'ecf-framework'),
            'accessibility' => __('Accessibility helpers such as visually hidden content for screen-reader-only text.', 'ecf-framework'),
        ];
    }

    private function typography_row_value($group, $name, $fallback = '') {
        $settings = $this->get_settings();
        foreach ((array) ($settings['typography'][$group] ?? []) as $row) {
            if (sanitize_key($row['name'] ?? '') === sanitize_key($name)) {
                $value = (string) ($row['value'] ?? '');
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return $fallback;
    }

    private function utility_type_size_prop($step) {
        $settings = $this->get_settings();
        $root_base_px = $this->get_root_font_base_px($settings);
        foreach ($this->build_type_scale_preview($settings['typography']['scale'], $root_base_px) as $item) {
            if (($item['step'] ?? '') === $step) {
                $preferred = trim((string) ($item['max'] ?? '')) . 'rem';
                $prop = $this->size_prop($preferred);
                if ($prop !== null) {
                    return $prop;
                }
                return $this->size_prop(trim((string) ($item['max_px'] ?? '')) . 'px');
            }
        }
        return null;
    }

    private function utility_class_props($name) {
        $leading_tight = $this->typography_row_value('leading', 'tight', '1.2');
        $leading_snug = $this->typography_row_value('leading', 'snug', '1.375');
        $leading_normal = $this->typography_row_value('leading', 'normal', '1.5');
        $leading_relaxed = $this->typography_row_value('leading', 'relaxed', '1.625');
        $weight_bold = $this->typography_row_value('weights', 'bold', '700');
        $weight_semibold = $this->typography_row_value('weights', 'semibold', '600');
        $tracking_widest = $this->typography_row_value('tracking', 'widest', '0.1em');

        $map = [
            'ecf-heading-1' => [
                'font-size' => $this->utility_type_size_prop('4xl') ?? $this->string_prop('var(--ecf-text-4xl)'),
                'line-height' => $this->string_prop($leading_tight),
                'font-weight' => $this->string_prop($weight_bold),
            ],
            'ecf-heading-2' => [
                'font-size' => $this->utility_type_size_prop('3xl') ?? $this->string_prop('var(--ecf-text-3xl)'),
                'line-height' => $this->string_prop($leading_tight),
                'font-weight' => $this->string_prop($weight_bold),
            ],
            'ecf-heading-3' => [
                'font-size' => $this->utility_type_size_prop('2xl') ?? $this->string_prop('var(--ecf-text-2xl)'),
                'line-height' => $this->string_prop($leading_snug),
                'font-weight' => $this->string_prop($weight_semibold),
            ],
            'ecf-heading-4' => [
                'font-size' => $this->utility_type_size_prop('xl') ?? $this->string_prop('var(--ecf-text-xl)'),
                'line-height' => $this->string_prop($leading_snug),
                'font-weight' => $this->string_prop($weight_semibold),
            ],
            'ecf-heading-5' => [
                'font-size' => $this->utility_type_size_prop('l') ?? $this->string_prop('var(--ecf-text-l)'),
                'line-height' => $this->string_prop($leading_normal),
                'font-weight' => $this->string_prop($weight_semibold),
            ],
            'ecf-body-l' => [
                'font-size' => $this->utility_type_size_prop('l') ?? $this->string_prop('var(--ecf-text-l)'),
                'line-height' => $this->string_prop($leading_relaxed),
            ],
            'ecf-body-m' => [
                'font-size' => $this->utility_type_size_prop('m') ?? $this->string_prop('var(--ecf-text-m)'),
                'line-height' => $this->string_prop($leading_normal),
            ],
            'ecf-body-s' => [
                'font-size' => $this->utility_type_size_prop('s') ?? $this->string_prop('var(--ecf-text-s)'),
                'line-height' => $this->string_prop($leading_normal),
            ],
            'ecf-caption' => [
                'font-size' => $this->utility_type_size_prop('xs') ?? $this->string_prop('var(--ecf-text-xs)'),
                'line-height' => $this->string_prop($leading_snug),
            ],
            'ecf-overline' => [
                'font-size' => $this->utility_type_size_prop('xs') ?? $this->string_prop('var(--ecf-text-xs)'),
                'line-height' => $this->string_prop($leading_snug),
                'font-weight' => $this->string_prop($weight_semibold),
                'letter-spacing' => $this->string_prop($tracking_widest),
                'text-transform' => $this->string_prop('uppercase'),
            ],
            'ecf-text-left' => ['text-align' => $this->string_prop('left')],
            'ecf-text-center' => ['text-align' => $this->string_prop('center')],
            'ecf-text-right' => ['text-align' => $this->string_prop('right')],
            'ecf-text-balance' => ['text-wrap' => $this->string_prop('balance')],
            'ecf-text-pretty' => ['text-wrap' => $this->string_prop('pretty')],
            'ecf-inline' => [
                'display' => $this->string_prop('inline-flex'),
                'align-items' => $this->string_prop('center'),
            ],
            'ecf-inline-block' => ['display' => $this->string_prop('inline-block')],
            'ecf-hidden' => ['display' => $this->string_prop('none')],
            'ecf-center-inline' => [
                'margin-left' => $this->string_prop('auto'),
                'margin-right' => $this->string_prop('auto'),
            ],
            'ecf-cluster' => [
                'display' => $this->string_prop('flex'),
                'flex-wrap' => $this->string_prop('wrap'),
                'gap' => $this->string_prop('var(--ecf-space-s)'),
                'align-items' => $this->string_prop('center'),
            ],
            'ecf-visually-hidden' => [
                'position' => $this->string_prop('absolute'),
                'width' => $this->string_prop('1px'),
                'height' => $this->string_prop('1px'),
                'padding' => $this->string_prop('0'),
                'margin' => $this->string_prop('-1px'),
                'overflow' => $this->string_prop('hidden'),
                'clip' => $this->string_prop('rect(0, 0, 0, 0)'),
                'white-space' => $this->string_prop('nowrap'),
                'border' => $this->string_prop('0'),
            ],
        ];

        return $map[$name] ?? [];
    }

    private function get_selected_utility_class_names($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $selected = [];
        foreach (array_keys(array_filter((array) ($settings['utility_classes']['enabled'] ?? []))) as $name) {
            $normalized = $this->normalize_starter_class_name($name);
            if ($normalized !== '') {
                $selected[] = $normalized;
            }
        }
        return array_values(array_unique($selected));
    }

    private function starter_class_category_labels() {
        return [
            'all' => __('All classes', 'ecf-framework'),
            'layout' => __('Layout', 'ecf-framework'),
            'sections' => __('Sections', 'ecf-framework'),
            'navigation' => __('Navigation', 'ecf-framework'),
            'hero' => __('Hero', 'ecf-framework'),
            'cards' => __('Cards', 'ecf-framework'),
            'buttons' => __('Buttons', 'ecf-framework'),
            'footer' => __('Footer', 'ecf-framework'),
            'content' => __('Content', 'ecf-framework'),
            'forms' => __('Forms', 'ecf-framework'),
            'dialogs' => __('Dialogs', 'ecf-framework'),
            'custom' => __('Custom', 'ecf-framework'),
        ];
    }

    private function normalize_starter_class_name($name) {
        $name = sanitize_key($name);
        if ($name === '') {
            return '';
        }
        if (strpos($name, 'ecf-') !== 0) {
            $name = 'ecf-' . ltrim($name, '-');
        }
        return $name;
    }

    private function synced_class_labels_option_name() {
        return $this->option_name . '_synced_class_labels';
    }

    private function get_starter_class_category_map($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $map = [];
        foreach ($this->starter_class_library() as $tier => $items) {
            foreach ($items as $item) {
                $map[$item['name']] = $item['category'];
            }
        }
        foreach (($settings['starter_classes']['custom'] ?? []) as $row) {
            $name = $this->normalize_starter_class_name($row['name'] ?? '');
            $category = sanitize_key($row['category'] ?? 'custom');
            if ($name !== '') {
                $map[$name] = $category ?: 'custom';
            }
        }
        return $map;
    }

    private function get_utility_class_category_map() {
        $map = [];
        foreach ($this->utility_class_library() as $category => $items) {
            foreach ($items as $item) {
                $map[$item['name']] = $category;
            }
        }
        return $map;
    }

    private function get_selected_starter_class_names($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $selected = [];
        $enabled = $settings['starter_classes']['enabled'] ?? [];
        foreach (array_keys(array_filter((array) $enabled)) as $name) {
            $normalized = $this->normalize_starter_class_name($name);
            if ($normalized !== '') {
                $selected[] = $normalized;
            }
        }
        foreach (($settings['starter_classes']['custom'] ?? []) as $row) {
            if (empty($row['enabled'])) {
                continue;
            }
            $normalized = $this->normalize_starter_class_name($row['name'] ?? '');
            if ($normalized !== '') {
                $selected[] = $normalized;
            }
        }
        return array_values(array_unique($selected));
    }
}
