<?php

trait ECF_Framework_Admin_Page_Sections_Trait {
    private function help_getting_started_items() {
        return [
            [
                'title' => __('Set the site basics', 'ecf-framework'),
                'description' => __('Open General Settings and define root size, body text size, body font, heading font and the base colors of the site.', 'ecf-framework'),
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
                'title' => __('What do General Settings do?', 'ecf-framework'),
                'description' => __('General Settings control global basics like root font size, plugin language, container widths, base colors, body font, and editor helper behavior.', 'ecf-framework'),
            ],
        ];
    }

    private function render_variables_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="variables">
            <?php if ($show_elementor_status_cards): ?>
                <div class="ecf-card ecf-class-limit-card ecf-class-limit-card--<?php echo esc_attr($elementor_variable_limit_status); ?>">
                    <div class="ecf-class-limit-card__eyebrow"><?php echo esc_html__('Elementor Variables', 'ecf-framework'); ?></div>
                    <div class="ecf-class-limit-card__hero">
                        <div class="ecf-class-limit-card__headline">
                            <span class="ecf-class-limit-card__usage">
                                <span id="ecf-total-variables"><?php echo esc_html((string) $native_variable_counts['total']); ?></span>
                                <span class="ecf-class-limit-card__slash">/</span>
                                <span id="ecf-limit-variables"><?php echo esc_html((string) $elementor_variable_limit); ?></span>
                            </span>
                            <span><?php echo esc_html__('variables currently found in Elementor', 'ecf-framework'); ?></span>
                        </div>
                        <div class="ecf-class-limit-card__percent">
                            <strong><?php echo esc_html((string) round((($native_variable_counts['total'] ?? 0) / max(1, $elementor_variable_limit)) * 100)); ?>%</strong>
                            <span><?php echo esc_html__('of current Elementor limit', 'ecf-framework'); ?></span>
                        </div>
                    </div>
                    <ul class="ecf-class-limit-card__details ecf-class-limit-card__details--variables">
                        <li>
                            <span><?php echo esc_html__('ECF', 'ecf-framework'); ?></span>
                            <strong><span id="ecf-total-ecf-variables"><?php echo esc_html((string) $native_variable_counts['ecf']); ?></span></strong>
                        </li>
                        <li>
                            <span><?php echo esc_html__('Foreign', 'ecf-framework'); ?></span>
                            <strong><span id="ecf-total-foreign-variables"><?php echo esc_html((string) $native_variable_counts['foreign']); ?></span></strong>
                        </li>
                        <li>
                            <span><?php echo esc_html__('Total', 'ecf-framework'); ?></span>
                            <strong><span id="ecf-total-variables-inline"><?php echo esc_html((string) $native_variable_counts['total']); ?></span></strong>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="ecf-card ecf-global-search-card">
                <div class="ecf-global-search">
                    <label class="ecf-global-search__field">
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input type="search" id="ecf-global-search-input" placeholder="<?php echo esc_attr__('Search variables…', 'ecf-framework'); ?>" autocomplete="off">
                    </label>
                    <div class="ecf-global-search__results" id="ecf-global-search-results" hidden></div>
                </div>
            </div>
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
            <div class="ecf-grid" data-ecf-layout-group="variables-main" data-ecf-masonry-layout="1">
                <div class="ecf-card" id="ecf-vars-ecf" data-ecf-layout-item="ecf-vars-ecf">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html__('Layrix Variablen', 'ecf-framework'); ?> <span class="ecf-badge" id="ecf-badge-ecf">–</span></h2>
                        <div class="ecf-vargroup-tools">
                            <div class="ecf-vargroup-actions">
                                <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="ecf">
                                    <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                    <span><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                                </button>
                                <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="ecf" aria-label="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>" title="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="ecf-varlist-ecf" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html__('Loading…', 'ecf-framework'); ?></p></div>
                </div>
                <div class="ecf-card" id="ecf-vars-foreign" data-ecf-layout-item="ecf-vars-foreign">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html__('Foreign Variables', 'ecf-framework'); ?> <span class="ecf-badge" id="ecf-badge-foreign">–</span></h2>
                        <div class="ecf-vargroup-tools">
                            <div class="ecf-vargroup-actions">
                                <button type="button" class="ecf-btn ecf-btn--ghost ecf-btn--sm ecf-select-all" data-group="foreign">
                                    <span class="ecf-select-all__icon" aria-hidden="true"></span>
                                    <span><?php echo esc_html__('Select all', 'ecf-framework'); ?></span>
                                </button>
                                <button type="button" class="ecf-btn ecf-btn--danger ecf-btn--sm ecf-delete-selected" data-group="foreign" aria-label="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>" title="<?php echo esc_attr__('Delete selected', 'ecf-framework'); ?>">
                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="ecf-varlist-foreign" class="ecf-varlist"><p class="ecf-loading"><?php echo esc_html__('Loading…', 'ecf-framework'); ?></p></div>
                </div>
            </div>
            <p style="color:#6b7280;font-size:12px;margin-top:12px;"><?php echo esc_html__('Changes take effect immediately in Elementor. The cache is cleared automatically; open Elementor tabs should be reloaded once.', 'ecf-framework'); ?></p>
        </div>
        <?php
    }

    private function render_tokens_panel($settings) {
        ?>
        <div class="ecf-panel" data-panel="tokens">
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
        <?php
    }

    private function render_shadows_panel($settings) {
        ?>
        <div class="ecf-panel" data-panel="shadows">
            <div class="ecf-shadow-layout" data-ecf-layout-group="shadows-main">
                <div class="ecf-shadow-sidebar" data-ecf-layout-item="shadows-vars">
                    <div class="ecf-card">
        <h2><?php echo esc_html__('Box Shadow - Vars', 'ecf-framework'); ?></h2>
        <p style="color:#6b7280;font-size:13px;margin:0 0 12px"><?php echo wp_kses(__('Values in CSS box-shadow syntax, e.g. <code>0 4px 16px rgba(0,0,0,0.1)</code>.', 'ecf-framework'), ['code' => []]); ?></p>
                        <?php $this->render_rows('shadows', $settings['shadows']); ?>
                    </div>
                </div>
                <div class="ecf-card ecf-shadow-preview-card"
                     data-ecf-layout-item="shadows-preview"
                     data-ecf-shadow-preview
                     data-active-shadow="<?php echo esc_attr(sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?>"
            data-preview-word="<?php echo esc_attr__('Shadow', 'ecf-framework'); ?>"
            data-preview-helper="<?php echo esc_attr__('Click a shadow token to inspect it in detail.', 'ecf-framework'); ?>">
                    <div class="ecf-shadow-preview-header">
                        <div>
          <h2><?php echo esc_html__('Live Box Shadow Preview', 'ecf-framework'); ?></h2>
          <p><?php echo esc_html__('Preview of your shadow tokens.', 'ecf-framework'); ?></p>
                        </div>
                    </div>
                    <div class="ecf-shadow-focus" data-ecf-shadow-focus>
                        <div class="ecf-shadow-focus__meta">
            <span class="ecf-preview-pill"><?php echo esc_html__('Preview', 'ecf-framework'); ?></span>
                            <strong data-ecf-shadow-token><?php echo esc_html('--ecf-shadow-' . sanitize_key($settings['shadows'][0]['name'] ?? 'xs')); ?></strong>
            <p data-ecf-shadow-helper><?php echo esc_html__('Click a shadow token to inspect it in detail.', 'ecf-framework'); ?></p>
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
                            <button type="button" class="ecf-shadow-row<?php echo $index === 0 ? ' is-active' : ''; ?>" data-ecf-shadow-step="<?php echo esc_attr($shadow_name); ?>">
                                <div class="ecf-shadow-row__token"><?php echo esc_html('--ecf-shadow-' . $shadow_name); ?></div>
                                <div class="ecf-shadow-row__value"><code><?php echo esc_html($row['value']); ?></code></div>
                                <div class="ecf-shadow-row__sample ecf-shadow-preview-bg">
                                    <div class="ecf-shadow-row__mini" style="box-shadow:<?php echo esc_attr($row['value']); ?>;"></div>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_utilities_panel($args) {
        extract($args, EXTR_SKIP);
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
                <div class="ecf-card ecf-starter-classes"
                     data-ecf-layout-item="utilities-library"
                     data-ecf-starter-classes
                    data-ecf-class-current="<?php echo esc_attr((string) $elementor_total_class_count); ?>"
                     data-ecf-class-limit="<?php echo esc_attr((string) $elementor_class_limit); ?>"
                     data-ecf-existing-labels="<?php echo esc_attr(wp_json_encode($elementor_existing_class_labels)); ?>">
                    <div class="ecf-vargroup-header">
                        <h2><?php echo esc_html__('Class library', 'ecf-framework'); ?></h2>
                    </div>
                    <p class="ecf-muted-copy"><?php echo esc_html__('Use starter classes for semantic naming and utility classes for a compact curated helper set. Both count toward Elementor’s 100-class limit.', 'ecf-framework'); ?></p>
                    <div class="ecf-class-limit-card ecf-class-limit-card--<?php echo esc_attr($elementor_class_limit_status); ?> ecf-starter-classes__status" data-ecf-starter-status>
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
                            <div class="ecf-class-limit-card__percent">
                                <strong data-ecf-starter-percent><?php echo esc_html((string) $class_usage_percent); ?></strong>
                                <span><?php echo esc_html__('% of limit', 'ecf-framework'); ?></span>
                            </div>
                        </div>
                        <div class="ecf-class-limit-card__progress" aria-hidden="true">
                            <span data-ecf-starter-progress style="width:<?php echo esc_attr((string) $class_usage_percent); ?>%"></span>
                        </div>
                        <ul class="ecf-class-limit-card__details">
                            <li>
                                <span><?php echo esc_html__('Elementor', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-starter-current><?php echo esc_html((string) $elementor_total_class_count); ?></span> <?php echo esc_html__('classes', 'ecf-framework'); ?></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html__('Plugin', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-starter-selected>0</span> <?php echo esc_html__('classes', 'ecf-framework'); ?></strong>
                            </li>
                            <li>
                                <span><?php echo esc_html__('After sync:', 'ecf-framework'); ?></span>
                                <strong><span data-ecf-starter-projected-inline><?php echo esc_html((string) $elementor_total_class_count); ?></span> / <?php echo esc_html((string) $elementor_class_limit); ?></strong>
                            </li>
                        </ul>
                    </div>
                    <div class="ecf-var-tabs ecf-class-tier-tabs" data-ecf-class-tier-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-class-tier="all">
                            <?php echo esc_html__('All', 'ecf-framework'); ?>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="basic">
                            <?php echo esc_html__('Basic', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-basic-count>0</span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="extras">
                            <?php echo esc_html__('Extras', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-extras-count>0</span>
                        </button>
                        <button type="button" class="ecf-var-tab" data-ecf-class-tier="custom">
                            <?php echo esc_html__('Custom', 'ecf-framework'); ?>
                            <span class="ecf-var-tab__count" data-ecf-starter-custom-count>0</span>
                        </button>
                    </div>
                    <div class="ecf-var-tabs ecf-library-tabs" data-ecf-library-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-library-tab="starter" data-ecf-help="<?php echo esc_attr($class_library_help_texts['starter']); ?>" title="<?php echo esc_attr($starter_library_tooltip); ?>"><?php echo esc_html__('Advanced classes', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-library-tab="utility" data-ecf-help="<?php echo esc_attr($class_library_help_texts['utility']); ?>" title="<?php echo esc_attr($utility_library_tooltip); ?>"><?php echo esc_html__('Utility classes', 'ecf-framework'); ?></button>
                    </div>
                    <p class="ecf-tab-help" data-ecf-library-help><?php echo esc_html($class_library_help_texts['starter']); ?></p>
                    <?php wp_nonce_field('ecf_class_library_sync', '_ecf_class_library_sync_nonce'); ?>
                    <div class="ecf-library-section" data-ecf-library-section="starter">
                    <div class="ecf-class-filterbar" data-ecf-starter-filterbar>
                        <label class="ecf-class-filterbar__field">
                            <span class="ecf-class-filterbar__label"><?php echo esc_html__('Area', 'ecf-framework'); ?></span>
                            <select data-ecf-starter-select title="<?php echo esc_attr__('Filter the starter classes by area.', 'ecf-framework'); ?>">
                                <?php foreach ($starter_class_tabs as $tab_key => $tab): ?>
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
                                       title="<?php echo esc_attr($this->starter_class_tooltip($class_name, $class['category'], $tier)); ?>">
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
                            <button type="button" class="ecf-step-btn" data-ecf-starter-custom-add title="<?php echo esc_attr__('Add', 'ecf-framework'); ?>">+</button>
                            <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-starter-custom-remove title="<?php echo esc_attr__('Remove last', 'ecf-framework'); ?>">−</button>
                        </div>
                    </div>
                    </div>
                    <div class="ecf-library-section" data-ecf-library-section="utility" hidden>
                        <div class="ecf-vargroup-header">
                            <h3><?php echo esc_html__('Curated utility set', 'ecf-framework'); ?></h3>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html__('Small optional helpers for text styles, alignment, and a few safe layout utilities. They are intentionally limited so the class system stays manageable.', 'ecf-framework'); ?></p>
                        <div class="ecf-var-tabs ecf-starter-class-tabs" data-ecf-utility-tabs>
                            <?php foreach ($utility_class_categories as $category_key => $category_label): ?>
                                <button type="button" class="ecf-var-tab<?php echo $category_key === 'all' ? ' is-active' : ''; ?>" data-ecf-utility-tab="<?php echo esc_attr($category_key); ?>" data-ecf-help="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>" title="<?php echo esc_attr($utility_class_help_texts[$category_key] ?? ''); ?>">
                                    <span class="dashicons <?php echo esc_attr($utility_tab_icons[$category_key] ?? 'dashicons-category'); ?>" aria-hidden="true"></span>
                                    <?php echo esc_html($category_label); ?>
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
                                           data-class-name="<?php echo esc_attr($class_name); ?>"
                                           title="<?php echo esc_attr($this->utility_class_tooltip($class_name, $category_key)); ?>">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr($this->option_name); ?>[utility_classes][enabled][<?php echo esc_attr($class_name); ?>]"
                                               value="1"
                                               class="ecf-utility-class-toggle"
                                               <?php checked(!empty($settings['utility_classes']['enabled'][$class_name])); ?>>
                                        <span class="ecf-starter-class-item__badge ecf-starter-class-item__badge--utility"><?php echo esc_html__('Utility', 'ecf-framework'); ?></span>
                                        <span class="ecf-starter-class-item__name"><?php echo esc_html($class_name); ?></span>
                                        <span class="ecf-starter-class-item__meta"><?php echo esc_html($utility_class_categories[$category_key] ?? ucfirst($category_key)); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
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
        ?>
        <div class="ecf-panel" data-panel="components">
            <div class="ecf-grid">
                <div class="ecf-card">
                    <div class="ecf-general-settings__header">
      <h2><?php echo esc_html__('General Settings', 'ecf-framework'); ?></h2>
                        <div class="ecf-format-picker__tooltip ecf-format-picker__tooltip--header" data-ecf-format-tooltip hidden><?php echo esc_html($boxed_format_options[$boxed_selected_format]['tip']); ?></div>
                    </div>
                    <div class="ecf-var-tabs ecf-general-tabs" data-ecf-general-tabs>
                        <button type="button" class="ecf-var-tab is-active" data-ecf-general-tab="website" title="<?php echo esc_attr__('Website-wide basics like root size, widths, body font and base colors.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-home" aria-hidden="true"></span><?php echo esc_html__('Website', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="interface" title="<?php echo esc_attr__('Elementor editor helpers, plugin language and the ECF backend appearance.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span><?php echo esc_html__('Interface', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="system" title="<?php echo esc_attr__('System status, update checks, limits and integrated help.', 'ecf-framework'); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html__('System', 'ecf-framework'); ?></button>
                        <button type="button" class="ecf-var-tab" data-ecf-general-tab="favorites" data-ecf-new-key="general-favorites" title="<?php echo esc_attr__('Pinned quick settings from Website and Plugin basics.', 'ecf-framework'); ?>"><span class="dashicons dashicons-heart" aria-hidden="true"></span><?php echo esc_html__('Favorites', 'ecf-framework'); ?></button>
                    </div>
                    <div class="ecf-general-section is-active ecf-general-section--website" data-ecf-general-section="website" data-ecf-layout-group="components-website">
                        <div class="ecf-settings-group" data-ecf-layout-item="website-type-size">
                            <div class="ecf-settings-group__header">
                                <h3><?php echo esc_html__('Type & Size', 'ecf-framework'); ?></h3>
                                <p><?php echo esc_html__('Set the root rem size plus the default body type choices for your site.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-layout-columns-toolbar ecf-layout-columns-toolbar--inner ecf-layout-columns-toolbar--toggle" data-ecf-layout-columns-toolbar data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Columns', 'ecf-framework'); ?>">
                                <span class="ecf-layout-columns-toolbar__label"><?php echo esc_html__('Layout', 'ecf-framework'); ?></span>
                                <div class="ecf-layout-columns-toolbar__options" role="group" aria-label="<?php echo esc_attr__('Columns', 'ecf-framework'); ?>">
                                    <button type="button" class="ecf-layout-columns-btn is-active" data-ecf-layout-columns-btn="1" data-ecf-layout-columns="2" data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Switch to 2 columns', 'ecf-framework'); ?>" title="<?php echo esc_attr__('2 columns side by side', 'ecf-framework'); ?>">
                                        <span class="ecf-layout-columns-btn__icon ecf-layout-columns-btn__icon--2" aria-hidden="true">
                                            <span></span><span></span>
                                        </span>
                                        <span class="ecf-layout-columns-btn__label"><?php echo esc_html__('2 columns', 'ecf-framework'); ?></span>
                                    </button>
                                    <button type="button" class="ecf-layout-columns-btn" data-ecf-layout-columns-btn="1" data-ecf-layout-columns="3" data-group="components-website-type-size" aria-label="<?php echo esc_attr__('Switch to 3 columns', 'ecf-framework'); ?>" title="<?php echo esc_attr__('3 columns side by side', 'ecf-framework'); ?>">
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
                                    <div class="ecf-inline-size-input">
                                        <input type="text" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_value]" value="<?php echo esc_attr($content_width_parts['value']); ?>" placeholder="72 oder min(72ch, 100% - 2rem)" title="<?php echo esc_attr__('Enter either a simple value like 72 or, with f(x), a full CSS expression such as min(72ch, 100% - 2rem).', 'ecf-framework'); ?>">
                                        <div class="ecf-format-picker" data-ecf-format-picker>
                                            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[content_max_width_format]" value="<?php echo esc_attr($content_selected_format); ?>" data-ecf-format-input>
                                            <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" title="<?php echo esc_attr__('Choose the unit for simple values. ch is usually best for readable text widths. Use f(x) for full CSS expressions.', 'ecf-framework'); ?>">
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
                                    <div class="ecf-inline-size-input">
                                        <input type="text" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_value]" value="<?php echo esc_attr($boxed_width_parts['value']); ?>" placeholder="1140 oder clamp(20rem, 80vw, 1140px)" title="<?php echo esc_attr__('Enter either a plain value like 1140 or, with f(x), a full CSS expression such as clamp(20rem, 80vw, 1140px).', 'ecf-framework'); ?>">
                                        <div class="ecf-format-picker" data-ecf-format-picker>
                                            <input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[elementor_boxed_width_format]" value="<?php echo esc_attr($boxed_selected_format); ?>" data-ecf-format-input>
                                            <button type="button" class="ecf-format-picker__trigger" data-ecf-format-trigger aria-expanded="false" title="<?php echo esc_attr__('Choose the unit for simple values. Use f(x) for complete CSS expressions like min(...), calc(...) or clamp(...).', 'ecf-framework'); ?>">
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
                        <div class="ecf-settings-group" data-ecf-layout-item="website-base-colors">
                            <div class="ecf-settings-group__header">
                                <h3><?php echo esc_html__('Base Colors', 'ecf-framework'); ?></h3>
                                <p><?php echo esc_html__('These colors define the visual starting point before component-specific styles take over.', 'ecf-framework'); ?></p>
                            </div>
                            <div class="ecf-form-grid ecf-form-grid--two">
                                <?php $this->render_general_color_field($settings, 'base_text_color', 'Base Text Color', 'Basis-Textfarbe', 'Default body text color for the whole site.', 'Standard-Textfarbe für den Fließtext der ganzen Website.', 'editor-textcolor'); ?>
                                <?php $this->render_general_color_field($settings, 'base_background_color', 'Base Background Color', 'Basis-Hintergrundfarbe', 'Default page background for the website.', 'Standard-Seitenhintergrund für die Website.', 'art'); ?>
                                <?php $this->render_general_color_field($settings, 'link_color', 'Link Color', 'Link-Farbe', 'Default color for normal links.', 'Standardfarbe für normale Links.', 'admin-links'); ?>
                                <?php $this->render_general_color_field($settings, 'focus_color', 'Focus Color', 'Fokus-Farbe', 'Visible color for keyboard focus outlines and focus rings.', 'Sichtbare Farbe für Tastatur-Fokusrahmen und Focus-Rings.', 'visibility'); ?>
                                <label data-ecf-general-field="focus_outline_width">
                                    <span class="ecf-general-label-with-favorite">
                                        <?php echo $this->general_setting_label(__('Focus Outline Width', 'ecf-framework'), 'Visible width of the keyboard focus outline.', 'editor-expand'); ?>
                                        <?php $this->render_general_setting_favorite_toggle($settings, 'focus_outline_width'); ?>
                                    </span>
                                    <?php $this->render_general_size_field_inline($settings, 'focus_outline_width', $settings['focus_outline_width'] ?? '2px', $this->focus_outline_format_options(), 'px', '2', __('Visible width of the keyboard focus outline.', 'ecf-framework')); ?>
                                </label>
                                <label data-ecf-general-field="focus_outline_offset">
                                    <span class="ecf-general-label-with-favorite">
                                        <?php echo $this->general_setting_label(__('Focus Outline Offset', 'ecf-framework'), 'Distance between the element edge and the keyboard focus outline.', 'move'); ?>
                                        <?php $this->render_general_setting_favorite_toggle($settings, 'focus_outline_offset'); ?>
                                    </span>
                                    <?php $this->render_general_size_field_inline($settings, 'focus_outline_offset', $settings['focus_outline_offset'] ?? '2px', $this->focus_outline_format_options(), 'px', '2', __('Distance between the element edge and the keyboard focus outline.', 'ecf-framework')); ?>
                                </label>
                            </div>
                        </div>
                        <details class="ecf-settings-group ecf-settings-group--details" data-ecf-layout-item="website-scale-impact">
                            <summary class="ecf-settings-group__summary">
                                <span>
                                    <strong><?php echo esc_html__('Scale Impact', 'ecf-framework'); ?></strong>
                                    <small><?php echo esc_html__('See how the current root size affects typography, spacing and radius tokens.', 'ecf-framework'); ?></small>
                                </span>
                                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                            </summary>
                            <div class="ecf-root-font-impact"
                         data-ecf-root-font-impact
                         data-type-step="<?php echo esc_attr($settings['typography']['scale']['base_index'] ?? 'm'); ?>"
                         data-spacing-step="<?php echo esc_attr($settings['spacing']['base_index'] ?? 'm'); ?>"
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
                        </details>
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
                                        <span><?php echo $this->tip_hover_label(__('Colors', 'ecf-framework'), 'Filters color variables like brand, text, border or background colors to color-compatible Elementor fields.', ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][text]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['text'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Typography', 'ecf-framework'), 'Filters typography variables like text sizes so they appear only in matching typography-related Elementor controls.', ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][space]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['space'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Spacing', 'ecf-framework'), 'Filters spacing variables like gaps, padding or margins into spacing-compatible Elementor fields.', ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][radius]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['radius'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Radius', 'ecf-framework'), 'Filters border-radius style variables into matching radius fields.', ''); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][shadow]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['shadow'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Shadows', 'ecf-framework'), 'Filters shadow variables into matching box-shadow or shadow-related Elementor fields.', 'Filtert Schatten-Variablen nur in passende box-shadow- oder schattenbezogene Elementor-Felder.'); ?></span>
                                    </label>
                                    <label class="ecf-form-grid__checkbox">
                                        <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[elementor_variable_type_filter_scopes][string]" value="1" <?php checked(!empty($settings['elementor_variable_type_filter_scopes']['string'])); ?>>
                                        <span><?php echo $this->tip_hover_label(__('Other text values', 'ecf-framework'), 'Filters remaining string-based values that are neither size nor color, for example free text-like CSS values.', ''); ?></span>
                                    </label>
                                </div>
                            </details>
                            <?php $this->render_interface_language_field($settings); ?>
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
        ?>
        <div class="ecf-panel" data-panel="typography">
            <div class="ecf-typography-layout" data-ecf-layout-group="typography-main">
                <div class="ecf-typography-sidebar" data-ecf-layout-item="typography-settings">
                    <div class="ecf-card">
                        <h2><?php echo esc_html__('Type Scale', 'ecf-framework'); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--compact">
                            <div class="ecf-scale-group">
                                <strong class="ecf-scale-group__title"><?php echo esc_html__('Minimum', 'ecf-framework'); ?></strong>
                                <label><?php echo $this->tip_hover_label(__('Min Font Size (px)', 'ecf-framework'), 'Font size at the smallest viewport (mobile). The base step gets this size.', ''); ?>
                                    <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][min_base]" value="<?php echo esc_attr($settings['typography']['scale']['min_base'] ?? 16); ?>">
                                </label>
                                <label><?php echo $this->tip_hover_label(__('Min Scale Ratio', 'ecf-framework'), 'Multiplier between steps at mobile size. E.g. 1.125 means each step is 12.5% larger.', ''); ?>
                                    <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][min_ratio]', $settings['typography']['scale']['min_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.125)); ?>
                                </label>
                            </div>
                            <div class="ecf-scale-group ecf-scale-group--divider">
                                <strong class="ecf-scale-group__title"><?php echo esc_html__('Maximum', 'ecf-framework'); ?></strong>
                                <label><?php echo $this->tip_hover_label(__('Max Font Size (px)', 'ecf-framework'), 'Font size at the largest viewport (desktop). The base step gets this size.', ''); ?>
                                    <input type="number" step="0.01" name="<?php echo $this->option_name; ?>[typography][scale][max_base]" value="<?php echo esc_attr($settings['typography']['scale']['max_base'] ?? 18); ?>">
                                </label>
                                <label><?php echo $this->tip_hover_label(__('Max Scale Ratio', 'ecf-framework'), 'Multiplier between steps at desktop size. A higher ratio creates more contrast between sizes.', ''); ?>
                                    <?php $this->render_scale_ratio_select($this->option_name . '[typography][scale][max_ratio]', $settings['typography']['scale']['max_ratio'] ?? ($settings['typography']['scale']['ratio'] ?? 1.25)); ?>
                                </label>
                            </div>
                            <label><?php echo $this->tip_hover_label(__('Base step', 'ecf-framework'), 'The step that equals your base font size. Steps above are larger, steps below are smaller.', ''); ?>
                                <select name="<?php echo $this->option_name; ?>[typography][scale][base_index]">
                                    <?php foreach ($settings['typography']['scale']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['typography']['scale']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-check ecf-check--inline">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[typography][scale][fluid]" value="1" <?php checked(!empty($settings['typography']['scale']['fluid'])); ?>>
                                <span><?php echo $this->tip_hover_label('Fluid (clamp)', 'Generates clamp() values that smoothly scale between min and max viewport width.', 'Generiert clamp()-Werte, die zwischen Min- und Max-Viewport flüssig skalieren.'); ?></span>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min viewport (px)', 'ecf-framework'), 'Viewport width at which the minimum font sizes apply (typically 375px for mobile).', ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][min_vw]" value="<?php echo esc_attr($settings['typography']['scale']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max viewport (px)', 'ecf-framework'), 'Viewport width at which the maximum font sizes apply (typically 1280px for desktop).', ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[typography][scale][max_vw]" value="<?php echo esc_attr($settings['typography']['scale']['max_vw']); ?>">
                            </label>
                        </div>
                        <p class="ecf-muted-copy"><?php echo esc_html__('The preview updates live while you edit the scale settings.', 'ecf-framework'); ?></p>
                    </div>
                    <div class="ecf-card">
                        <h2><?php echo esc_html__('Site Font Assignment', 'ecf-framework'); ?></h2>
                        <p class="ecf-muted-copy"><?php echo esc_html__('Choose the active body and heading fonts here. Imported fonts are stored locally in the media library and can be removed again below.', 'ecf-framework'); ?></p>
                        <p class="ecf-muted-copy ecf-font-assignment-note"><?php echo esc_html__('Privacy note: imported library fonts are downloaded into your own media library on the server and served locally, so the frontend does not need live requests to external font providers.', 'ecf-framework'); ?></p>
                        <div class="ecf-font-assignment-accordion">
                            <details class="ecf-settings-group ecf-settings-group--details ecf-font-assignment-accordion__item" open>
                                <summary class="ecf-settings-group__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Body Font', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('Default font for flowing text and normal site copy.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-font-assignment-accordion__content">
                                    <?php $this->render_base_font_family_field($settings, false); ?>
                                </div>
                            </details>
                            <details class="ecf-settings-group ecf-settings-group--details ecf-font-assignment-accordion__item">
                                <summary class="ecf-settings-group__summary">
                                    <span>
                                        <strong><?php echo esc_html__('Heading Font', 'ecf-framework'); ?></strong>
                                        <small><?php echo esc_html__('Separate font family for h1 to h6 and heading-like elements.', 'ecf-framework'); ?></small>
                                    </span>
                                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                                </summary>
                                <div class="ecf-font-assignment-accordion__content">
                                    <?php $this->render_heading_font_family_field($settings, false); ?>
                                </div>
                            </details>
                        </div>
                    </div>
                    <details class="ecf-card ecf-card--details" open>
                        <summary class="ecf-card__summary">
                            <span>
                                <strong><?php echo esc_html__('Core Font Tokens', 'ecf-framework'); ?></strong>
                                <small><?php echo esc_html__('Reusable typography stacks like Primary, Secondary and Mono.', 'ecf-framework'); ?></small>
                            </span>
                            <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
                        </summary>
                        <div class="ecf-card__content">
                            <?php $this->render_rows('typography_fonts', $settings['typography']['fonts'], $this->option_name.'[typography][fonts]'); ?>
                        </div>
                    </details>
                    <details class="ecf-card ecf-card--details" data-ecf-local-fonts-section>
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
                <div class="ecf-card ecf-typography-preview-card"
                     data-ecf-layout-item="typography-preview"
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
                                    <strong data-ecf-focus-min-line><?php echo esc_html($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?></strong>
                                    <span><i class="dashicons dashicons-smartphone"></i><?php echo esc_html__('Minimum', 'ecf-framework'); ?></span>
                                </div>
                                <div class="ecf-typography-focus__size-line ecf-typography-focus__size-line--max">
                                    <strong data-ecf-focus-max-line><?php echo esc_html($this->type_preview_text_for_step((string) ($settings['typography']['scale']['base_index'] ?? 'm'), $settings)); ?></strong>
                                    <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
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
                        <button type="button" class="ecf-step-btn" data-ecf-add-step="smaller" title="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="smaller" title="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>">−</button>
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
                                        <strong style="font-size:<?php echo esc_attr($item['min_px']); ?>px;"><?php echo esc_html($this->type_preview_text_for_step((string) $item['step'], $settings)); ?></strong>
                                    </div>
                                    <div class="ecf-type-row__sample-line ecf-type-row__sample-line--max">
                                        <span><i class="dashicons dashicons-desktop"></i><?php echo esc_html__('Maximum', 'ecf-framework'); ?></span>
                                        <strong style="font-size:<?php echo esc_attr($item['max_px']); ?>px;"><?php echo esc_html($this->type_preview_text_for_step((string) $item['step'], $settings)); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ecf-step-controls ecf-step-controls--bottom">
                        <button type="button" class="ecf-step-btn" data-ecf-add-step="larger" title="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove" data-ecf-remove-step="larger" title="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>">−</button>
                    </div>
                </div>
            </div>
            <div class="ecf-grid" data-ecf-layout-group="typography-secondary" data-ecf-masonry-layout="1">
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
        <?php
    }

    private function render_spacing_panel($args) {
        extract($args, EXTR_SKIP);
        ?>
        <div class="ecf-panel" data-panel="spacing">
            <div class="ecf-spacing-layout" data-ecf-layout-group="spacing-main">
                <div class="ecf-spacing-sidebar" data-ecf-layout-item="spacing-settings">
                    <div class="ecf-card">
                        <h2><?php echo esc_html__('Base Settings', 'ecf-framework'); ?></h2>
                        <?php $this->render_root_font_size_select($settings, false); ?>
                        <div class="ecf-form-grid ecf-form-grid--single">
                            <label><?php echo $this->tip_hover_label(__('Naming Convention', 'ecf-framework'), 'Prefix used for CSS tokens, e.g. "space" → --ecf-space-m. Change to rename all tokens.', ''); ?>
                                <input type="text" name="<?php echo $this->option_name; ?>[spacing][prefix]" value="<?php echo esc_attr($settings['spacing']['prefix'] ?? 'space'); ?>" placeholder="space">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Size (px)', 'ecf-framework'), 'Base spacing size at the smallest viewport (mobile). All other steps scale relative to this.', ''); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][min_base]" value="<?php echo esc_attr($settings['spacing']['min_base'] ?? 14); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Scale Ratio', 'ecf-framework'), 'Multiplier between spacing steps on mobile. 1.25 means each step is 25% larger than the previous.', ''); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][min_ratio]', $settings['spacing']['min_ratio'] ?? 1.2); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Size (px)', 'ecf-framework'), 'Base spacing size at the largest viewport (desktop). Typically slightly larger than the min size.', ''); ?>
                                <input type="number" step="0.1" name="<?php echo $this->option_name; ?>[spacing][max_base]" value="<?php echo esc_attr($settings['spacing']['max_base'] ?? $settings['spacing']['base'] ?? 16); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Scale Ratio', 'ecf-framework'), 'Multiplier between spacing steps on desktop. A higher ratio creates more visual contrast between sizes.', ''); ?>
                                <?php $this->render_scale_ratio_select($this->option_name.'[spacing][max_ratio]', $settings['spacing']['max_ratio'] ?? $settings['spacing']['ratio_up'] ?? 1.25); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Base Step', 'ecf-framework'), 'The step that equals your base spacing size. Steps above are larger, steps below are smaller.', ''); ?>
                                <select name="<?php echo $this->option_name; ?>[spacing][base_index]">
                                    <?php foreach ($settings['spacing']['steps'] as $step): ?>
                                        <option value="<?php echo esc_attr($step); ?>" <?php selected($settings['spacing']['base_index'], $step); ?>><?php echo esc_html($step); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="ecf-form-grid__checkbox">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[spacing][fluid]" value="1" <?php checked(!empty($settings['spacing']['fluid'])); ?>>
                                <?php echo $this->tip_hover_label(__('Fluid (clamp)', 'ecf-framework'), 'Generates clamp() values that smoothly scale between min and max viewport widths.', ''); ?>
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Min Viewport (px)', 'ecf-framework'), 'Screen width at which minimum spacing sizes apply. Usually 375px (iPhone).', ''); ?>
                                <input type="number" name="<?php echo $this->option_name; ?>[spacing][min_vw]" value="<?php echo esc_attr($settings['spacing']['min_vw']); ?>">
                            </label>
                            <label><?php echo $this->tip_hover_label(__('Max Viewport (px)', 'ecf-framework'), 'Screen width at which maximum spacing sizes apply. Usually 1280px (desktop).', ''); ?>
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
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="smaller" title="<?php echo esc_attr__('Add smaller step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="smaller" title="<?php echo esc_attr__('Remove smallest step', 'ecf-framework'); ?>">−</button>
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
                        <button type="button" class="ecf-step-btn ecf-spacing-step-btn" data-ecf-spacing-add="larger" title="<?php echo esc_attr__('Add larger step', 'ecf-framework'); ?>">+</button>
                        <button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-spacing-step-btn" data-ecf-spacing-remove="larger" title="<?php echo esc_attr__('Remove largest step', 'ecf-framework'); ?>">−</button>
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
            <div class="ecf-grid" data-ecf-layout-group="sync-main" data-ecf-masonry-layout="1">
                <div class="ecf-card" data-ecf-layout-item="sync-native">
                    <h2><?php echo esc_html__('Native Elementor Sync', 'ecf-framework'); ?></h2>
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
                    <p style="color:#9ca3af;font-size:13px;margin:0 0 12px;"><?php echo wp_kses(__('Works in <strong>merge mode</strong> — ECF adds to existing Elementor variables and Global Classes without overwriting.', 'ecf-framework'), ['strong' => []]); ?></p>
                    <p style="color:#6b7280;font-size:13px;margin:0 0 16px;"><?php echo esc_html__('Synced:', 'ecf-framework'); ?>
                        <br>• <?php echo esc_html__('Variables', 'ecf-framework'); ?>: <code>ecf-color-*</code>, <code>ecf-space-*</code>, <code>ecf-radius-*</code>, <code>ecf-text-*</code>
                        <br>• <?php echo esc_html__('Global Classes', 'ecf-framework'); ?>: <?php echo esc_html__('selected starter classes and selected utility classes', 'ecf-framework'); ?>
                    </p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=ecf_native_sync')); ?>">
                            <?php wp_nonce_field('ecf_native_sync'); ?>
                            <input type="hidden" name="action" value="ecf_native_sync">
                            <button type="submit" class="ecf-btn ecf-btn--primary"><span class="dashicons dashicons-update" aria-hidden="true"></span><span><?php echo esc_html__('Sync to Elementor (Merge)', 'ecf-framework'); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Do you really want to remove %1$d ECF Global Classes from Elementor so they can be synced again as empty classes?', 'ecf-framework'), $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_class_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_class_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--ghost" <?php disabled($cleanup_class_count === 0); ?> title="<?php echo esc_attr($cleanup_class_count === 0 ? __('No ECF classes found in Elementor.', 'ecf-framework') : sprintf(__('Removes %1$d ECF classes from Elementor without touching variables.', 'ecf-framework'), $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html__('Cleanup ECF Classes', 'ecf-framework'); ?></span></button>
                        </form>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('Do you really want to remove %1$d ECF variables and %2$d Global Classes from Elementor?', 'ecf-framework'), $cleanup_variable_count, $cleanup_class_count)); ?>');">
                            <?php wp_nonce_field('ecf_native_cleanup'); ?>
                            <input type="hidden" name="action" value="ecf_native_cleanup">
                            <button type="submit" class="ecf-btn ecf-btn--danger" <?php disabled($cleanup_total_count === 0); ?> title="<?php echo esc_attr($cleanup_total_count === 0 ? __('No ECF variables or classes found in Elementor.', 'ecf-framework') : sprintf(__('Removes %1$d variables and %2$d classes from Elementor.', 'ecf-framework'), $cleanup_variable_count, $cleanup_class_count)); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span><?php echo esc_html__('Cleanup ECF from Elementor', 'ecf-framework'); ?></span></button>
                        </form>
                    </div>
                </div>

                <div class="ecf-card" data-ecf-layout-item="sync-import-export">
                    <h2><?php echo esc_html__('Export / Import', 'ecf-framework'); ?></h2>
                    <p style="color:#9ca3af;font-size:13px;margin:0 0 16px;"><?php echo esc_html__('Export settings as JSON or import from another installation.', 'ecf-framework'); ?></p>
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

                <div class="ecf-card" data-ecf-layout-item="sync-editor-panel">
                    <h2><?php echo esc_html__('Elementor Editor Panel', 'ecf-framework'); ?></h2>
                    <p style="color:#9ca3af;font-size:13px;"><?php echo wp_kses(__('In the Elementor editor, find the <strong>Layrix</strong> section under the <strong>Advanced</strong> tab of any element.', 'ecf-framework'), ['strong' => []]); ?></p>
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
            <div class="ecf-row ecf-row--color">
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
                <button type="button" class="button ecf-remove-row">×</button>
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
