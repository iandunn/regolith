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

add_filter( 'upgrader_clear_destination', __NAMESPACE__ . '\upgrader_symlink_compatibility', 11, 3 );    // after \Plugin_Upgrader::delete_old_plugin() | delete_old_theme()

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

/**
 * Make `\WP_Upgrader` compatible with shared dependency symlinks on production
 *
 * On production, Core and 3rd-party plugins/themes live in the `shared` folder, so that they can be accessed by
 * all releases. `web/content/plugins/{foo}` is just a symlink to `shared/web/content/plugins/{foo}`.
 *
 * `\WP_Upgrader` doesn't understand that `{foo}` is just a symlink, and tries to delete it before installing the
 * new version. `\WP_Filesystem_Direct::delete()` fails because `rmdir( $symlink )` returns `false`.
 *
 * That's actually what we want, though, because we don't want to remove the symlink, only the contents of the
 * target directory.
 *
 * `\WP_Upgrader` thinks that failing to delete the directory is a critical error, and would normally abort the
 * rest of the upgrade process, so we don't hook in at the last minute and tell it everything is fine.
 *
 * @param true|\WP_Error $removed
 * @param string         $local_destination
 * @param string         $remote_destination
 *
 * @return true|\WP_Error
 */
function upgrader_symlink_compatibility( $removed, $local_destination, $remote_destination ) {
	if ( 'production' !== REGOLITH_ENVIRONMENT ) {
		return $removed;
	}

	if ( ! is_wp_error( $removed ) || 'remove_old_failed' !== $removed->get_error_code() ) {
		return $removed;
	}

	$potential_dependency = str_replace( REGOLITH_ROOT_DIR, '', $remote_destination );
	$potential_dependency = trim( $potential_dependency, '/' );
	$dependencies         = file_get_contents( REGOLITH_ROOT_DIR . '/.gitignore' );
	$is_dependency        = false !== strpos( $dependencies, $potential_dependency );

	if ( ! $is_dependency ) {
		return $removed;
	}

	return true;
}
