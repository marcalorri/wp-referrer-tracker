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

	$activate_multi = filter_input( INPUT_GET, 'activate-multi', FILTER_UNSAFE_RAW );
	if ( null !== $activate_multi ) {
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

	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=referrertracker' ) ) . '">' . esc_html__( 'Settings', 'referrertracker' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

function referrertracker_admin_menu() {
	add_options_page(
		__( 'ReferrerTracker', 'referrertracker' ),
		__( 'ReferrerTracker', 'referrertracker' ),
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
		__( 'Settings', 'referrertracker' ),
		'__return_null',
		'referrertracker'
	);

	add_settings_field(
		'referrertracker_api_key',
		__( 'API Key', 'referrertracker' ),
		'referrertracker_field_api_key',
		'referrertracker',
		'referrertracker_main'
	);

	add_settings_field(
		'referrertracker_cookie_duration',
		__( 'Cookie Duration (days)', 'referrertracker' ),
		'referrertracker_field_cookie_duration',
		'referrertracker',
		'referrertracker_main'
	);

	add_settings_field(
		'referrertracker_debug',
		__( 'Debug', 'referrertracker' ),
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

	echo '<label><input type="checkbox" name="' . esc_attr( REFERRERTRACKER_OPTION_KEY ) . '[debug]" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html__( 'Enabled', 'referrertracker' ) . '</label>';
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
		$filename = 'assets/referrer-tracker.min.js';
		$message  = __( 'ReferrerTracker: the file', 'referrertracker' ) . ' <code>' . esc_html( $filename ) . '</code> ' . __( 'is missing or empty. Add the script file (downloaded from your ReferrerTracker dashboard) and update the plugin release on GitHub.', 'referrertracker' );
		echo '<div class="notice notice-warning"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}

function referrertracker_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tab = referrertracker_get_admin_tab();

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'ReferrerTracker', 'referrertracker' ) . '</h1>';

	referrertracker_render_general_instructions();

	echo '<h2>' . esc_html__( 'Settings', 'referrertracker' ) . '</h2>';
	echo '<form action="options.php" method="post">';
	settings_fields( 'referrertracker' );
	do_settings_sections( 'referrertracker' );
	submit_button();
	echo '</form>';

	referrertracker_render_tabs( $tab );
	referrertracker_render_tab_content( $tab );

	echo '</div>';
}

function referrertracker_get_admin_tab() {
	$tab_raw = filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW );
	$tab     = is_string( $tab_raw ) ? sanitize_key( $tab_raw ) : 'wpforms';
	$allowed = array( 'wpforms', 'gravityforms', 'cf7', 'elementor', 'fluentforms', 'ninjaforms' );
	if ( ! in_array( $tab, $allowed, true ) ) {
		$tab = 'wpforms';
	}
	return $tab;
}

function referrertracker_render_general_instructions() {
	$dashboard_url = 'https://www.referrertracker.com/dashboard';
	$docs_url = 'https://www.referrertracker.com/es/docs';
	$implementation_url = 'https://www.referrertracker.com/es/soporte/implementacion';

	$dashboard_link = '<a href="' . esc_url( $dashboard_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $dashboard_url ) . '</a>';
	$docs_link = '<a href="' . esc_url( $docs_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $docs_url ) . '</a>';
	$implementation_link = '<a href="' . esc_url( $implementation_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $implementation_url ) . '</a>';

	echo '<div class="notice notice-info" style="padding: 12px 12px 8px;">';
	echo '<p><strong>' . esc_html__( 'Quick setup', 'referrertracker' ) . '</strong></p>';
	echo '<ol style="margin-top: 8px;">';
	echo '<li>' . esc_html__( 'Get your API Key in your ReferrerTracker dashboard:', 'referrertracker' ) . ' ' . $dashboard_link . '</li>';
	echo '<li>' . esc_html__( 'Paste it in the Settings section below and click Save', 'referrertracker' ) . '</li>';
	echo '<li>' . esc_html__( 'Add hidden fields to your forms to capture the parameters you need (see each form tab)', 'referrertracker' ) . '</li>';
	echo '</ol>';
	echo '<p style="margin-top: 8px;">' . esc_html__( 'Documentation:', 'referrertracker' ) . ' ' . $docs_link . '<br />' . esc_html__( 'Implementation guide:', 'referrertracker' ) . ' ' . $implementation_link . '</p>';
	echo '</div>';
}

