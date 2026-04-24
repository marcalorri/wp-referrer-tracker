<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function referrertracker_get_options() {
	$defaults = array(
		'apiKey'         => '',
		'cookieDuration' => 30,
		'debug'          => false,
	);

	$stored = get_option( REFERRERTRACKER_OPTION_KEY );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_merge( $defaults, $stored );
}

function referrertracker_frontend_register() {
	add_action( 'wp_enqueue_scripts', 'referrertracker_enqueue_scripts', 5 );
	add_filter( 'script_loader_tag', 'referrertracker_script_loader_tag', 10, 2 );
}

function referrertracker_script_loader_tag( $tag, $handle ) {
	if ( 'referrertracker-core' !== $handle && 'referrertracker-bridge' !== $handle ) {
		return $tag;
	}

	$attrs = ' data-cfasync="false" data-no-optimize="1" data-no-minify="1"';
	return str_replace( ' src=', $attrs . ' src=', $tag );
}

function referrertracker_enqueue_scripts() {
	$options = referrertracker_get_options();
	$api_key = isset( $options['apiKey'] ) ? (string) $options['apiKey'] : '';

	if ( $api_key === '' ) {
		return;
	}

	$core_rel = 'assets/referrer-tracker.min.js';
	$core_path = REFERRERTRACKER_PLUGIN_DIR . $core_rel;
	$core_url  = REFERRERTRACKER_PLUGIN_URL . $core_rel;

	$core_ver = REFERRERTRACKER_VERSION;
	if ( file_exists( $core_path ) ) {
		$core_ver = (string) filemtime( $core_path );
	}

	wp_enqueue_script(
		'referrertracker-core',
		$core_url,
		array(),
		$core_ver,
		false
	);

	$bridge_rel  = 'assets/referrertracker-wp-bridge.js';
	$bridge_path = REFERRERTRACKER_PLUGIN_DIR . $bridge_rel;
	$bridge_url  = REFERRERTRACKER_PLUGIN_URL . $bridge_rel;

	$bridge_ver = REFERRERTRACKER_VERSION;
	if ( file_exists( $bridge_path ) ) {
		$bridge_ver = (string) filemtime( $bridge_path );
	}

	wp_enqueue_script(
		'referrertracker-bridge',
		$bridge_url,
		array(),
		$bridge_ver,
		false
	);

	$duration = 30;
	if ( isset( $options['cookieDuration'] ) ) {
		$duration = absint( $options['cookieDuration'] );
		if ( $duration < 1 ) {
			$duration = 30;
		}
		if ( $duration > 90 ) {
			$duration = 90;
		}
	}

	$debug = ! empty( $options['debug'] );

	$config = array(
		'apiKey'           => $api_key,
		'storageExpireDays' => $duration,
		'debug'            => $debug,
	);

	$inline = "window.addEventListener('DOMContentLoaded', function () {\n" .
		"  if (!window.ReferrerTracker || typeof window.ReferrerTracker.configure !== 'function') { return; }\n" .
		"  window.ReferrerTracker.configure(" . wp_json_encode( $config ) . ");\n" .
		"});";

	wp_add_inline_script( 'referrertracker-core', $inline, 'after' );
}
