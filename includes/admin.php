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
	add_action( 'admin_init', 'referrertracker_maybe_redirect_to_settings' );
	add_filter( 'plugin_action_links_' . plugin_basename( REFERRERTRACKER_PLUGIN_FILE ), 'referrertracker_plugin_action_links' );
	add_action( 'admin_notices', 'referrertracker_admin_notices' );
}

function referrertracker_activation() {
	set_transient( 'referrertracker_activation_redirect', 1, 30 );
}

function referrertracker_maybe_redirect_to_settings() {
	if ( ! get_transient( 'referrertracker_activation_redirect' ) ) {
		return;
	}

	delete_transient( 'referrertracker_activation_redirect' );

	if ( is_network_admin() ) {
		return;
	}

	if ( isset( $_GET['activate-multi'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=referrertracker' ) );
	exit;
}

function referrertracker_plugin_action_links( $links ) {
	if ( ! is_array( $links ) ) {
		$links = array();
	}

	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=referrertracker' ) ) . '">Settings</a>';
	array_unshift( $links, $settings_link );

	return $links;
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

	$tab = referrertracker_get_admin_tab();

	echo '<div class="wrap">';
	echo '<h1>ReferrerTracker</h1>';

	referrertracker_render_general_instructions();
	referrertracker_render_tabs( $tab );
	referrertracker_render_tab_content( $tab );

	echo '</div>';
}

function referrertracker_get_admin_tab() {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'settings';
	$allowed = array( 'settings', 'wpforms', 'gravityforms', 'cf7' );
	if ( ! in_array( $tab, $allowed, true ) ) {
		$tab = 'settings';
	}
	return $tab;
}

function referrertracker_render_general_instructions() {
	$dashboard_url = 'https://www.referrertracker.com/dashboard';
	$docs_url = 'https://www.referrertracker.com/es/docs';
	$implementation_url = 'https://www.referrertracker.com/es/soporte/implementacion';

	echo '<div class="notice notice-info" style="padding: 12px 12px 8px;">';
	echo '<p><strong>Quick setup</strong></p>';
	echo '<ol style="margin-top: 8px;">';
	echo '<li>Get your API Key in your ReferrerTracker dashboard: <a href="' . esc_url( $dashboard_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $dashboard_url ) . '</a></li>';
	echo '<li>Paste it in the Settings tab below and click Save</li>';
	echo '<li>Add hidden fields to your forms to capture the parameters you need (see each form tab)</li>';
	echo '</ol>';
	echo '<p style="margin-top: 8px;">Documentation: <a href="' . esc_url( $docs_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $docs_url ) . '</a><br />Implementation guide: <a href="' . esc_url( $implementation_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $implementation_url ) . '</a></p>';
	echo '</div>';
}

function referrertracker_render_tabs( $active_tab ) {
	$base_url = admin_url( 'options-general.php?page=referrertracker' );
	$tabs = array(
		'settings'     => 'Settings',
		'wpforms'      => 'WPForms',
		'gravityforms' => 'Gravity Forms',
		'cf7'          => 'Contact Form 7',
	);

	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $tab => $label ) {
		$url = add_query_arg( 'tab', $tab, $base_url );
		$class = ( $active_tab === $tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
		echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</h2>';
}

function referrertracker_render_tab_content( $tab ) {
	if ( $tab === 'settings' ) {
		echo '<form action="options.php" method="post">';
		settings_fields( 'referrertracker' );
		do_settings_sections( 'referrertracker' );
		submit_button();
		echo '</form>';
		return;
	}

	if ( $tab === 'wpforms' ) {
		referrertracker_render_wpforms_instructions();
		return;
	}

	if ( $tab === 'gravityforms' ) {
		referrertracker_render_gravityforms_instructions();
		return;
	}

	if ( $tab === 'cf7' ) {
		referrertracker_render_cf7_instructions();
		return;
	}
}

function referrertracker_render_wpforms_instructions() {
	echo '<h2>WPForms</h2>';
	echo '<p>WPForms sometimes places your "Field CSS Class" on a wrapper element instead of the actual <code>&lt;input&gt;</code>. This plugin includes a bridge that copies <code>js-rt-*</code> classes to the input so ReferrerTracker can fill the values.</p>';

	echo '<ol>';
	echo '<li>Add Hidden Fields for the parameters you want to capture</li>';
	echo '<li>For each hidden field, set <strong>CSS Class</strong> to one of:</li>';
	echo '</ol>';

	echo '<p><code>js-rt-source</code>, <code>js-rt-medium</code>, <code>js-rt-campaign</code>, <code>js-rt-content</code>, <code>js-rt-term</code>, <code>js-rt-referrer</code>, <code>js-rt-landing-page</code>, <code>js-rt-gclid</code>, <code>js-rt-fbclid</code>, <code>js-rt-msclkid</code>, <code>js-rt-ttclid</code>, <code>js-rt-li-fat-id</code>, <code>js-rt-twclid</code>, <code>js-rt-epik</code>, <code>js-rt-rdt-cid</code></p>';

	echo '<p>Tip: Hidden fields can also be filled by ID (recommended). Use IDs like <code>rt-source</code> or <code>rt-gclid</code>.</p>';
}

function referrertracker_render_gravityforms_instructions() {
	echo '<h2>Gravity Forms</h2>';
	echo '<p>Gravity Forms supports dynamic population. This plugin reads ReferrerTracker cookies and provides values when a field is configured to populate dynamically.</p>';

	echo '<ol>';
	echo '<li>Add Hidden Fields for the parameters you want to capture</li>';
	echo '<li>Enable <strong>Allow field to be populated dynamically</strong></li>';
	echo '<li>Set <strong>Parameter Name</strong> to one of:</li>';
	echo '</ol>';

	echo '<p><code>rt_source</code>, <code>rt_medium</code>, <code>rt_campaign</code>, <code>rt_content</code>, <code>rt_term</code>, <code>rt_referrer</code>, <code>rt_landing_page</code>, <code>rt_gclid</code>, <code>rt_fbclid</code>, <code>rt_msclkid</code>, <code>rt_ttclid</code>, <code>rt_li_fat_id</code>, <code>rt_twclid</code>, <code>rt_epik</code>, <code>rt_rdt_cid</code></p>';

	echo '<p>You can also use class-style names and the plugin will map them (example: <code>js-rt-referrer</code>).</p>';
}

function referrertracker_render_cf7_instructions() {
	echo '<h2>Contact Form 7</h2>';
	echo '<p>Contact Form 7 typically renders the class on the input itself, so ReferrerTracker can fill values without additional changes.</p>';

	echo '<p>Recommended approach:</p>';
	echo '<ol>';
	echo '<li>Add hidden fields and set their IDs to <code>rt-source</code>, <code>rt-medium</code>, <code>rt-campaign</code>, etc.</li>';
	echo '<li>Alternatively use names like <code>rt_source</code> or classes like <code>js-rt-source</code></li>';
	echo '</ol>';
}
