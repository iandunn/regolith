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
 * This is a quick and dirty alternative to tools like MailCatcher and MailHog. It's more appropriate because
 * Regolith projects typically don't use Ansible, etc to provision identical production and development
 * environments. so we want something at the application-level
 *
 * todo note doesn't cover smtp, only sendmail, but wouldn't have smtp configured in dev env anyway
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
