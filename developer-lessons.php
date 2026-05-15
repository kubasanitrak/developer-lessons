<?php
/**
 * Plugin Name: Developer Lessons
 * Plugin URI: https://example.com/developer-lessons
 * Description: Pay-per-post functionality for lesson content with Comgate and Bank Transfer payments.
 * Version: 1.1.6
 * Author: Developer
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: developer-lessons
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DL_VERSION', '1.1.6');
define('DL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Activation hook
 */
function dl_activate() {
    require_once DL_PLUGIN_DIR . 'includes/class-dl-activator.php';
    DL_Activator::activate();
}
register_activation_hook(__FILE__, 'dl_activate');

/**
 * Deactivation hook
 */
function dl_deactivate() {
    require_once DL_PLUGIN_DIR . 'includes/class-dl-deactivator.php';
    DL_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'dl_deactivate');

/**
 * Load plugin files
 */
require_once DL_PLUGIN_DIR . 'includes/class-dl-loader.php';

/**
 * Initialize the plugin
 */
function dl_init() {
    $loader = new DL_Loader();
    $loader->run();
    // load_plugin_textdomain('developer-lessons', false, dirname(DL_PLUGIN_BASENAME) . '/languages/');
}
add_action('plugins_loaded', 'dl_init');

/**
 * Load text domain
 */
function dl_load_textdomain() {
    load_plugin_textdomain('developer-lessons', false, dirname(DL_PLUGIN_BASENAME) . '/languages/');
}
add_action('init', 'dl_load_textdomain');
