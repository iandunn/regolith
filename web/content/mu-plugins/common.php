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

add_filter( 'allow_minor_auto_core_updates', '__return_true'  );
add_filter( 'allow_major_auto_core_updates', '__return_true'  );
add_filter( 'auto_update_plugin',            '__return_true'  );
add_filter( 'auto_update_theme',             '__return_true'  );
add_filter( 'xmlrpc_enabled',                '__return_false' ); // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

add_filter( 'wp_mail',      __NAMESPACE__ . '\intercept_outbound_mail'  );
add_action( 'wp_footer',    __NAMESPACE__ . '\content_sensor_flag', 999 );
add_action( 'login_footer', __NAMESPACE__ . '\content_sensor_flag', 999 );


/**
 * Prevent sandbox e-mails from going to production email accounts
 *
 * @param array @args
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
