<?php

trait ECF_Framework_Native_Elementor_Data_Trait {
    private function build_native_class_payloads() {
        $settings = $this->get_settings();
        $items = [];

        $boxed_width = trim((string) ($settings['elementor_boxed_width'] ?? ''));
        if ($boxed_width !== '') {
            $items['ecf-container-boxed'] = [
                'type' => 'class',
                'label' => 'ecf-container-boxed',
                'sync_to_v3' => false,
                // Keep the helper class empty in Elementor. The actual boxed-width
                // behavior is provided by the frontend CSS output below, which avoids
                // invalid free-form width payloads in Elementor's Global Classes API.
                'variants' => $this->class_variant([]),
            ];
        }

        foreach ($this->get_selected_starter_class_names($settings) as $starter_label) {
            if (isset($items[$starter_label])) {
                continue;
            }
            $items[$starter_label] = [
                'type' => 'class',
                'label' => $starter_label,
                'sync_to_v3' => false,
                'variants' => $this->class_variant([]),
            ];
        }

        foreach ($this->get_selected_utility_class_names($settings) as $utility_label) {
            if (isset($items[$utility_label])) {
                continue;
            }
            $items[$utility_label] = [
                'type' => 'class',
                'label' => $utility_label,
                'sync_to_v3' => false,
                'variants' => $this->class_variant([]),
            ];
        }

        return $items;
    }

    private function build_native_variable_payloads($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $root_base_px = $this->get_root_font_base_px($settings);
        $spacing = $this->build_spacing_scale($settings['spacing'], $root_base_px);
        $type_scale = $this->build_type_scale($settings['typography']['scale'], $root_base_px);
        $payloads = [];

        foreach ($settings['colors'] as $row) {
            $color = $this->sanitize_css_color_value($row['value'], $row['format'] ?? '');
            if ($color === '') {
                continue;
            }
            $payloads['ecf-color-' . sanitize_key($row['name'])] = [
                'type' => 'global-color-variable',
                'value' => $color,
            ];

            $color_name = sanitize_key($row['name']);
            foreach ($this->generated_color_variants($row['value'], $row) as $variant => $variant_value) {
                $payloads['ecf-color-' . $color_name . '-' . sanitize_key($variant)] = [
                    'type' => 'global-color-variable',
                    'value' => $variant_value,
                ];
            }
        }

        foreach ($spacing as $name => $value) {
            $payloads['ecf-space-' . sanitize_key($name)] = [
                'type' => 'global-size-variable',
                'value' => $value,
            ];
        }

        foreach ($settings['radius'] as $row) {
            $payloads['ecf-radius-' . sanitize_key($row['name'])] = [
                'type' => 'global-size-variable',
                'value' => $this->radius_css_value($row, 375, 1280, $root_base_px),
            ];
        }

        foreach ($type_scale as $name => $value) {
            $payloads['ecf-text-' . sanitize_key($name)] = [
                'type' => 'global-size-variable',
                'value' => $value,
            ];
        }

        $boxed_width = trim((string) ($settings['elementor_boxed_width'] ?? ''));
        if ($boxed_width !== '') {
            $payloads['ecf-container-boxed'] = [
                'type' => 'global-size-variable',
                'value' => $boxed_width,
            ];
        }

        foreach ($settings['shadows'] as $row) {
            $payloads['ecf-shadow-' . sanitize_key($row['name'])] = [
                'type' => 'global-string-variable',
                'value' => sanitize_text_field($row['value']),
            ];
        }

        return $payloads;
    }

    private function get_synced_variable_labels() {
        $labels = get_option($this->synced_variable_labels_option_name(), []);
        if (!is_array($labels)) {
            return [];
        }

        $normalized = [];
        foreach ($labels as $label) {
            $label = sanitize_key($label);
            if ($label !== '') {
                $normalized[$label] = true;
            }
        }

        return $normalized;
    }

