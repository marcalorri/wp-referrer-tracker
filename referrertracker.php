<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: ReferrerTracker
 * Description: Adds ReferrerTracker tracking script and helps populate tracking fields in supported form plugins.
 * Version: 0.1.4
 * Author: ReferrerTracker
 * License: GPLv2 or later
 * Text Domain: referrertracker
 */

define( 'REFERRERTRACKER_VERSION', '0.1.4' );
define( 'REFERRERTRACKER_PLUGIN_FILE', __FILE__ );
define( 'REFERRERTRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REFERRERTRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'REFERRERTRACKER_OPTION_KEY', 'referrertracker_options' );

define( 'REFERRERTRACKER_GITHUB_OWNER', 'marcalorri' );
define( 'REFERRERTRACKER_GITHUB_REPO', 'wp-referrer-tracker' );
define( 'REFERRERTRACKER_PLUGIN_SLUG', 'referrertracker' );

require_once REFERRERTRACKER_PLUGIN_DIR . 'includes/frontend.php';
require_once REFERRERTRACKER_PLUGIN_DIR . 'includes/admin.php';
require_once REFERRERTRACKER_PLUGIN_DIR . 'includes/updater.php';
require_once REFERRERTRACKER_PLUGIN_DIR . 'includes/gravityforms.php';

referrertracker_frontend_register();
referrertracker_admin_register();
referrertracker_updater_register();
referrertracker_gravityforms_register();

register_activation_hook( __FILE__, 'referrertracker_activation' );
