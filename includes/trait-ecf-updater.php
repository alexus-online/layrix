<?php

if (!defined('ABSPATH')) {
    exit;
}

trait ECF_Framework_Updater_Trait {
    private function plugin_basename() {
        return plugin_basename(ECF_FRAMEWORK_FILE);
    }

    private function plugin_slug() {
        return dirname($this->plugin_basename());
    }

    private function canonical_plugin_slug() {
        return $this->canonical_plugin_slug;
    }

    private function auto_updates_enabled() {
        $plugins = (array) get_site_option('auto_update_plugins', []);
        return in_array($this->plugin_basename(), $plugins, true);
    }

    private function plugin_is_active() {
        if (function_exists('is_plugin_active')) {
            return is_plugin_active($this->plugin_basename());
        }

        $active_plugins = (array) get_option('active_plugins', []);
        if (in_array($this->plugin_basename(), $active_plugins, true)) {
            return true;
        }

        if (is_multisite()) {
            $network_active_plugins = array_keys((array) get_site_option('active_sitewide_plugins', []));
            return in_array($this->plugin_basename(), $network_active_plugins, true);
        }

        return false;
    }

    private function active_state_cache_key() {
        return 'ecf_framework_was_active_before_update';
    }

    private function clear_registered_plugin_update_state() {
        delete_site_transient($this->update_cache_key);

        $transient = get_site_transient('update_plugins');
        if (!is_object($transient)) {
            return;
        }

        $plugin_file = $this->plugin_basename();
        if (isset($transient->response) && is_array($transient->response)) {
            unset($transient->response[$plugin_file]);
        }
        if (isset($transient->no_update) && is_array($transient->no_update)) {
            unset($transient->no_update[$plugin_file]);
        }

        set_site_transient('update_plugins', $transient);
    }

    private function github_token() {
        if (defined('ECF_GITHUB_TOKEN') && is_string(ECF_GITHUB_TOKEN) && ECF_GITHUB_TOKEN !== '') {
            return ECF_GITHUB_TOKEN;
        }

        return '';
    }

    private function has_github_token() {
        return $this->github_token() !== '';
    }

    private function github_update_checks_enabled($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        return !empty($settings['github_update_checks_enabled']);
    }

    private function github_api_url($path) {
        return 'https://api.github.com/repos/' . $this->github_repo . '/' . ltrim($path, '/');
    }

    private function github_contents_api_url($path) {
        return add_query_arg(
            ['ref' => $this->github_branch],
            $this->github_api_url('contents/' . ltrim($path, '/'))
        );
    }

