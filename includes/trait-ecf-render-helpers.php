<?php

trait ECF_Framework_Render_Helpers_Trait {
    private function render_field_token_pills(array $items) {
        $normalized_items = [];

        foreach ($items as $item) {
            $value = trim((string) ($item['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $type = trim((string) ($item['type'] ?? __('Variable', 'ecf-framework')));
            $normalized_items[] = [
                'type' => $type !== '' ? $type : __('Variable', 'ecf-framework'),
                'value' => $value,
                'copyable' => array_key_exists('copyable', $item) ? !empty($item['copyable']) : true,
            ];
        }

        if (empty($normalized_items)) {
            return;
        }
        ?>
        <div class="ecf-field-token-row">
            <?php foreach ($normalized_items as $item): ?>
                <?php if (!empty($item['copyable'])): ?>
                    <button type="button" class="ecf-field-token-pill" data-ecf-token-copy="<?php echo esc_attr($item['value']); ?>" data-tip="<?php echo esc_attr__('Copy', 'ecf-framework'); ?>" aria-label="<?php echo esc_attr__('Copy', 'ecf-framework'); ?>">
                        <span><?php echo esc_html($item['type']); ?></span>
                        <code><?php echo esc_html($item['value']); ?></code>
                    </button>
                <?php else: ?>
                    <span class="ecf-field-token-pill ecf-field-token-pill--static">
                        <span><?php echo esc_html($item['type']); ?></span>
                        <code><?php echo esc_html($item['value']); ?></code>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_field_dependency_disclosure($summary, array $items) {
        $summary = trim((string) $summary);
        if ($summary === '') {
            return;
        }

        $normalized_items = [];
        foreach ($items as $item) {
            $value = trim((string) ($item['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $normalized_items[] = $item;
        }

        if (empty($normalized_items)) {
            return;
        }
        ?>
        <details class="ecf-field-meta-disclosure">
            <summary class="ecf-field-meta-disclosure__summary">
                <span><?php echo esc_html($summary); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </summary>
            <div class="ecf-field-meta-disclosure__content">
                <?php $this->render_field_token_pills($normalized_items); ?>
            </div>
        </details>
        <?php
    }

    private function render_rows($group, $rows, $input_key = null) {
        if ($input_key === null) {
            $input_key = $this->option_name . '[' . $group . ']';
        }
        $is_minmax = ($group === 'radius');
        $is_color = ($group === 'colors');
        $is_shadow = ($group === 'shadows');
        $col_class = $is_minmax ? 'ecf-table--minmax' : ($is_color ? 'ecf-table--color' : '');

        echo '<div class="ecf-table ' . esc_attr($col_class) . '" data-group="' . esc_attr($group) . '" data-input-key="' . esc_attr($input_key) . '" data-minmax="' . ($is_minmax ? '1' : '0') . '">';

        if ($is_color) {
            echo '<div class="ecf-head ecf-head--color">';
            echo '<span aria-hidden="true"></span>';
            echo '<span>' . $this->tip_hover_label(__('Name', 'ecf-framework'), __('Color name / CSS variable', 'ecf-framework'), '') . '</span>';
            echo '<span>' . $this->tip_hover_label(__('Value', 'ecf-framework'), __('Color value', 'ecf-framework'), '') . '</span>';
            echo '<span>' . $this->tip_hover_label(__('Format', 'ecf-framework'), __('Color format', 'ecf-framework'), '') . '</span>';
            echo '<span></span>';
            echo '<span></span>';
            echo '</div>';
        } elseif ($is_minmax) {
            echo '<div class="ecf-head ecf-head--minmax"><span>' . $this->tip_hover_label(__('Class', 'ecf-framework'), __('Token name / CSS class name', 'ecf-framework'), '') . '</span><span>' . $this->tip_hover_label(__('Min', 'ecf-framework'), __('Minimum value', 'ecf-framework'), '') . '</span><span>' . $this->tip_hover_label(__('Max', 'ecf-framework'), __('Maximum value', 'ecf-framework'), '') . '</span><span></span></div>';
        } elseif ($is_shadow) {
            echo '<div class="ecf-head">';
            echo '<span aria-hidden="true"></span>';
            echo '<span>' . $this->tip_hover_label(__('Class Name', 'ecf-framework'), __('Token name / CSS class name', 'ecf-framework'), '') . '</span>';
            echo '<span>' . $this->tip_hover_label(__('Value', 'ecf-framework'), __('Token value / CSS value', 'ecf-framework'), '') . '</span>';
            echo '<span></span>';
            echo '</div>';
        } else {
            echo '<div class="ecf-head"><span>' . $this->tip_hover_label(__('Class Name', 'ecf-framework'), __('Token name / CSS class name', 'ecf-framework'), '') . '</span><span>' . $this->tip_hover_label(__('Value', 'ecf-framework'), __('Token value / CSS value', 'ecf-framework'), '') . '</span><span></span></div>';
        }

        foreach ($rows as $i => $row) {
            if ($is_color) {
                $format = strtolower($row['format'] ?? 'hex');
                if (!in_array($format, ['hex', 'hexa', 'rgb', 'rgba', 'hsl', 'hsla'], true)) {
                    $format = 'hex';
                }
                $generate_shades = !array_key_exists('generate_shades', $row) || !empty($row['generate_shades']);
                $shade_count = min(10, max(4, (int) ($row['shade_count'] ?? 6)));
                $generate_tints = !empty($row['generate_tints']);
                $tint_count = min(10, max(4, (int) ($row['tint_count'] ?? 6)));
                $color_name = sanitize_key($row['name'] ?? '');
                $color_value = $this->sanitize_css_color_value($row['value'] ?? '#000000', $format);
                echo '<div class="ecf-row ecf-row--color" data-ecf-color-detail-row>';
                $picker_hex = $this->format_css_color($this->parse_css_color($row['value']), 'hex');
                echo '<input type="text" class="ecf-color-field" value="' . esc_attr($picker_hex) . '" placeholder="#000000" />';
                echo '<input type="hidden" class="ecf-color-value-input" name="' . $input_key . '[' . $i . '][value]" value="' . esc_attr($row['value']) . '" />';
                echo '<input type="text" data-ecf-slug-field="token" name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr__('name', 'ecf-framework') . '" />';
                echo '<code class="ecf-color-varname">--ecf-color-<span>' . esc_html($row['name'] ?? '') . '</span></code>';
                echo '<input type="text" class="ecf-color-value-display" value="' . esc_attr($row['value']) . '" spellcheck="false" autocomplete="off" />';
                echo '<select class="ecf-color-format-select" name="' . $input_key . '[' . $i . '][format]">';
                echo '<option value="hex"' . selected($format, 'hex', false) . '>HEX</option>';
                echo '<option value="hexa"' . selected($format, 'hexa', false) . '>HEXA</option>';
                echo '<option value="rgb"' . selected($format, 'rgb', false) . '>RGB</option>';
                echo '<option value="rgba"' . selected($format, 'rgba', false) . '>RGBA</option>';
                echo '<option value="hsl"' . selected($format, 'hsl', false) . '>HSL</option>';
                echo '<option value="hsla"' . selected($format, 'hsla', false) . '>HSLA</option>';
                echo '</select>';
                echo '<input type="hidden" name="' . $input_key . '[' . $i . '][generate_shades]" value="0" />';
                echo '<input type="hidden" name="' . $input_key . '[' . $i . '][generate_tints]" value="0" />';
                echo '<button type="button" class="ecf-color-detail-toggle" aria-expanded="false" data-tip="' . esc_attr__('ecf_color_generator_show_details', 'ecf-framework') . '" aria-label="' . esc_attr__('ecf_color_generator_show_details', 'ecf-framework') . '"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>';
                echo '<button type="button" class="ecf-remove-row" data-tip="' . esc_attr__('Remove', 'ecf-framework') . '" aria-label="' . esc_attr__('Remove', 'ecf-framework') . '">×</button>';
                $this->render_color_detail_panel($color_name, $color_value, $picker_hex, [
                    'generate_shades' => $generate_shades ? '1' : '0',
                    'shade_count' => $shade_count,
                    'generate_tints' => $generate_tints ? '1' : '0',
                    'tint_count' => $tint_count,
                    'input_key' => $input_key . '[' . $i . ']',
                ]);
                echo '</div>';
            } elseif ($is_minmax) {
                echo '<div class="ecf-row ecf-row--minmax">';
                echo '<input type="text" data-ecf-slug-field="token" name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr__('class name', 'ecf-framework') . '" />';
                $min_val = esc_attr($row['min'] ?? $row['value'] ?? '');
                $max_val = esc_attr($row['max'] ?? $row['value'] ?? '');
                echo '<input type="text" name="' . $input_key . '[' . $i . '][min]" value="' . $min_val . '" placeholder="min" />';
                echo '<input type="text" name="' . $input_key . '[' . $i . '][max]" value="' . $max_val . '" placeholder="max" />';
                echo '<button type="button" class="ecf-remove-row" data-tip="' . esc_attr__('Remove', 'ecf-framework') . '" aria-label="' . esc_attr__('Remove', 'ecf-framework') . '">×</button>';
                echo '</div>';
            } else {
                echo '<div class="ecf-row"' . ($is_shadow ? ' data-ecf-shadow-edit-row data-ecf-shadow-row-index="' . esc_attr((string) $i) . '"' : '') . '>';
                if ($is_shadow) {
                    echo '<div class="ecf-shadow-preview" style="box-shadow:' . esc_attr($row['value']) . ';"></div>';
                }
                echo '<input type="text" data-ecf-slug-field="token" ' . ($is_shadow ? 'data-ecf-shadow-name-input ' : '') . 'name="' . $input_key . '[' . $i . '][name]" value="' . esc_attr($row['name']) . '" placeholder="' . esc_attr__('class name', 'ecf-framework') . '" />';
                echo '<input type="text" ' . ($is_shadow ? 'data-ecf-shadow-value-input ' : '') . 'name="' . $input_key . '[' . $i . '][value]" value="' . esc_attr($row['value']) . '" placeholder="value" />';
                echo '<button type="button" class="ecf-remove-row" data-tip="' . esc_attr__('Remove', 'ecf-framework') . '" aria-label="' . esc_attr__('Remove', 'ecf-framework') . '">×</button>';
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<div class="ecf-row-controls ecf-row-controls--bottom">';
        echo '<button type="button" class="ecf-step-btn ecf-add-row" data-group="' . esc_attr($group) . '" data-tip="' . esc_attr__('Add', 'ecf-framework') . '" aria-label="' . esc_attr__('Add', 'ecf-framework') . '">+</button>';
        echo '<button type="button" class="ecf-step-btn ecf-step-btn--remove ecf-remove-last-row" data-group="' . esc_attr($group) . '" data-tip="' . esc_attr__('Remove last', 'ecf-framework') . '" aria-label="' . esc_attr__('Remove last', 'ecf-framework') . '">−</button>';
        echo '</div>';
    }

    private function render_color_detail_panel($color_name, $color_value, $picker_hex, $options = []) {
        $token = $color_name !== '' ? '--ecf-color-' . $color_name : '--ecf-color-name';
        $safe_value = $color_value !== '' ? $color_value : ($picker_hex ?: '#000000');
        $generate_shades = !empty($options['generate_shades']);
        $shade_count = min(10, max(4, (int) ($options['shade_count'] ?? 6)));
        $generate_tints = !empty($options['generate_tints']);
        $tint_count = min(10, max(4, (int) ($options['tint_count'] ?? 6)));
        $input_key = (string) ($options['input_key'] ?? '');
        $variants = $this->generated_color_variants($safe_value, [
            'generate_shades' => $generate_shades ? '1' : '0',
            'shade_count' => $shade_count,
            'generate_tints' => $generate_tints ? '1' : '0',
            'tint_count' => $tint_count,
        ]);
        ?>
        <div class="ecf-color-detail" hidden>
            <div class="ecf-color-detail__controls">
                <div class="ecf-color-generator-row">
                    <label class="ecf-color-generator-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($input_key); ?>[generate_shades]" value="1" data-ecf-color-generate="shades" <?php checked($generate_shades); ?>>
                        <span><?php echo esc_html__('ecf_color_generator_generate_shades', 'ecf-framework'); ?></span>
                    </label>
                    <div class="ecf-color-generator-count">
                        <button type="button" data-ecf-color-count-minus="shades">−</button>
                        <input type="number" min="4" max="10" name="<?php echo esc_attr($input_key); ?>[shade_count]" value="<?php echo esc_attr((string) $shade_count); ?>" data-ecf-color-count="shades">
                        <button type="button" data-ecf-color-count-plus="shades">+</button>
                    </div>
                </div>
                <div class="ecf-color-generator-row">
                    <label class="ecf-color-generator-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($input_key); ?>[generate_tints]" value="1" data-ecf-color-generate="tints" <?php checked($generate_tints); ?>>
                        <span><?php echo esc_html__('ecf_color_generator_generate_tints', 'ecf-framework'); ?></span>
                    </label>
                    <div class="ecf-color-generator-count">
                        <button type="button" data-ecf-color-count-minus="tints">−</button>
                        <input type="number" min="4" max="10" name="<?php echo esc_attr($input_key); ?>[tint_count]" value="<?php echo esc_attr((string) $tint_count); ?>" data-ecf-color-count="tints">
                        <button type="button" data-ecf-color-count-plus="tints">+</button>
                    </div>
                </div>
            </div>
            <div class="ecf-color-detail__preview" style="<?php echo esc_attr('--ecf-color-detail-base:' . $safe_value . ';'); ?>">
                <?php if (!empty($variants)): ?>
                    <?php foreach ($variants as $variant => $variant_hex): ?>
                        <span style="<?php echo esc_attr('background:' . $variant_hex . ';'); ?>" data-tip="<?php echo esc_attr($token . '-' . $variant . ': ' . $variant_hex); ?>"></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="<?php echo esc_attr('background:' . $safe_value . ';'); ?>"></span>
                <?php endif; ?>
            </div>
            <div class="ecf-color-detail__meta">
                <strong><?php echo esc_html($token); ?></strong>
                <code><?php echo esc_html($safe_value); ?></code>
            </div>
            <div class="ecf-color-detail__shades">
                <?php foreach ($variants as $variant => $variant_hex): ?>
                    <?php $variant_token = $token . '-' . $variant; ?>
                    <button type="button" class="ecf-color-token-copy" data-ecf-copy-text="<?php echo esc_attr($variant_token); ?>">
                        <i style="<?php echo esc_attr('background:' . $variant_hex . ';'); ?>"></i>
                        <span><code><?php echo esc_html($variant_token); ?></code><small><?php echo esc_html($variant_hex); ?></small></span>
                        <em><?php echo esc_html__('ecf_color_generator_copy', 'ecf-framework'); ?></em>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function size_prop($value) {
        if (!preg_match('/^(-?\d+(?:\.\d+)?)([a-z%]+)$/i', trim((string) $value), $m)) {
            return null;
        }
        return ['$$type' => 'size', 'value' => ['size' => $m[1] + 0, 'unit' => strtolower($m[2])]];
    }

    private function color_prop($value) {
        return ['$$type' => 'color', 'value' => $this->sanitize_css_color_value($value)];
    }

    private function string_prop($value) {
        return ['$$type' => 'string', 'value' => (string) $value];
    }

    private function class_variant(array $props) {
        return [[
            'meta' => ['state' => null, 'breakpoint' => 'desktop'],
            'props' => $props,
        ]];
    }
}
