<?php
/**
 * Plugin Name: Referrer Tracker for Forms
 * Plugin URI: https://github.com/marcalorri/referrer-tracker
 * Description: Track and store referrer information in your WordPress forms (Contact Form 7, WPForms and Gravity Forms) and CMS
 * Version: 1.5.2
 * Author: Marçal Orri
 * Author URI: https://github.com/marcalorri
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: referrer-tracker-for-forms
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REFETRFO_VERSION', '1.5.2');
define('REFETRFO_PLUGIN_FILE', __FILE__);
define('REFETRFO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REFETRFO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include main plugin class
require_once REFETRFO_PLUGIN_DIR . 'includes/class-referrer-tracker.php';

/**
 * Initialize the plugin
 *
 * @return Refetrfo_Referrer_Tracker The plugin instance
 */
function refetrfo_referrer_tracker_init() {
    return Refetrfo_Referrer_Tracker::get_instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'refetrfo_referrer_tracker_init');
