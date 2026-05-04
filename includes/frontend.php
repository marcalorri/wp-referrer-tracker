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
	add_action( 'init', 'referrertracker_add_wp_rocket_exclusions' );
}

function referrertracker_script_loader_tag( $tag, $handle ) {
	if ( 'referrertracker-core' !== $handle && 'referrertracker-bridge' !== $handle ) {
		return $tag;
	}

	$attrs = ' data-cfasync="false" data-no-optimize="1" data-no-minify="1" data-rocket-ignore';
	return str_replace( ' src=', $attrs . ' src=', $tag );
}

function referrertracker_add_wp_rocket_exclusions() {
	$exclude_patterns = array(
		'referrertracker',
		'rt.min.js',
	);

	foreach ( $exclude_patterns as $pattern ) {
		add_filter( 'rocket_delay_js_exclusions', function ( $exclusions ) use ( $pattern ) {
			if ( is_array( $exclusions ) && ! in_array( $pattern, $exclusions, true ) ) {
				$exclusions[] = $pattern;
			}
			return $exclusions;
		} );

		add_filter( 'rocket_exclude_defer_js', function ( $exclusions ) use ( $pattern ) {
			if ( is_array( $exclusions ) && ! in_array( $pattern, $exclusions, true ) ) {
				$exclusions[] = $pattern;
			}
			return $exclusions;
		} );

		add_filter( 'rocket_exclude_js', function ( $exclusions ) use ( $pattern ) {
			if ( is_array( $exclusions ) && ! in_array( $pattern, $exclusions, true ) ) {
				$exclusions[] = $pattern;
			}
			return $exclusions;
		} );
	}
}

function referrertracker_enqueue_scripts() {
	$options = referrertracker_get_options();
	$api_key = isset( $options['apiKey'] ) ? (string) $options['apiKey'] : '';

	if ( $api_key === '' ) {
		return;
	}

	$core_rel = 'assets/rt.min.js';
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
		array( 'referrertracker-core' ),
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

	$config_json = wp_json_encode( $config );

	$inline = "(function(){" .
		"var c=" . $config_json . ";window.rtQueue=window.rtQueue||[];window.rtQueue.__config=c;" .
		"var a=0;" .
		"var m=50;" .
		"function t(){" .
		"if(window.ReferrerTracker&&typeof window.ReferrerTracker.configure==='function'){" .
		"if(!window.rtQueue||!window.rtQueue.__configured){" .
		"if(window.rtQueue)window.rtQueue.__configured=1;" .
		"window.ReferrerTracker.configure(c);" .
		"if(c.debug)console.log('[ReferrerTracker WP] configure() called via retry after '+(a*100)+'ms');" .
		"}" .
		"return;" .
		"}" .
		"a++;" .
		"if(a>=m){" .
		"try{" .
		"var e=Date.now()+c.storageExpireDays*24*60*60*1000;" .
		"localStorage.setItem('rt_source',JSON.stringify({v:'none',e:e}));" .
		"localStorage.setItem('rt_medium',JSON.stringify({v:'direct',e:e}));" .
		"sessionStorage.setItem('rt_source','none');" .
		"sessionStorage.setItem('rt_medium','direct');" .
		"if(c.debug)console.log('[ReferrerTracker WP] Core not available after '+(a*100)+'ms, wrote defaults to storage');" .
		"}catch(x){}" .
		"return;" .
		"}" .
		"setTimeout(t,100);" .
		"}" .
		"t();" .
		"})();";

	wp_add_inline_script( 'referrertracker-core', $inline, 'after' );
}