function referrertracker_render_tabs( $active_tab ) {
	$base_url = admin_url( 'options-general.php?page=referrertracker' );
	$tabs = array(
		'wpforms'      => __( 'WPForms', 'referrertracker' ),
		'gravityforms' => __( 'Gravity Forms', 'referrertracker' ),
		'cf7'          => __( 'Contact Form 7', 'referrertracker' ),
		'elementor'    => __( 'Elementor Forms', 'referrertracker' ),
		'fluentforms'  => __( 'Fluent Forms', 'referrertracker' ),
		'ninjaforms'   => __( 'Ninja Forms', 'referrertracker' ),
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

	if ( $tab === 'elementor' ) {
		referrertracker_render_elementor_instructions();
		return;
	}

	if ( $tab === 'fluentforms' ) {
		referrertracker_render_fluentforms_instructions();
		return;
	}

	if ( $tab === 'ninjaforms' ) {
		referrertracker_render_ninjaforms_instructions();
		return;
	}
}

function referrertracker_render_wpforms_instructions() {
	echo '<h2>' . esc_html__( 'WPForms', 'referrertracker' ) . '</h2>';
	echo '<p>' . wp_kses_post( __( 'WPForms sometimes places your "Field CSS Class" on a wrapper element instead of the actual <code>&lt;input&gt;</code>. This plugin includes a bridge that copies <code>js-rt-*</code> classes to the input so ReferrerTracker can fill the values.', 'referrertracker' ) ) . '</p>';

	echo '<ol>';
	echo '<li>' . esc_html__( 'Add Hidden Fields for the parameters you want to capture', 'referrertracker' ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'For each hidden field, set <strong>CSS Class</strong> to one of:', 'referrertracker' ) ) . '</li>';
	echo '</ol>';

	echo '<p><code>js-rt-source</code>, <code>js-rt-medium</code>, <code>js-rt-campaign</code>, <code>js-rt-content</code>, <code>js-rt-term</code>, <code>js-rt-referrer</code>, <code>js-rt-landing-page</code>, <code>js-rt-gclid</code>, <code>js-rt-fbclid</code>, <code>js-rt-msclkid</code>, <code>js-rt-ttclid</code>, <code>js-rt-li-fat-id</code>, <code>js-rt-twclid</code>, <code>js-rt-epik</code>, <code>js-rt-rdt-cid</code></p>';

	echo '<p>' . wp_kses_post( __( 'Note: WPForms generates dynamic IDs, so using the CSS classes (<code>js-rt-*</code>) is the recommended approach.', 'referrertracker' ) ) . '</p>';

	echo '<p>' . esc_html__( 'If fields are not detected automatically, add this JavaScript snippet:', 'referrertracker' ) . '</p>';
	echo '<pre style="white-space: pre-wrap;">' . esc_html( "// WPForms - Assign IDs to hidden fields\ndocument.addEventListener('DOMContentLoaded', function() {\n  var mappings = {\n    'rt_source': '.js-rt-source input',\n    'rt_medium': '.js-rt-medium input',\n    'rt_campaign': '.js-rt-campaign input',\n    'rt_gclid': '.js-rt-gclid input',\n    'rt_fbclid': '.js-rt-fbclid input'\n  };\n\n  Object.keys(mappings).forEach(function(id) {\n    var input = document.querySelector(mappings[id]);\n    if (input) input.id = id;\n  });\n});" ) . '</pre>';
}

