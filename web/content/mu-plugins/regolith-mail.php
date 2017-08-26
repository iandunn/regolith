<?php

/*
Plugin Name: Regolith - Mail
Description: Functionality related to sending email
Version:     0.1
Author:      Ian Dunn
Author URI:  http://iandunn.name
*/

namespace Regolith\Miscellaneous;
defined( 'WPINC' ) or die();

add_filter( 'wp_mail',           __NAMESPACE__ . '\intercept_outbound_mail' );

/**
 * Prevent sandbox e-mails from going to production email accounts
 *
 * This is a quick and dirty fallback in case better tools like MailHog or MailCatcher aren't available.
 *
 * @param array $args
 *
 * @return array
 */
function intercept_outbound_mail( $args ) {
	if ( 'production' === REGOLITH_ENVIRONMENT ) {
		return $args;
	}

	if ( better_interceptor_active() ) {
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

	$override_text = "This message was intercepted and redirected to you to prevent users getting e-mails from development servers.\n\nwp_mail() arguments:\n\n%s\n\nOriginal message:\n-----------------------\n\n%s";
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
 * Detect if a dedicated mail interceptor is installed
 *
 * @return bool
 */
function better_interceptor_active() {
	$better_interceptor_active = false;

	// MailHog
	if ( shell_exec( 'which mailhog' ) ) {
		$better_interceptor_active = true;
	}

	// MailCatcher
	if ( false !== strpos( ini_get( 'sendmail_path' ), 'catchmail' ) ) {
		$better_interceptor_active = true;
	}

	return $better_interceptor_active;
}