    private function is_ecf_native_variable_label($label, $settings = null) {
        $normalized = sanitize_key($label);
        if ($normalized === '') {
            return false;
        }

        $tracked = $this->get_synced_variable_labels();
        if (!empty($tracked)) {
            return isset($tracked[$normalized]);
        }

        $payloads = $this->build_native_variable_payloads($settings);
        return isset($payloads[$normalized]);
    }

    private function is_ecf_native_variable($variable, $settings = null) {
        if (!is_object($variable) || !method_exists($variable, 'label')) {
            return false;
        }

        return $this->is_ecf_native_variable_label($variable->label(), $settings);
    }

    private function get_synced_class_labels() {
        $labels = get_option($this->synced_class_labels_option_name(), []);
        if (!is_array($labels)) {
            return [];
        }

        $normalized = [];
        foreach ($labels as $label) {
            $label = sanitize_key($label);
            if ($label !== '') {
                $normalized[$label] = true;
            }
        }

        return $normalized;
    }

    private function sync_native_variables_merge() {
        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            throw new \Exception('Elementor variable classes not available.');
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            throw new \Exception('No active Elementor kit found.');
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $existing_by_label = [];
        foreach ($collection->all() as $id => $variable) {
            $existing_by_label[strtolower($variable->label())] = $variable;
        }

        $settings = $this->get_settings();
        $payloads = $this->build_native_variable_payloads($settings);
        $desired_labels = array_keys($payloads);
        $previous_labels = get_option($this->synced_variable_labels_option_name(), []);
        $updated = 0;
        $created = 0;
        $deleted = 0;

        $desired_lookup = [];
        foreach ($desired_labels as $label) {
            $desired_lookup[strtolower($label)] = true;
        }

        foreach ((array) $previous_labels as $old_label) {
            $old_label = (string) $old_label;
            $old_key = strtolower($old_label);
            if ($old_label === '' || isset($desired_lookup[$old_key]) || !isset($existing_by_label[$old_key])) {
                continue;
            }
            foreach ($collection->all() as $id => $variable) {
                if (strtolower((string) $variable->label()) !== $old_key) {
                    continue;
                }
                if ($this->delete_native_variable_entity($collection, $id, $variable)) {
                    unset($existing_by_label[$old_key]);
                    $deleted++;
                }
                break;
            }
        }

        $upsert = function($label, $type, $value) use ($collection, &$existing_by_label, &$updated, &$created) {
            $key = strtolower($label);
            if (isset($existing_by_label[$key])) {
                $existing_by_label[$key]->apply_changes([
                    'type' => $type,
                    'value' => $value,
                    'sync_to_v3' => true,
                ]);
                if ($existing_by_label[$key]->is_deleted()) {
                    $existing_by_label[$key]->restore();
                }
                $updated++;
                return;
            }

            $id = $collection->next_id();
            $variable = \Elementor\Modules\Variables\Storage\Entities\Variable::create_new([
                'id' => $id,
                'type' => $type,
                'label' => $label,
                'value' => $value,
                'order' => $collection->get_next_order(),
                'sync_to_v3' => true,
            ]);
            $collection->add_variable($variable);
            $existing_by_label[$key] = $variable;
            $created++;
        };

        foreach ($payloads as $label => $payload) {
            $upsert($label, $payload['type'], $payload['value']);
        }

        $repo->save($collection);
        $this->clear_elementor_sync_caches();
        update_option($this->synced_variable_labels_option_name(), $desired_labels, false);

        return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted];
    }

    private function sync_native_classes_merge() {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            throw new \Exception('Elementor global classes repository not available.');
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];
        $desired_payloads = $this->build_native_class_payloads();
        $desired_labels = array_keys($desired_payloads);
        $previous_labels = get_option($this->synced_class_labels_option_name(), []);

        $label_to_id = [];
        foreach ($items as $id => $item) {
            if (!empty($item['label'])) {
                $label_to_id[strtolower($item['label'])] = $id;
            }
        }

        $class_limit = $this->get_native_global_class_limit();
        $current_total = is_array($items) ? count($items) : 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $deleted = 0;

        $desired_lookup = [];
        foreach ($desired_labels as $label) {
            $desired_lookup[strtolower($label)] = true;
        }

        foreach ((array) $previous_labels as $old_label) {
            $old_label = (string) $old_label;
            $old_key = strtolower($old_label);
            if ($old_label === '' || isset($desired_lookup[$old_key]) || !isset($label_to_id[$old_key])) {
                continue;
            }
            $old_id = $label_to_id[$old_key];
            unset($items[$old_id]);
            $order = array_values(array_filter($order, static fn($entry_id) => $entry_id !== $old_id));
            unset($label_to_id[$old_key]);
            $deleted++;
        }

        foreach ($desired_payloads as $label => $payload) {
            $key = strtolower($label);
            if (isset($label_to_id[$key])) {
                $id = $label_to_id[$key];
                $items[$id] = array_merge(['id' => $id], $payload, $items[$id]);
                if (!in_array($id, $order, true)) {
                    $order[] = $id;
                    $updated++;
                }
            } else {
                if (($current_total + $created) >= $class_limit) {
                    $skipped++;
                    continue;
                }
                $id = 'g-ecf-' . substr(md5($label), 0, 10);
                while (isset($items[$id])) {
                    $id = 'g-ecf-' . substr(md5($label . wp_generate_uuid4()), 0, 10);
                }
                $items[$id] = array_merge(['id' => $id], $payload);
                $order[] = $id;
                $created++;
            }
        }

        $repo->put($items, $order);
        $this->clear_elementor_sync_caches();
        update_option($this->synced_class_labels_option_name(), array_values($desired_labels), false);

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'skipped' => $skipped,
            'total' => max(0, $current_total - $deleted + $created),
            'limit' => $class_limit,
        ];
    }

    private function clear_elementor_sync_caches() {
        if (class_exists('\Elementor\Modules\DesignSystemSync\Classes\Variables_Provider')) {
            \Elementor\Modules\DesignSystemSync\Classes\Variables_Provider::clear_cache();
        }

        if (class_exists('\Elementor\Modules\DesignSystemSync\Classes\Classes_Provider')) {
            \Elementor\Modules\DesignSystemSync\Classes\Classes_Provider::clear_cache();
        }

        if (isset(\Elementor\Plugin::$instance->files_manager)) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }

        do_action('elementor/core/files/clear_cache');
        do_action('elementor/core/settings/page/clear_cache');
    }

    private function cleanup_native_variables() {
        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            throw new \Exception('Elementor variable classes not available.');
        }
        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            throw new \Exception('No active Elementor kit found.');
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $before_count = $this->count_ecf_variables_in_collection($collection);
        foreach ($collection->all() as $id => $variable) {
            if ($variable->is_deleted()) {
                continue;
            }
            if ($this->is_ecf_native_variable($variable)) {
                $this->delete_native_variable_entity($collection, $id, $variable);
            }
        }
        $repo->save($collection);
        $this->clear_elementor_sync_caches();
        delete_option($this->synced_variable_labels_option_name());

        $after_collection = $repo->load();
        $after_count = $this->count_ecf_variables_in_collection($after_collection);

        return max(0, $before_count - $after_count);
    }

    private function delete_native_variable_entity($collection, $id, $variable) {
        if (method_exists($variable, 'is_deleted') && $variable->is_deleted()) {
            return false;
        }

        if (method_exists($variable, 'delete')) {
            $variable->delete();
            return true;
        }

        if (method_exists($variable, 'apply_changes')) {
            $soft_delete_payloads = [
                ['deleted' => true],
                ['is_deleted' => true],
                ['deleted_at' => time()],
                ['status' => 'deleted'],
            ];

            foreach ($soft_delete_payloads as $changes) {
                try {
                    $variable->apply_changes($changes);
                } catch (\Throwable $e) {
                    continue;
                }

                if (!method_exists($variable, 'is_deleted') || $variable->is_deleted()) {
                    return true;
                }
            }
        }

        if (method_exists($variable, 'set_deleted')) {
            $variable->set_deleted(true);
            return true;
        }

        if (method_exists($variable, 'mark_as_deleted')) {
            $variable->mark_as_deleted();
            return true;
        }

        if (method_exists($collection, 'delete')) {
            $collection->delete($id);
            return true;
        }

        if (method_exists($collection, 'remove')) {
            $collection->remove($id);
            return true;
        }

        if (method_exists($collection, 'forget')) {
            $collection->forget($id);
            return true;
        }

        if ($this->remove_variable_from_collection_by_reflection($collection, $id, $variable)) {
            return true;
        }

        return false;
    }

    private function count_ecf_variables_in_collection($collection) {
        $count = 0;

        foreach ($collection->all() as $variable) {
            if (method_exists($variable, 'is_deleted') && $variable->is_deleted()) {
                continue;
            }

            if ($this->is_ecf_native_variable($variable)) {
                $count++;
            }
        }

        return $count;
    }

    private function remove_variable_from_collection_by_reflection($collection, $id, $variable) {
        try {
            $reflection = new \ReflectionObject($collection);
        } catch (\Throwable $e) {
            return false;
        }

        $removed = false;

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);

            try {
                $value = $property->getValue($collection);
            } catch (\Throwable $e) {
                continue;
            }

            if (!is_array($value) || empty($value)) {
                continue;
            }

            $updated = $value;

            if (array_key_exists($id, $updated)) {
                unset($updated[$id]);
                $removed = true;
            } else {
                foreach ($updated as $key => $entry) {
                    if ($entry === $variable) {
                        unset($updated[$key]);
                        $removed = true;
                        continue;
                    }

                    if (is_object($entry) && method_exists($entry, 'label') && strtolower($entry->label()) === strtolower($variable->label())) {
                        unset($updated[$key]);
                        $removed = true;
                    }
                }
            }

            if ($removed) {
                try {
                    $property->setValue($collection, $updated);
                } catch (\Throwable $e) {
                    return false;
                }
            }
        }

        return $removed;
    }

    private function get_native_variable_cleanup_count() {
        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            return 0;
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            return 0;
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();
        $count = 0;

        foreach ($collection->all() as $variable) {
            if ($variable->is_deleted()) {
                continue;
            }
            if ($this->is_ecf_native_variable($variable)) {
                $count++;
            }
        }

        return $count;
    }

    private function get_native_variable_counts() {
        $counts = [
            'total' => 0,
            'ecf' => 0,
            'foreign' => 0,
        ];

        if (!class_exists('\Elementor\Plugin') || !class_exists('\Elementor\Modules\Variables\Storage\Variables_Repository')) {
            return $counts;
        }

        $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
        if (!$kit) {
            return $counts;
        }

        $repo = new \Elementor\Modules\Variables\Storage\Variables_Repository($kit);
        $collection = $repo->load();

        foreach ($collection->all() as $variable) {
            if (method_exists($variable, 'is_deleted') && $variable->is_deleted()) {
                continue;
            }

            $counts['total']++;
            if ($this->is_ecf_native_variable($variable)) {
                $counts['ecf']++;
            } else {
                $counts['foreign']++;
            }
        }

        return $counts;
    }

    private function is_ecf_native_class($id, array $item) {
        $label = sanitize_key($item['label'] ?? '');
        $tracked = $this->get_synced_class_labels();

        if (strpos((string) $id, 'g-ecf-') === 0) {
            return true;
        }

        return $label !== '' && isset($tracked[$label]);
    }

    private function native_class_category(array $item) {
        $label = strtolower($item['label'] ?? '');
        $starter_categories = $this->get_starter_class_category_map();
        $utility_categories = $this->get_utility_class_category_map();

        if (isset($starter_categories[$label])) {
            return 'class';
        }

        if (isset($utility_categories[$label])) {
            return $utility_categories[$label] === 'accessibility' ? 'layout' : $utility_categories[$label];
        }

        if (strpos($label, 'ecf-p') === 0 || strpos($label, 'ecf-m') === 0 || strpos($label, 'ecf-gap-') === 0) {
            return 'spacing';
        }
        if (strpos($label, 'ecf-text-') === 0 || strpos($label, 'ecf-font-') === 0 || strpos($label, 'ecf-weight-') === 0) {
            return 'typography';
        }
        if (strpos($label, 'ecf-radius-') === 0) {
            return 'radius';
        }
        if (strpos($label, 'ecf-shadow-') === 0) {
            return 'shadow';
        }
        if (strpos($label, 'ecf-flex') === 0 || strpos($label, 'ecf-grid') === 0 || strpos($label, 'ecf-stack') === 0 || strpos($label, 'ecf-wrap') === 0 || strpos($label, 'ecf-items-') === 0 || strpos($label, 'ecf-justify-') === 0) {
            return 'layout';
        }

        return 'class';
    }

    private function native_class_preview_value(array $item) {
        $label = sanitize_key($item['label'] ?? '');
        $variants = $item['variants'] ?? [];
        if (!is_array($variants) || empty($variants)) {
            return isset($this->get_starter_class_category_map()[$label])
                ? __('Starter class', 'ecf-framework')
                : '—';
        }

        $variant = $variants[0] ?? [];
        $props = $variant['props'] ?? [];
        if (!is_array($props) || empty($props)) {
            return isset($this->get_starter_class_category_map()[$label])
                ? __('Starter class', 'ecf-framework')
                : '—';
        }

        $parts = [];
        foreach ($props as $prop_name => $prop_value) {
            $parts[] = $prop_name . ': ' . $this->native_class_prop_to_string($prop_value);
        }

        return implode('; ', $parts);
    }

    private function native_class_prop_to_string($prop_value) {
        if (!is_array($prop_value)) {
            return is_scalar($prop_value) ? (string) $prop_value : '—';
        }

        $type = $prop_value['$$type'] ?? '';
        $value = $prop_value['value'] ?? null;

        if ($type === 'size' && is_array($value)) {
            $size = $value['size'] ?? '';
            $unit = $value['unit'] ?? '';
            return $size . $unit;
        }

        if (($type === 'string' || $type === 'color') && is_scalar($value)) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return wp_json_encode($prop_value);
    }

    private function cleanup_native_classes() {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            throw new \Exception('Elementor global classes repository not available.');
        }
        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $order = $current['order'] ?? [];
        $deleted = 0;

        foreach ($items as $id => $item) {
            if ($this->is_ecf_native_class($id, is_array($item) ? $item : [])) {
                unset($items[$id]);
                $order = array_values(array_filter($order, fn($o) => $o !== $id));
                $deleted++;
            }
        }

        $repo->put($items, $order);
        delete_option($this->synced_class_labels_option_name());
        $this->clear_elementor_sync_caches();
        return $deleted;
    }

    private function get_native_class_cleanup_count() {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            return 0;
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        $count = 0;

        foreach ($items as $id => $item) {
            if ($this->is_ecf_native_class($id, is_array($item) ? $item : [])) {
                $count++;
            }
        }

        return $count;
    }

    private function get_native_global_class_total_count() {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            return 0;
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];

        return is_array($items) ? count($items) : 0;
    }

    private function get_native_global_class_labels() {
        if (!class_exists('\Elementor\Modules\GlobalClasses\Global_Classes_Repository')) {
            return [];
        }

        $repo = \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->context(\Elementor\Modules\GlobalClasses\Global_Classes_Repository::CONTEXT_FRONTEND);
        $current = $repo->all()->get();
        $items = $current['items'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $labels = [];
        foreach ($items as $item) {
            $label = sanitize_key($item['label'] ?? '');
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return array_values(array_unique($labels));
    }

}
