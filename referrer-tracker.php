<?php
/**
 * Plugin Name: Referrer Tracker for Forms and CMS
 * Plugin URI: https://github.com/marcalorri/referrer-tracker
 * Description: Track and store referrer information in your WordPress forms (Contact Form 7, WPForms) and CMS
 * Version: 1.5.2
 * Author: Marçal Orri
 * Author URI: https://github.com/marcalorri
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: referrer-tracker
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RT_VERSION', '1.5.2');
define('RT_PLUGIN_FILE', __FILE__);
define('RT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include main plugin class
require_once RT_PLUGIN_DIR . 'includes/class-referrer-tracker.php';

/**
 * Initialize the plugin
 *
 * @return RT_Referrer_Tracker The plugin instance
 */
function rt_referrer_tracker_init() {
    return RT_Referrer_Tracker::get_instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'rt_referrer_tracker_init');
