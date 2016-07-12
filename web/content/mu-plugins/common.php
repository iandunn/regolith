<?php

/*
Plugin Name: Regolith - Common
Description: Functionality common across my sites
Version:     0.1
Author:      Ian Dunn
Author URI:  http://iandunn.name
*/

namespace Regolith\Common_Functionality;
defined( 'WPINC' ) or die();

add_filter( 'xmlrpc_enabled',                '__return_false' ); // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

add_filter( 'wp_mail',                    __NAMESPACE__ . '\intercept_outbound_mail'        );
add_action( 'wp_footer',                  __NAMESPACE__ . '\content_sensor_flag',      999  );
add_action( 'login_footer',               __NAMESPACE__ . '\content_sensor_flag',      999  );
add_action( 'admin_bar_menu',             __NAMESPACE__ . '\admin_bar_environment'          );
add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\admin_bar_environment_css'      );

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

	$args['to']      = REGOLITH_MAIL_INTERCEPT_ADDRESS;
	$args['subject'] = sprintf( '[%s] %s', strtoupper( REGOLITH_ENVIRONMENT ), $args['subject'] );
	$args['message'] = sprintf( $override_text, $args_text, $original_message );
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
