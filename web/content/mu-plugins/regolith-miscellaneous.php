<?php

/*
Plugin Name: Regolith - Miscellaneous
Description: Functionality that doesn't fit in the other Regolith mu-plugins
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Miscellaneous;
use WP_Error, WP_REST_Request, WP_Admin_Bar;

defined( 'WPINC' ) or die();

add_filter( 'xmlrpc_enabled', '__return_false' );   // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

add_action( 'init',                       __NAMESPACE__ . '\schedule_cron_jobs'             );
add_filter( 'cron_schedules',             __NAMESPACE__ . '\add_cron_schedules'             );
add_action( 'regolith_backup_database',   __NAMESPACE__ . '\backup_database'                );
add_action( 'rest_api_init',              __NAMESPACE__ . '\register_rest_routes'           );
add_action( 'template_redirect',          __NAMESPACE__ . '\coming_soon_page'               );
add_action( 'wp_head',                    __NAMESPACE__ . '\google_analytics'               );
add_action( 'wp_footer',                  __NAMESPACE__ . '\content_sensor_flag',       999 );
add_action( 'login_footer',               __NAMESPACE__ . '\content_sensor_flag',       999 );
add_action( 'admin_bar_menu',             __NAMESPACE__ . '\admin_bar_environment'          );
add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\admin_bar_environment_css'      );
add_action( 'admin_print_styles',         __NAMESPACE__ . '\remove_intrusive_wordfence_ui'  );


/**
 * Add custom schedules for WP-Cron
 *
 * @param array $schedules
 *
 * @return array
 */
function add_cron_schedules( $schedules ) {
	$schedules['regolith_backup'] = array(
		'interval' => REGOLITH_BACKUP_INTERVAL,
		'display'  => 'Regolith Backup'
	);

	return $schedules;
}

/**
 * Schedule WP-Cron jobs
 */
function schedule_cron_jobs() {
	if ( 'production' !== REGOLITH_ENVIRONMENT || wp_next_scheduled( 'regolith_backup_database' ) ) {
		return;
	}

	wp_schedule_event( time(), 'regolith_backup', 'regolith_backup_database' );
}

/**
 * Launch our WP-CLI command to backup the database
 */
function backup_database() {
	shell_exec( 'wp regolith backup-database' );
}

/**
 * Register routes for the REST API.
 */
function register_rest_routes() {
	register_rest_route( 'regolith/v1', '/reset_opcache', array(
		'methods'  => 'POST',
		'callback' => __NAMESPACE__ . '\reset_opcache',
	) );
}

/**
 * Reset OPCache.
 *
 * This is a REST API endpoint because `opcache_reset()` must be called via php-fpm, because OPCache is not
 * enabled in CLI mode.
 *
 * @param WP_REST_Request $request
 *
 * @return string|WP_Error
 */
function reset_opcache( $request ) {
	// Require a valid authorization token, to prevent this being used in a DDoS attack.
	if ( empty( REGOLITH_OPCACHE_RESET_KEY ) || REGOLITH_OPCACHE_RESET_KEY !== $request->get_header( 'X-Regolith-Authorization' ) ) {
		return new WP_Error( 'invalid_key', 'Authorization key invalid.' );
	}

	if ( ! function_exists( 'opcache_reset' ) ) {
		return new WP_Error( 'php_old', 'Your version of PHP is too old to have OPCache.' );
	}

	if ( ! ini_get( 'opcache.enable' ) ) {
		return new WP_Error( 'opcache_disabled', 'OPCache is not enabled on this server.' );
	}

	if ( opcache_reset() ) {
		return 'success';
	} else {
		return new WP_Error( 'reset_failed', 'Failed to reset OPCache.' );
	}
}

/**
 * Show a "undergoing maintenance" page to logged-out visitors.
 *
 * This can be used when you're first setting up a site, or performing maintenance and don't want regular visitors
 * seeing the site. Logged-out visitors will see this page instead of the normal site, but users can still log in
 * and view the site normally.
 *
 * _WARNING_: This doesn't block RSS feeds, the REST API, etc. It's not meant to prevent a determined person from seeing
 * content, just as a simple cosmetic block.
 */
function coming_soon_page() {
	if ( ! REGOLITH_MAINTENANCE_MODE || current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_die( REGOLITH_MAINTENANCE_MODE_MESSAGE );
}

/**
 * Output Google Analytics code
 */
function google_analytics() {
	if ( 'production' !== REGOLITH_ENVIRONMENT || empty( REGOLITH_GOOGLE_ANALYTICS_ID ) ) {
		return;
	}

	?>

	<!-- Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( REGOLITH_GOOGLE_ANALYTICS_ID ); ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push( arguments );
		}

		gtag( 'js', new Date() );
		gtag( 'config', '<?php echo esc_js( REGOLITH_GOOGLE_ANALYTICS_ID ); ?>' );
	</script>

	<?php
}

/**
 * Add a flag at the end of the page for external monitoring services to check
 *
 * The service can check for this value in the HTTP response body. If the flag is detected, then we know that
 * Apache and MySQL are available, and that there were no fatal PHP errors while rendering the page. Based on
 * that, we can assume that everything is ok.
 *
 * When making requests to the front-end, the monitoring service should add a cachebuster to the URL, like
 * `/?s={timestamp}`.
 */
function content_sensor_flag() {
	printf( '<!-- %s -->', REGOLITH_CONTENT_SENSOR_FLAG );
}

/**
 * Show the current environment in the Admin Bar
 *
 * This helps increase awareness of the current environment, to make it less likely that someone will
 * accidentally modify content on production that they meant to modify in a development environment.
 *
 * @param WP_Admin_Bar $admin_bar
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

/**
 * Remove intrusive WordFence user interface elements
 */
function remove_intrusive_wordfence_ui() {
	?>

	<!-- Begin Regolith remove_intrusive_wordfence_ui() -->
	<style>
		/* The notification banner is intrusive, and the content is just marketing */
		#adminmenu .update-plugins.wf-notification-count-container {
			display: none;
		}

		/*
		 * The logo is in color when all others are in greyscale, making it stand out, which is distasteful
		 * and visually distracting
		 */
		#toplevel_page_Wordfence .wp-menu-image img {
			display: none;
		}

		#toplevel_page_Wordfence .wp-menu-image::before {
			content: "\f160";
		}
	</style>
	<!-- End Regolith remove_intrusive_wordfence_ui() -->

	<?php
}
