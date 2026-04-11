<?php
/**
 * Plugin Name: Layrix Legacy Update Bridge
 * Description: Compatibility bridge for older Elementor Core Framework installs. Redirects future updates to the Layrix repository.
 * Version: 0.3.4
 * Author: Alexander Kaiser
 * Update URI: https://github.com/alexus-online/layrix
 * Text Domain: ecf-framework
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ECF_FRAMEWORK_FILE')) {
    define('ECF_FRAMEWORK_FILE', __FILE__);
}

// Keep legacy installs updateable by exposing the old root plugin file
// while forwarding update metadata to the renamed Layrix repository.
require_once __DIR__ . '/layrix.php';
