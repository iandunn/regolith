<?php

/*
Plugin Name: Regolith - Mail
Description: Functionality related to sending email
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Miscellaneous;
use PHPMailer, phpmailerException;

defined( 'WPINC' ) or die();

add_filter( 'wp_mail',           __NAMESPACE__ . '\intercept_outbound_mail' );
add_action( 'phpmailer_init', __NAMESPACE__ . '\configure_smtp'          );


/**
 * Prevent sandbox e-mails from going to production email accounts
 *
 * This is a quick and dirty fallback in case better tools like MailHog or MailCatcher aren't available.
 *
 * _WARNING_: This will be bypassed if you use SMTP. See the note in `environment.php` for details.
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
	if ( ! defined( 'REGOLITH_DEV_NOTIFICATIONS' ) || ! is_email( REGOLITH_DEV_NOTIFICATIONS ) ) {
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

	$args['to']      = REGOLITH_DEV_NOTIFICATIONS;
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
	if ( false !== stripos( ini_get( 'sendmail_path' ), 'mailhog' ) || ! empty( getenv( 'MH_OUTGOING_SMTP' ) ) || shell_exec( 'which mailhog' ) ) {
		$better_interceptor_active = true;
	}

	// MailCatcher
	if ( false !== stripos( ini_get( 'sendmail_path' ), 'catchmail' ) ) {
		$better_interceptor_active = true;
	}

	return $better_interceptor_active;
}

/**
 * Configure emails to be sent via SMTP for better reliability.
 *
 * @param PHPMailer $phpmailer
 *
 * @throws phpmailerException
 */
function configure_smtp( $phpmailer ) {
	global $regolith_smtp;

	// Don't use `WP_HOME` because on Multisite it's always the root site.
	$current_site = parse_url( home_url(), PHP_URL_HOST );

	if ( empty( $regolith_smtp[ $current_site ]['hostname'] ) ) {
		return;
	}

	$config = $regolith_smtp[ $current_site ];

	$phpmailer->IsSMTP();
	$phpmailer->SMTPAuth   = true;
	$phpmailer->SMTPSecure = 'tls';
	$phpmailer->Host       = $config['hostname'];
	$phpmailer->Port       = $config['port'];
	$phpmailer->Username   = $config['username'];
	$phpmailer->Password   = $config['password'];

	/*
	 * The third param should be `false` to avoid forging the `Sender` header, which could cause the message to
	 * be rejected.
	 *
	 * See https://core.trac.wordpress.org/ticket/37736
	 */
	$phpmailer->setFrom(    $config['from_email'],     $config['from_name'], false );
	$phpmailer->AddReplyTo( $config['reply_to_email'], $config['from_name'] );
}