    private function github_raw_content_url($path) {
        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s',
            $this->github_repo,
            $this->github_branch,
            ltrim($path, '/')
        );
    }

    private function github_file_url($path) {
        if ($this->has_github_token()) {
            return $this->github_contents_api_url($path);
        }

        return $this->github_raw_content_url($path);
    }

    private function github_raw_plugin_url() {
        return $this->github_file_url(basename(ECF_FRAMEWORK_FILE));
    }

    private function github_raw_changelog_url() {
        return $this->github_file_url('CHANGELOG.md');
    }

    private function github_package_url() {
        if ($this->has_github_token()) {
            return $this->github_api_url('zipball/' . $this->github_branch);
        }

        return sprintf(
            'https://codeload.github.com/%s/zip/refs/heads/%s',
            $this->github_repo,
            $this->github_branch
        );
    }

    private function github_request_args() {
        $args = [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'ECF-Framework-Updater',
            ],
        ];

        if ($this->has_github_token()) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->github_token();
        }

        return $args;
    }

    private function extract_github_file_body($response) {
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }

        $body = (string) wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        if (is_array($json) && !empty($json['content'])) {
            $content = base64_decode(str_replace("\n", '', (string) $json['content']), true);
            return $content === false ? '' : $content;
        }

        return $body;
    }

    private function probe_github_plugin_version() {
        $response = wp_remote_get($this->github_raw_plugin_url(), $this->github_request_args());

        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'status' => 0,
                'version' => '',
                'error' => $response->get_error_message(),
            ];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = $this->extract_github_file_body($response);

        if ($status !== 200) {
            return [
                'ok' => false,
                'status' => $status,
                'version' => '',
                'error' => 'HTTP ' . $status,
            ];
        }

        if ($body === '') {
            return [
                'ok' => false,
                'status' => $status,
                'version' => '',
                'error' => 'Empty response body',
            ];
        }

        if (!preg_match('/^\s*\*\s*Version:\s*(.+)$/mi', $body, $matches)) {
            return [
                'ok' => false,
                'status' => $status,
                'version' => '',
                'error' => 'Version header not found',
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'version' => trim($matches[1]),
            'error' => '',
        ];
    }

    private function current_plugin_version() {
        return (string) get_file_data(ECF_FRAMEWORK_FILE, ['Version' => 'Version'])['Version'];
    }

    private function build_github_update_payload($plugin_file, $installed_version) {
        $update = $this->get_github_update_data();
        if (!$update) {
            return false;
        }

        if (!version_compare($update['version'], (string) $installed_version, '>')) {
            return false;
        }

        return (object) [
            'id' => 'https://github.com/' . $this->github_repo,
            'slug' => $this->canonical_plugin_slug(),
            'plugin' => $plugin_file,
            'version' => $update['version'],
            'new_version' => $update['version'],
            'package' => $update['package'],
            'url' => $update['homepage'],
            'tested' => $update['tested'],
            'requires' => $update['requires'],
            'requires_php' => PHP_VERSION,
            'icons' => [],
            'banners' => [],
            'banners_rtl' => [],
            'translations' => [],
            'compatibility' => new stdClass(),
            'autoupdate' => $this->auto_updates_enabled(),
        ];
    }

    private function should_refresh_plugin_updates() {
        $last_update = get_site_transient('update_plugins');
        $last_checked = 0;

        if (is_object($last_update) && !empty($last_update->last_checked)) {
            $last_checked = (int) $last_update->last_checked;
        }

        if ((time() - $last_checked) >= HOUR_IN_SECONDS) {
            return true;
        }

        return get_site_transient($this->update_cache_key) === false;
    }

    private function get_available_plugin_update($plugin_file = null, $installed_version = null, $force = false) {
        $plugin_file = $plugin_file ?: $this->plugin_basename();

        if ($installed_version === null || $installed_version === '') {
            $installed_version = $this->current_plugin_version();
        }

        $updates = get_site_transient('update_plugins');
        if (
            !$force &&
            is_object($updates) &&
            !empty($updates->response[$plugin_file]) &&
            is_object($updates->response[$plugin_file])
        ) {
            return (object) $updates->response[$plugin_file];
        }

        return $this->build_github_update_payload($plugin_file, (string) $installed_version);
    }

    private function refresh_plugin_update_transient($force = false) {
        if ($force) {
            delete_site_transient($this->update_cache_key);
        }

        $plugin_file = $this->plugin_basename();
        $installed_version = $this->current_plugin_version();
        $transient = get_site_transient('update_plugins');

        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = [];
        }
        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = [];
        }
        if (!isset($transient->checked) || !is_array($transient->checked)) {
            $transient->checked = [];
        }

        $transient->checked[$plugin_file] = $installed_version;
        $transient->last_checked = time();
        $transient = $this->merge_github_update_into_transient($transient);

        set_site_transient('update_plugins', $transient);

        return $transient;
    }

    private function markdown_to_html($markdown) {
        $markdown = trim((string) $markdown);
        if ($markdown === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $html = '';
        $in_list = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h4>' . esc_html($m[1]) . '</h4>';
                continue;
            }

            if (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h3>' . esc_html($m[1]) . '</h3>';
                continue;
            }

            if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                if ($in_list) {
                    $html .= '</ul>';
                    $in_list = false;
                }
                $html .= '<h2>' . esc_html($m[1]) . '</h2>';
                continue;
            }

            if (preg_match('/^- (.+)$/', $trimmed, $m)) {
                if (!$in_list) {
                    $html .= '<ul>';
                    $in_list = true;
                }
                $html .= '<li>' . esc_html($m[1]) . '</li>';
                continue;
            }

            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }

            $html .= '<p>' . esc_html($trimmed) . '</p>';
        }

        if ($in_list) {
            $html .= '</ul>';
        }

        return $html;
    }

    private function get_github_update_data($force = false) {
        if (!$this->github_update_checks_enabled()) {
            return null;
        }

        if (!$force) {
            $cached = get_site_transient($this->update_cache_key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $plugin_response = wp_remote_get($this->github_raw_plugin_url(), $this->github_request_args());
        $plugin_body = $this->extract_github_file_body($plugin_response);

        if ($plugin_body === '') {
            return null;
        }

        if (!preg_match('/^\s*\*\s*Version:\s*(.+)$/mi', $plugin_body, $matches)) {
            return null;
        }

        $remote_version = trim($matches[1]);
        $changelog_html = '';
        $changelog_response = wp_remote_get($this->github_raw_changelog_url(), $this->github_request_args());
        $changelog_body = $this->extract_github_file_body($changelog_response);

        if ($changelog_body !== '') {
            $changelog_html = $this->markdown_to_html($changelog_body);
        }

        $data = [
            'version' => $remote_version,
            'package' => $this->github_package_url(),
            'homepage' => 'https://github.com/' . $this->github_repo,
            'tested' => get_bloginfo('version'),
            'requires' => '6.0',
            'sections' => [
                'description' => '<p>' . esc_html__('Automatic updates delivered from the GitHub repository.', 'ecf-framework') . '</p>',
                'changelog' => $changelog_html ?: '<p>' . esc_html__('No changelog available.', 'ecf-framework') . '</p>',
            ],
        ];

        set_site_transient($this->update_cache_key, $data, HOUR_IN_SECONDS);

        return $data;
    }

    public function add_github_auth_headers($args, $url) {
        if (!$this->github_update_checks_enabled() || !$this->has_github_token() || !is_string($url)) {
            return $args;
        }

        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $repo_path = '/' . $this->github_repo . '/';
        $is_github_request = in_array($host, ['api.github.com', 'raw.githubusercontent.com', 'codeload.github.com'], true);
        $is_repo_request = strpos($path, $repo_path) !== false || $host === 'api.github.com';

        if (!$is_github_request || !$is_repo_request) {
            return $args;
        }

        if (!isset($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = [];
        }

        $args['headers']['Authorization'] = 'Bearer ' . $this->github_token();

        if ($host === 'api.github.com') {
            $args['headers']['Accept'] = 'application/vnd.github+json';
            $args['headers']['User-Agent'] = 'ECF-Framework-Updater';
        }

        return $args;
    }

    public function inject_github_plugin_update($update, $plugin_data, $plugin_file, $locales) {
        if (!$this->github_update_checks_enabled()) {
            return $update;
        }

        if ($plugin_file !== $this->plugin_basename()) {
            return $update;
        }

        $installed_version = isset($plugin_data['Version']) ? (string) $plugin_data['Version'] : $this->current_plugin_version();
        return $this->build_github_update_payload($plugin_file, $installed_version);
    }

    public function merge_github_update_into_transient($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = [];
        }
        if (!isset($transient->no_update) || !is_array($transient->no_update)) {
            $transient->no_update = [];
        }
        if (!isset($transient->checked) || !is_array($transient->checked)) {
            $transient->checked = [];
        }

        $plugin_file = $this->plugin_basename();
        $installed_version = $transient->checked[$plugin_file] ?? $this->current_plugin_version();

        if (!$this->github_update_checks_enabled()) {
            unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);
            return $transient;
        }

        $payload = $this->build_github_update_payload($plugin_file, $installed_version);

        unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);

        if ($payload) {
            $transient->response[$plugin_file] = $payload;
        } else {
            $transient->no_update[$plugin_file] = (object) [
                'id' => 'https://github.com/' . $this->github_repo,
                'slug' => $this->canonical_plugin_slug(),
                'plugin' => $plugin_file,
                'new_version' => (string) $installed_version,
                'url' => 'https://github.com/' . $this->github_repo,
                'package' => '',
                'icons' => [],
                'banners' => [],
                'banners_rtl' => [],
                'translations' => [],
                'compatibility' => new stdClass(),
                'autoupdate' => $this->auto_updates_enabled(),
            ];
        }

        return $transient;
    }

    public function inject_github_plugin_info($result, $action, $args) {
        if (!$this->github_update_checks_enabled()) {
            return $result;
        }

        if (
            $action !== 'plugin_information' ||
            empty($args->slug) ||
            !in_array($args->slug, [$this->plugin_slug(), $this->canonical_plugin_slug()], true)
        ) {
            return $result;
        }

        $update = $this->get_github_update_data();
        if (!$update) {
            return $result;
        }

        return (object) [
            'name' => 'Layrix',
            'slug' => $this->canonical_plugin_slug(),
            'version' => $update['version'],
            'author' => '<a href="https://github.com/alexus-online">Alexander Kaiser</a>',
            'homepage' => $update['homepage'],
            'download_link' => $update['package'],
            'trunk' => $update['package'],
            'requires' => $update['requires'],
            'tested' => $update['tested'],
            'last_updated' => gmdate('Y-m-d H:i:s'),
            'sections' => $update['sections'],
            'banners' => [],
            'icons' => [],
        ];
    }

    public function maybe_refresh_plugin_updates() {
        if (!$this->github_update_checks_enabled()) {
            return;
        }

        if (!$this->can_manage_framework()) {
            return;
        }

        if (!$this->should_refresh_plugin_updates()) {
            return;
        }

        delete_site_transient('update_plugins');
        $this->refresh_plugin_update_transient(true);
    }

    public function remember_active_state_before_upgrade($response, $hook_extra) {
        if (
            empty($hook_extra['plugin']) ||
            empty($hook_extra['type']) ||
            $hook_extra['type'] !== 'plugin' ||
            $hook_extra['plugin'] !== $this->plugin_basename()
        ) {
            return $response;
        }

        set_site_transient($this->active_state_cache_key(), $this->plugin_is_active(), HOUR_IN_SECONDS);

        return $response;
    }

    public function rename_github_update_source($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;

        if (
            empty($hook_extra['plugin']) ||
            empty($hook_extra['type']) ||
            $hook_extra['type'] !== 'plugin' ||
            $hook_extra['plugin'] !== $this->plugin_basename()
        ) {
            return $source;
        }

        $target = trailingslashit($remote_source) . $this->canonical_plugin_slug();
        if ($source === $target) {
            return $source;
        }

        if ($wp_filesystem && $wp_filesystem->exists($target)) {
            $wp_filesystem->delete($target, true);
        }

        if ($wp_filesystem && $wp_filesystem->move($source, $target, true)) {
            return $target;
        }

        return new \WP_Error(
            'ecf_github_update_source_failed',
            __('Could not prepare the GitHub update package for installation.', 'ecf-framework')
        );
    }

    public function normalize_github_plugin_destination($response, $hook_extra, $result) {
        global $wp_filesystem;

        if (
            empty($hook_extra['plugin']) ||
            empty($hook_extra['type']) ||
            $hook_extra['type'] !== 'plugin' ||
            $hook_extra['plugin'] !== $this->plugin_basename() ||
            empty($result['destination'])
        ) {
            return $response;
        }

        $destination = untrailingslashit($result['destination']);
        $expected = untrailingslashit(WP_PLUGIN_DIR . '/' . $this->canonical_plugin_slug());

        if ($destination === $expected) {
            return $response;
        }

        $plugin_file = trailingslashit($destination) . basename(ECF_FRAMEWORK_FILE);
        if (!$wp_filesystem || !$wp_filesystem->exists($plugin_file)) {
            return $response;
        }

        if ($wp_filesystem->exists($expected)) {
            $wp_filesystem->delete($expected, true);
        }

        if (!$wp_filesystem->move($destination, $expected, true)) {
            return new \WP_Error(
                'ecf_github_update_destination_failed',
                __('Could not normalize the GitHub plugin folder after installation.', 'ecf-framework')
            );
        }

        $result['destination'] = $expected;
        if (is_array($response)) {
            $response['destination'] = $expected;
        }

        return $response;
    }

    public function clear_github_update_cache($upgrader, $hook_extra) {
        if (!empty($hook_extra['type']) && $hook_extra['type'] === 'plugin') {
            delete_site_transient($this->update_cache_key);
        }

        if (
            empty($hook_extra['type']) ||
            $hook_extra['type'] !== 'plugin' ||
            empty($hook_extra['plugins']) ||
            !is_array($hook_extra['plugins']) ||
            !in_array($this->plugin_basename(), $hook_extra['plugins'], true)
        ) {
            return;
        }

        $was_active = (bool) get_site_transient($this->active_state_cache_key());
        delete_site_transient($this->active_state_cache_key());

        if ($was_active && !$this->plugin_is_active() && current_user_can('activate_plugins')) {
            activate_plugin($this->plugin_basename(), '', is_multisite(), true);
        }
    }

    public function handle_check_updates() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('plugins.php'), ['ecf_update_check' => '1']);
        }
        check_admin_referer('ecf_check_updates');

        if (!$this->github_update_checks_enabled()) {
            $message = __('GitHub update checks are disabled in ECF General Settings > System.', 'ecf-framework');
            $this->redirect_with_message(admin_url('plugins.php'), ['ecf_update_check' => '1'], $message);
        }

        $installed_version = $this->current_plugin_version();
        $probe = $this->probe_github_plugin_version();
        delete_site_transient('update_plugins');
        $transient = $this->refresh_plugin_update_transient(true);
        $plugin_file = $this->plugin_basename();
        $update = !empty($transient->response[$plugin_file]) ? (object) $transient->response[$plugin_file] : null;

        if ($update && !empty($update->new_version)) {
            $message = sprintf(
                __('Update found: installed %1$s, remote %2$s.', 'ecf-framework'),
                $installed_version,
                (string) $update->new_version
            );
        } elseif (!$probe['ok']) {
            $message = sprintf(
                __('Plugin update check failed. Installed: %1$s. GitHub request error: %2$s.', 'ecf-framework'),
                $installed_version,
                $probe['error']
            );
        } else {
            $message = sprintf(
                __('Plugin update check completed. Installed: %1$s. GitHub remote: %2$s. No newer version was registered.', 'ecf-framework'),
                $installed_version,
                $probe['version']
            );
        }

        $this->redirect_with_message(admin_url('plugins.php'), ['ecf_update_check' => '1'], $message);
    }

    public function add_plugin_action_links($actions) {
        if (!$this->can_manage_framework() || !$this->github_update_checks_enabled()) {
            return $actions;
        }

        $check_url = wp_nonce_url(
            admin_url('admin-post.php?action=ecf_check_updates'),
            'ecf_check_updates'
        );
        $actions['ecf_check_updates'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($check_url),
            esc_html__('Check for updates', 'ecf-framework')
        );

        return $actions;
    }

    public function render_plugin_auto_update_column($html, $plugin_file, $plugin_data) {
        if ($plugin_file !== $this->plugin_basename() || !$this->can_manage_framework()) {
            return $html;
        }

        if (!$this->github_update_checks_enabled()) {
            return '<span class="description">' . esc_html__('Enable GitHub update checks in ECF > General Settings > System first.', 'ecf-framework') . '</span>';
        }

        $auto_updates_enabled = $this->auto_updates_enabled();
        $toggle_url = wp_nonce_url(
            admin_url('admin-post.php?action=ecf_toggle_auto_updates'),
            'ecf_toggle_auto_updates'
        );

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url($toggle_url),
            esc_html($auto_updates_enabled ? __('Disable auto-updates', 'ecf-framework') : __('Enable auto-updates', 'ecf-framework'))
        );
    }

    public function handle_toggle_auto_updates() {
        if (!$this->can_manage_framework()) {
            $this->deny_admin_request(admin_url('plugins.php'), ['ecf_update_check' => '1']);
        }
        check_admin_referer('ecf_toggle_auto_updates');

        if (!$this->github_update_checks_enabled()) {
            $message = __('GitHub update checks are disabled in ECF General Settings > System.', 'ecf-framework');
            $this->redirect_with_message(admin_url('plugins.php'), ['ecf_update_check' => '1'], $message);
        }

        $plugins = array_values(array_unique((array) get_site_option('auto_update_plugins', [])));
        $plugin_file = $this->plugin_basename();
        $enabled = in_array($plugin_file, $plugins, true);

        if ($enabled) {
            $plugins = array_values(array_filter($plugins, function ($plugin) use ($plugin_file) {
                return $plugin !== $plugin_file;
            }));
        } else {
            $plugins[] = $plugin_file;
            $plugins = array_values(array_unique($plugins));
        }

        update_site_option('auto_update_plugins', $plugins);

        $message = $enabled
            ? __('Automatic updates disabled for this plugin.', 'ecf-framework')
            : __('Automatic updates enabled for this plugin.', 'ecf-framework');

        $this->redirect_with_message(admin_url('plugins.php'), ['ecf_update_check' => '1'], $message);
    }

    public function render_plugin_list_notice() {
        if (!is_admin() || !current_user_can('activate_plugins')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }

        if (empty($_GET['ecf_update_check'])) {
            return;
        }
        $this->render_consumed_admin_notices('plugins', '');
    }
}