function referrertracker_render_gravityforms_instructions() {
	echo '<h2>' . esc_html__( 'Gravity Forms', 'referrertracker' ) . '</h2>';
	echo '<p>' . esc_html__( 'Gravity Forms is a powerful premium forms plugin. It supports hidden fields out of the box.', 'referrertracker' ) . '</p>';

	echo '<h3>' . esc_html__( 'Step 1: Add hidden fields', 'referrertracker' ) . '</h3>';
	echo '<ol>';
	echo '<li>' . esc_html__( 'Edit your form in Gravity Forms', 'referrertracker' ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'Add a <strong>Hidden</strong> field from <strong>Advanced Fields</strong>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'In field settings, set <strong>Field Name</strong> to <code>rt_source</code>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'In the <strong>Advanced</strong> tab, set <strong>CSS Class</strong> to <code>js-rt-source</code>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . esc_html__( 'Repeat for each tracking field you need', 'referrertracker' ) . '</li>';
	echo '</ol>';

	echo '<pre style="white-space: pre-wrap;">' . esc_html( "Recommended fields:\n- rt_source (CSS Class: js-rt-source)\n- rt_medium (CSS Class: js-rt-medium)\n- rt_campaign (CSS Class: js-rt-campaign)\n- rt_gclid (CSS Class: js-rt-gclid)\n- rt_fbclid (CSS Class: js-rt-fbclid)\n- rt_landing_page (CSS Class: js-rt-landing_page)\n- rt_referrer (CSS Class: js-rt-referrer)" ) . '</pre>';

	echo '<h3>' . esc_html__( 'Alternative: gform_pre_render', 'referrertracker' ) . '</h3>';
	echo '<p>' . esc_html__( 'You can also add fields dynamically with PHP:', 'referrertracker' ) . '</p>';
	echo '<pre style="white-space: pre-wrap;">' . esc_html( "// Add hidden tracking fields to Gravity Forms\nadd_filter('gform_pre_render', 'add_tracking_fields_to_gf');\n\nfunction add_tracking_fields_to_gf($form) {\n  ?>\n  <script>\n    document.addEventListener('DOMContentLoaded', function() {\n      // Find fields by CSS class\n      var fields = ['source', 'medium', 'campaign', 'gclid', 'fbclid'];\n      fields.forEach(function(field) {\n        var input = document.querySelector('.js-rt-' + field + ' input');\n        if (input) {\n          input.id = 'rt_' + field;\n        }\n      });\n    });\n  </script>\n  <?php\n\n  return $form;\n}" ) . '</pre>';
}

function referrertracker_render_cf7_instructions() {
	echo '<h2>' . esc_html__( 'Contact Form 7', 'referrertracker' ) . '</h2>';
	echo '<p>' . esc_html__( 'Contact Form 7 is one of the most popular WordPress form plugins. Integration is straightforward.', 'referrertracker' ) . '</p>';

	echo '<h3>' . esc_html__( 'Step 1: Add hidden fields to the form', 'referrertracker' ) . '</h3>';
	echo '<p>' . esc_html__( 'Edit your Contact Form 7 form and paste these hidden fields:', 'referrertracker' ) . '</p>';
	echo '<pre style="white-space: pre-wrap;">' . esc_html( '[hidden rt_source id:rt_source] [hidden rt_medium id:rt_medium] [hidden rt_campaign id:rt_campaign] [hidden rt_content id:rt_content] [hidden rt_term id:rt_term] [hidden rt_gclid id:rt_gclid] [hidden rt_fbclid id:rt_fbclid] [hidden rt_landing_page id:rt_landing_page] [hidden rt_referrer id:rt_referrer]' ) . '</pre>';

	echo '<h3>' . esc_html__( 'Step 2: Configure the notification email', 'referrertracker' ) . '</h3>';
	echo '<p>' . esc_html__( 'In the "Mail" tab, add these tags to include tracking data in the email:', 'referrertracker' ) . '</p>';
	echo '<pre style="white-space: pre-wrap;">' . esc_html( '--- Tracking Data --- Source: [rt_source] Medium: [rt_medium] Campaign: [rt_campaign] Landing Page: [rt_landing_page] Google Click ID: [rt_gclid] Facebook Click ID: [rt_fbclid]' ) . '</pre>';

	echo '<p>' . wp_kses_post( __( 'Done! ReferrerTracker will detect fields by their ID (prefix <code>rt_</code>) and fill them automatically.', 'referrertracker' ) ) . '</p>';
}

function referrertracker_render_elementor_instructions() {
	echo '<h2>' . esc_html__( 'Elementor Forms', 'referrertracker' ) . '</h2>';
	echo '<p>' . esc_html__( 'Elementor Pro includes a powerful forms builder. Integration requires a few extra steps.', 'referrertracker' ) . '</p>';

	echo '<h3>' . esc_html__( 'Step 1: Add hidden fields in Elementor', 'referrertracker' ) . '</h3>';
	echo '<ol>';
	echo '<li>' . esc_html__( 'Edit your page with Elementor', 'referrertracker' ) . '</li>';
	echo '<li>' . esc_html__( 'Select the Form widget', 'referrertracker' ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'Add a new field and select type <strong>Hidden</strong>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'In the field <strong>ID</strong>, type: <code>rt_source</code>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . esc_html__( 'Repeat for each tracking field', 'referrertracker' ) . '</li>';
	echo '</ol>';

	echo '<pre style="white-space: pre-wrap;">' . esc_html( "Fields to create in Elementor Forms:\n┌─────────────────┬──────────────────┐\n│ Type            │ ID               │\n├─────────────────┼──────────────────┤\n│ Hidden          │ rt_source         │\n│ Hidden          │ rt_medium         │\n│ Hidden          │ rt_campaign       │\n│ Hidden          │ rt_content        │\n│ Hidden          │ rt_term           │\n│ Hidden          │ rt_gclid          │\n│ Hidden          │ rt_fbclid         │\n│ Hidden          │ rt_landing_page   │\n│ Hidden          │ rt_referrer       │\n└─────────────────┴──────────────────┘" ) . '</pre>';

	echo '<h3>' . esc_html__( 'Step 2: Verify IDs are applied correctly', 'referrertracker' ) . '</h3>';
	echo '<p>' . esc_html__( 'Elementor sometimes modifies IDs. Add this script to ensure compatibility:', 'referrertracker' ) . '</p>';
	$elementor_js = "// Elementor Forms - Compatibility with ReferrerTracker\n" .
		"document.addEventListener('DOMContentLoaded', function() {\n" .
		"  // Elementor uses data-id, we need to map it to id\n" .
		"  var fields = document.querySelectorAll('.elementor-field-group input[type=\\\"hidden\\\"]');\n" .
		"  fields.forEach(function(field) {\n" .
		"    var wrapper = field.closest('.elementor-field-group');\n" .
		"    if (wrapper) {\n" .
		"      var fieldId = wrapper.getAttribute('data-id');\n" .
		"      if (fieldId && fieldId.startsWith('rt_')) {\n" .
		"        field.id = fieldId;\n" .
		"      }\n" .
		"    }\n" .
		"  });\n" .
		"});";
	printf( '<pre style="white-space: pre-wrap;">%s</pre>', esc_html( $elementor_js ) );

	echo '<h3>' . esc_html__( 'Step 3: Configure form actions', 'referrertracker' ) . '</h3>';
	echo '<ol>';
	echo '<li>' . esc_html__( 'Go to Actions After Submit', 'referrertracker' ) . '</li>';
	echo '<li>' . esc_html__( 'If you use Email, hidden fields are included automatically', 'referrertracker' ) . '</li>';
	echo '<li>' . esc_html__( 'If you use Webhook, fields are sent in the JSON payload', 'referrertracker' ) . '</li>';
	echo '</ol>';

	echo '<p>' . esc_html__( 'CRM integrations: if you connect Elementor to HubSpot, Mailchimp, or other CRMs via native actions, tracking fields are sent automatically.', 'referrertracker' ) . '</p>';
}

function referrertracker_render_fluentforms_instructions() {
	echo '<h2>' . esc_html__( 'Fluent Forms', 'referrertracker' ) . '</h2>';
	echo '<p>' . esc_html__( 'Fluent Forms is a lightweight and fast forms plugin. You can add tracking hidden fields using shortcodes.', 'referrertracker' ) . '</p>';

	echo '<pre style="white-space: pre-wrap;">' . esc_html( "// Fluent Forms - Add hidden fields via shortcode\n// Use this shortcode in your form:\n[fluentform_hidden name=\"rt_source\" id=\"rt_source\"] [fluentform_hidden name=\"rt_medium\" id=\"rt_medium\"] [fluentform_hidden name=\"rt_campaign\" id=\"rt_campaign\"] [fluentform_hidden name=\"rt_gclid\" id=\"rt_gclid\"] [fluentform_hidden name=\"rt_fbclid\" id=\"rt_fbclid\"]" ) . '</pre>';
}

function referrertracker_render_ninjaforms_instructions() {
	echo '<h2>' . esc_html__( 'Ninja Forms', 'referrertracker' ) . '</h2>';
	echo '<p>' . esc_html__( 'Ninja Forms supports hidden fields natively. Use a field key and CSS class so ReferrerTracker can populate values.', 'referrertracker' ) . '</p>';

	echo '<ol>';
	echo '<li>' . wp_kses_post( __( 'Add a <strong>Hidden</strong> field to your form', 'referrertracker' ) ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'In <strong>Administration</strong> → <strong>Field Key</strong>, type: <code>rt_source</code>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . wp_kses_post( __( 'In <strong>Display</strong> → <strong>Custom CSS Classes</strong>, add: <code>js-rt-source</code>', 'referrertracker' ) ) . '</li>';
	echo '<li>' . esc_html__( 'Repeat for each tracking field you need', 'referrertracker' ) . '</li>';
	echo '</ol>';
}
