<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function referrertracker_admin_register() {
	if ( ! is_admin() ) {
		return;
	}

	add_action( 'admin_menu', 'referrertracker_admin_menu' );
	add_action( 'admin_init', 'referrertracker_register_settings' );
	add_action( 'admin_notices', 'referrertracker_admin_notices' );
}

function referrertracker_admin_menu() {
	add_options_page(
		'ReferrerTracker',
		'ReferrerTracker',
		'manage_options',
		'referrertracker',
		'referrertracker_render_settings_page'
	);
}

function referrertracker_register_settings() {
	register_setting(
		'referrertracker',
		REFERRERTRACKER_OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'referrertracker_sanitize_options',
			'default'           => array(
				'apiKey'         => '',
				'cookieDuration' => 30,
				'debug'          => false,
			),
		)
	);

	add_settings_section(
		'referrertracker_main',
		'Settings',
		'__return_null',
		'referrertracker'
	);

	add_settings_field(
		'referrertracker_api_key',
		'API Key',
		'referrertracker_field_api_key',
		'referrertracker',
		'referrertracker_main'
	);

	add_settings_field(
		'referrertracker_cookie_duration',
		'Cookie Duration (days)',
		'referrertracker_field_cookie_duration',
		'referrertracker',
		'referrertracker_main'
	);

	add_settings_field(
		'referrertracker_debug',
		'Debug',
		'referrertracker_field_debug',
		'referrertracker',
		'referrertracker_main'
	);
}

function referrertracker_sanitize_options( $input ) {
	$output = array(
		'apiKey'         => '',
		'cookieDuration' => 30,
		'debug'          => false,
	);

	if ( is_array( $input ) ) {
		if ( isset( $input['apiKey'] ) ) {
			$output['apiKey'] = sanitize_text_field( (string) $input['apiKey'] );
		}

		if ( isset( $input['cookieDuration'] ) ) {
			$duration = absint( $input['cookieDuration'] );
			if ( $duration < 1 ) {
				$duration = 30;
			}
			if ( $duration > 90 ) {
				$duration = 90;
			}
			$output['cookieDuration'] = $duration;
		}

		$output['debug'] = ! empty( $input['debug'] );
	}

	return $output;
}

function referrertracker_field_api_key() {
	$options = referrertracker_get_options();
	$value   = isset( $options['apiKey'] ) ? (string) $options['apiKey'] : '';

	echo '<input type="text" class="regular-text" name="' . esc_attr( REFERRERTRACKER_OPTION_KEY ) . '[apiKey]" value="' . esc_attr( $value ) . '" />';
}

function referrertracker_field_cookie_duration() {
	$options = referrertracker_get_options();
	$value   = isset( $options['cookieDuration'] ) ? absint( $options['cookieDuration'] ) : 30;

	echo '<input type="number" min="1" max="90" name="' . esc_attr( REFERRERTRACKER_OPTION_KEY ) . '[cookieDuration]" value="' . esc_attr( (string) $value ) . '" />';
}

function referrertracker_field_debug() {
	$options = referrertracker_get_options();
	$checked = ! empty( $options['debug'] );

	echo '<label><input type="checkbox" name="' . esc_attr( REFERRERTRACKER_OPTION_KEY ) . '[debug]" value="1" ' . checked( $checked, true, false ) . ' /> Enabled</label>';
}

function referrertracker_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || $screen->id !== 'settings_page_referrertracker' ) {
		return;
	}

	$path = REFERRERTRACKER_PLUGIN_DIR . 'assets/referrer-tracker.min.js';
	if ( ! file_exists( $path ) || filesize( $path ) < 10 ) {
		echo '<div class="notice notice-warning"><p>ReferrerTracker: the file <code>assets/referrer-tracker.min.js</code> is missing or empty. Add the script file (downloaded from your ReferrerTracker dashboard) and update the plugin release on GitHub.</p></div>';
	}
}

function referrertracker_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="wrap">';
	echo '<h1>ReferrerTracker</h1>';
	echo '<form action="options.php" method="post">';

	settings_fields( 'referrertracker' );
	do_settings_sections( 'referrertracker' );
	submit_button();

	echo '</form>';
	echo '</div>';
}
