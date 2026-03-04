<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function referrertracker_gravityforms_register() {
	if ( ! class_exists( 'GFForms' ) ) {
		return;
	}

	add_filter( 'gform_field_value', 'referrertracker_gform_field_value', 10, 3 );
}

function referrertracker_gform_field_value( $value, $field, $name ) {
	if ( ! is_string( $name ) || $name === '' ) {
		return $value;
	}

	$cookie_name = referrertracker_map_gravity_parameter_to_cookie_name( $name );
	if ( $cookie_name === '' ) {
		return $value;
	}

	if ( empty( $_COOKIE[ $cookie_name ] ) ) {
		return $value;
	}

	$raw = $_COOKIE[ $cookie_name ];
	if ( is_array( $raw ) ) {
		return $value;
	}

	return sanitize_text_field( wp_unslash( (string) $raw ) );
}

function referrertracker_map_gravity_parameter_to_cookie_name( $parameter_name ) {
	$param = strtolower( trim( (string) $parameter_name ) );
	if ( $param === '' ) {
		return '';
	}

	if ( strpos( $param, 'rt_' ) === 0 ) {
		return $param;
	}

	if ( strpos( $param, 'js-rt-' ) === 0 ) {
		$suffix = substr( $param, strlen( 'js-rt-' ) );
		$suffix = str_replace( '-', '_', $suffix );
		return 'rt_' . $suffix;
	}

	if ( strpos( $param, 'rt-' ) === 0 ) {
		$suffix = substr( $param, strlen( 'rt-' ) );
		$suffix = str_replace( '-', '_', $suffix );
		return 'rt_' . $suffix;
	}

	return '';
}
