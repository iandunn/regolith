<?php

/*
Plugin Name: Regolith - Updates
Description: Automatically update Core, plugins and themes
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Updates;
defined( 'WPINC' ) || die();

add_filter( 'allow_minor_auto_core_updates',     '__return_true'  );
add_filter( 'allow_major_auto_core_updates',     '__return_true'  );
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false' ); // See note in auto_update_valid_dependencies().

add_action( 'init',                          __NAMESPACE__ . '\schedule_cron_jobs'                         );
add_filter( 'site_transient_update_plugins', __NAMESPACE__ . '\block_updates_for_custom_extensions'        );
add_filter( 'site_transient_update_themes',  __NAMESPACE__ . '\block_updates_for_custom_extensions'        );
add_filter( 'auto_update_plugin',            __NAMESPACE__ . '\auto_update_valid_dependencies',      10, 2 );
add_filter( 'auto_update_theme',             __NAMESPACE__ . '\auto_update_valid_dependencies',      10, 2 );
add_filter( 'upgrader_clear_destination',    __NAMESPACE__ . '\upgrader_symlink_compatibility',      11, 3 );    // after Plugin_Upgrader::delete_old_plugin().


/**
 * Schedule WP-Cron jobs
 */
function schedule_cron_jobs() {
	if ( wp_installing() ) {
		return;
	}

	// Install updates every hour, to minimize the window where a known vulnerability is active.
	if ( ! wp_next_scheduled( 'wp_maybe_auto_update' ) ) {
		wp_schedule_event( time(), 'hourly', 'wp_maybe_auto_update' );
	}
}

/**
 * Block WordPress.org updates for custom plugins and themes
 *
 * If the WordPress.org repository has a plugin or theme with the exact same slug as one of our custom plugins or
 * themes, and the extension in the repository has a higher version number, then WP Upgrader will overwrite the
 * custom extension with the one from the WordPress.org repository.
 *
 * See https://core.trac.wordpress.org/ticket/32101
 * See https://core.trac.wordpress.org/ticket/10814
 *
 * @param object $dependencies
 *
 * @return object
 */
function block_updates_for_custom_extensions( $dependencies ) {
	if ( empty( $dependencies->response ) ) {
		return $dependencies;
	}

	foreach ( $dependencies->response as $slug => $details ) {
		if ( 'site_transient_update_plugins' === current_filter() ) {
			$dependency_path = '/plugins/' . dirname( $slug );
		} else {
			// Don't block updates for Core themes.
			if ( is_core_theme( $slug ) ) {
				continue;
			}

			$dependency_path = '/themes/' . $slug;
		}

		if ( ! is_dependency( $dependency_path ) ) {
			unset( $dependencies->response[ $slug ] );
		}
	}

	return $dependencies;
}

/**
 * Determine if the given theme is a Core-bundled theme
 *
 * Core's API doesn't expose which themes were bundled and which were installed, but we know that
 * Regolith's custom content directory is `content` rather than `wp-content`, so we can use that to distinguish
 * between bundled and installed themes.
 *
 * @param string $slug
 *
 * @return bool
 */
function is_core_theme( $slug ) {
	$theme = wp_get_theme( $slug );

	return false !== strpos( $theme->get_theme_root(), 'wordpress/wp-content/themes' );
}

/**
 * Enable automatic updates for registered plugin/theme dependencies
 *
 * This is only for 3rd party dependencies, not for custom plugins and themes.
 *
 * By default, WP_Automatic_Updater refuses to update anything that it thinks is tracked in a version control
 * system. It doesn't detect that on a per-plugin or per-theme basis, though, it just looks thinks everything is
 * version-controlled if it finds a VCS folder anywhere in the tree. In order to get around that and automatically
 * update dependencies, `automatic_updates_is_vcs_checkout` is always set to `false`.
 *
 * Always setting that to `false` introduces the potential for conflicts with plugins/themes in the WordPress.org
 * directories, though.
 *
 * @param bool   $should_update
 * @param object $dependency
 *
 * @return bool
 */
function auto_update_valid_dependencies( $should_update, $dependency ) {
	$dependency_slug = isset( $dependency->plugin ) ? 'plugins/' . dirname( $dependency->plugin ) : "themes/{$dependency->theme}";

	return is_dependency( $dependency_slug );
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
 * rest of the upgrade process, so we hook in at the last minute and tell it everything is fine.
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

	if ( ! is_dependency( $remote_destination ) ) {
		return $removed;
	}

	return true;
}

/**
 * Check if a given path is one of our registered dependencies
 *
 * @param string $path
 *
 * @return bool
 */
function is_dependency( $path ) {
	$potential_dependency = str_replace( REGOLITH_ROOT_DIR, '', $path );
	$potential_dependency = trim( $potential_dependency, '/' );
	$dependencies         = file_get_contents( REGOLITH_ROOT_DIR . '/.gitignore' );

	return false !== strpos( $dependencies, $potential_dependency );
}
