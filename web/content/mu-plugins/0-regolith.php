<?php

/*
Plugin Name: Regolith - Base
Description: Functionality required for Regolith to work
Version:     0.1
Author:      Ian Dunn
Author URI:  http://iandunn.name
*/

namespace Regolith\Base;
defined( 'WPINC' ) or die();

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
    register_theme_directory( ABSPATH . 'wp-content/themes' );
}

if ( is_multisite() ) {
	// See https://github.com/roots/bedrock/issues/250 and https://core.trac.wordpress.org/ticket/36507
	add_filter( 'option_home',      __NAMESPACE__ . '\fix_home_url'                );
	add_filter( 'option_siteurl',   __NAMESPACE__ . '\fix_site_url'                );
	add_filter( 'network_site_url', __NAMESPACE__ . '\fix_network_site_url', 10, 3 );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	register_wp_cli_commands();
}

/**
 * Register all of our custom WP-CLI commands
 */
function register_wp_cli_commands() {
	$commands = glob( REGOLITH_ROOT_DIR . '/bin/wp-cli/multiple-use/*.php' );

	if ( is_array( $commands ) ) {
		foreach ( $commands as $command ) {
			require_once( $command );
		}
	}
}

/**
 * Ensure that home URL does not contain the /wordpress subdirectory.
 *
 * @param string $value the unchecked home URL
 *
 * @return string the verified home URL
 */
function fix_home_url( $value ) {
	if ( '/wordpress' === substr( $value, -10 ) ) {
		$value = substr( $value, 0, -10 );
	}

	return $value;
}

/**
 * Ensure that site URL contains the /wordpress subdirectory.
 *
 * @param string $value the unchecked site URL
 *
 * @return string the verified site URL
 */
function fix_site_url( $value ) {
	if ( '/wordpress' !== substr( $value, -10 ) ) {
		$value .= '/wordpress';
	}

	return $value;
}

/**
 * Ensure that the network site URL contains the /wordpress subdirectory.
 *
 * @param string $url    the unchecked network site URL with path appended
 * @param string $path   the path for the URL
 * @param string $scheme the URL scheme
 *
 * @return string the verified network site URL
 */
function fix_network_site_url( $url, $path, $scheme ) {
	$path = ltrim( $path, '/' );
	$url  = substr( $url, 0, strlen( $url ) - strlen( $path ) );

	if ( 'wordpress/' !== substr( $url, -10 ) ) {
		$url .= 'wordpress/';
	}

	return $url . $path;
}
