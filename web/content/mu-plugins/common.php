<?php

/*
Plugin Name: Regolith - Common
Description: Functionality common across my sites
Version:     0.1
Author:      Ian Dunn
Author URI:  http://iandunn.name
*/

namespace Regolith\Common_Functionality;
use Regolith\Base;
defined( 'WPINC' ) or die();

add_filter( 'allow_minor_auto_core_updates', '__return_true'  );
add_filter( 'allow_major_auto_core_updates', '__return_true'  );
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false' ); // See note in auto_update_valid_dependencies()
add_filter( 'xmlrpc_enabled',                '__return_false' ); // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

add_action( 'init',                       __NAMESPACE__ . '\schedule_cron_jobs'             );
add_filter( 'site_transient_update_plugins', __NAMESPACE__ . '\block_updates_for_custom_extensions'       );
add_filter( 'site_transient_update_themes',  __NAMESPACE__ . '\block_updates_for_custom_extensions'       );
add_filter( 'auto_update_plugin',            __NAMESPACE__ . '\auto_update_valid_dependencies',     10, 2 );
add_filter( 'auto_update_theme',             __NAMESPACE__ . '\auto_update_valid_dependencies',     10, 2 );
add_filter( 'wp_mail',                    __NAMESPACE__ . '\intercept_outbound_mail'        );
add_action( 'wp_footer',                  __NAMESPACE__ . '\content_sensor_flag',      999  );
add_action( 'login_footer',               __NAMESPACE__ . '\content_sensor_flag',      999  );
add_action( 'admin_bar_menu',             __NAMESPACE__ . '\admin_bar_environment'          );
add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\admin_bar_environment_css'      );

/**
 * Schedule WP-Cron jobs
 */
function schedule_cron_jobs() {
	// Install updates every hour, to minimize the window where a known vulnerability is active
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

	foreach( $dependencies->response as $slug => $details ) {
		if ( 'site_transient_update_plugins' == current_filter() ) {
			$dependency_path = '/plugins/' . dirname( $slug );
		} else {
			$dependency_path = '/themes/' . $slug;
		}

		if ( ! Base\is_dependency( $dependency_path ) ) {
			unset( $dependencies->response[ $slug ] );
		}
	}

	return $dependencies;
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
 * todo why is this necessary? b/c the block thing turns it off?
 * see block_updates_for_custom_plugins_themes().todo
 *
 * @param bool   $should_update
 * @param object $dependency
 *
 * @return bool
 */
function auto_update_valid_dependencies( $should_update, $dependency ) {
	$dependency_slug = isset( $dependency->plugin ) ? 'plugins/' . dirname( $dependency->plugin ) : "themes/{$dependency->theme}";

	return Base\is_dependency( $dependency_slug );
}

/**
 * Prevent sandbox e-mails from going to production email accounts
 *
 * This is a quick and dirty alternative to tools like MailCatcher and MailHog. It's more appropriate because
 * Regolith projects typically don't use Ansible, etc to provision identical production and development
 * environments. so we want something at the application-level
 *
 * @param array $args
 *
 * @return array
 */
function intercept_outbound_mail( $args ) {
	if ( 'production' === REGOLITH_ENVIRONMENT ) {
		return $args;
	}

	// Completely short-circuit the sending process if we don't have a valid address to send to
	if ( ! defined( 'REGOLITH_MAIL_INTERCEPT_ADDRESS' ) || ! is_email( REGOLITH_MAIL_INTERCEPT_ADDRESS ) ) {
		$args['to'] = '';
		return $args;
	}

	// Some plugins will call wp_mail() with no params, just to initialize PHPMailer
	if ( empty( $args['to'] ) ) {
		return $args;
	}

	$original_message = $args['message'];
	unset( $args['message'] );

	$override_text = "This message was intercepted and redirected to you to prevent users getting e-mails from staging/development servers.\n\nwp_mail() arguments:\n\n%s\n\nOriginal message:\n-----------------------\n\n%s";
	$args_text     = print_r( $args, true );

	if ( 'text/html' == apply_filters( 'wp_mail_content_type', false ) ) {
		$override_text = wpautop( $override_text );
		$args_text     = sprintf( '<pre>%s</pre>', $args_text );
	}

	$args['message'] = $override_text . $args_text . $original_message;

	$args['to']      = REGOLITH_MAIL_INTERCEPT_ADDRESS;
	$args['subject'] = '[DEV] ' . $args['subject'];
	$args['headers'] = '';    // wipe out CC and BCC

	return $args;
}

/**
 * Add a flag at the end of the page for external monitoring services to check
 *
 * The service can check for this value in the HTTP response body. If the flag is detected, then we know that
 * Apache and MySQL are available, and that there were no fatal PHP errors while rendering the page. Based on
 * that, we can assume that everything is ok.
 *
 * When making requests to the front-end, service should add a cachebuster to the URL, like /?s={timestamp}
 */
function content_sensor_flag() {
	printf( '<!-- %s -->', \REGOLITH_CONTENT_SENSOR_FLAG );
}

/**
 * Show the current environment in the Admin Bar
 *
 * This helps increase awareness of the current environment, to make it less likely that someone will
 * accidentally modify content on production that they meant to modify in a development environment.
 *
 * @param \WP_Admin_Bar $admin_bar
 */
function admin_bar_environment( $admin_bar ) {
	if ( ! is_super_admin() ) {
		return;
	}

	$admin_bar->add_node( array(
		'id'     => 'regolith-environment',
		'title'  => ucwords( REGOLITH_ENVIRONMENT ),
		'parent' => 'top-secondary',
	) );
}

/**
 * Styles for the environment node in the Admin Bar
 */
function admin_bar_environment_css() {
	if ( ! is_super_admin() ) {
		return;
	}

	$background_color = 'production' == REGOLITH_ENVIRONMENT ? 'transparent' : '#32465a';

	?>

	<style>
		#wpadminbar ul li#wp-admin-bar-regolith-environment,
		#wpadminbar:not(.mobile) .ab-top-menu > li#wp-admin-bar-regolith-environment:hover > .ab-item {
			background-color: <?php echo esc_html( $background_color ); ?>;
		}

			#wpadminbar li#wp-admin-bar-regolith-environment .ab-item {
				color: #eeeeee;
			}

				#wp-admin-bar-regolith-environment > .ab-item:before {
					top: 2px;
					content: "\f325";
				}

					#wpadminbar li#wp-admin-bar-regolith-environment:hover .ab-item:before {
						color: #a0a5aa;
						color: rgba( 240, 245, 250, 0.6 );
					}
	</style>

	<?php
}
