<?php

/*
Plugin Name: Regolith - Base
Description: Functionality required for Regolith to work
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Base;
defined( 'WPINC' ) || die();

initialize();


/**
 * Register hook callbacks and other initialization tasks
 */
function initialize() {
	/*
	 * Fix URLs on Multisite
	 *
	 * See https://github.com/roots/bedrock/issues/250
	 * See https://core.trac.wordpress.org/ticket/36507
	 */
	if ( is_multisite() ) {
		add_filter( 'option_home',      __NAMESPACE__ . '\fix_home_url'                );
		add_filter( 'option_siteurl',   __NAMESPACE__ . '\fix_site_url'                );
		add_filter( 'network_site_url', __NAMESPACE__ . '\fix_network_site_url', 10, 3 );
	}

	if ( is_network_admin() ) {
		add_filter( 'https_ssl_verify',       __NAMESPACE__ . '\allow_dev_network_upgrades' );
		add_filter( 'https_local_ssl_verify', __NAMESPACE__ . '\allow_dev_network_upgrades' );
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		register_wp_cli_commands();
	}

	load_site_specific_mu_plugins();
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
 * @param string $value the unchecked home URL.
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
 * @param string $value the unchecked site URL.
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
 * @param string $url    The unchecked network site URL with path appended.
 * @param string $path   The path for the URL.
 * @param string $scheme The URL scheme.
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

/**
 * Enable the Upgrade Network wizard in development environments
 *
 * The wizard will make HTTP requests to each site in the network as part of the upgrade process, and by default
 * it will verify their SSL certificates while making the request. The sites only have self-signed certificates
 * in development environments, so that verification will fail, and the wizard will abort.
 *
 * To avoid that, we disable verification for this specific case, but leave it enabled for all others. Because
 * SSL verification is a critical security function, it's important to only disable it in dev environments.
 *
 * @param bool $verify
 *
 * @return bool
 */
function allow_dev_network_upgrades( $verify ) {
	$is_network_upgrade = '/wordpress/wp-admin/network/upgrade.php' === $_SERVER['SCRIPT_NAME'];
	$is_network_upgrade = $is_network_upgrade && isset( $_GET['action'] ) && 'upgrade' === $_GET['action'];

	if ( 'development' === REGOLITH_ENVIRONMENT && $is_network_upgrade ) {
		$verify = false;
	}

	return $verify;
}

/**
 * Load mu-plugins for individual sites
 */
function load_site_specific_mu_plugins() {
	global $current_blog;

	if ( ! is_multisite() || empty( $current_blog->domain ) ) {
		return;
	}

	// Strip the TLD because dev and production sites have different TLDs (e.g., regolith-example.org and regolith-example.localhost).
	$second_level_domain = substr( $current_blog->domain, 0, strrpos( $current_blog->domain, '.' ) );
	$plugin_folder       = sprintf( '%s/sites/%s', __DIR__, $second_level_domain );
	$plugins             = glob( $plugin_folder . '/*.php' );

	if ( is_array( $plugins ) ) {
		foreach ( $plugins as $plugin ) {
			if ( is_file( $plugin ) ) {
				require_once( $plugin );
			}
		}
	}
}
