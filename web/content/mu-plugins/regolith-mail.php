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
use WP_Error;

defined( 'WPINC' ) or die();

add_filter( 'wp_mail',           __NAMESPACE__ . '\intercept_outbound_mail' );
add_filter( 'wp_mail',           __NAMESPACE__ . '\maybe_set_reply_to',      5 );  // Early priority so plugins can override.
add_filter( 'wp_mail_from_name', __NAMESPACE__ . '\set_default_from_name',   1 );  // Early priority so plugins like Formidable can override based on context.
add_filter( 'wp_mail_from',      __NAMESPACE__ . '\enforce_from_address',  999 );
add_action( 'phpmailer_init', __NAMESPACE__ . '\configure_smtp'          );
add_action( 'wp_mail_failed', __NAMESPACE__ . '\log_errors'              );


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
	if ( false !== stripos( ini_get( 'sendmail_path' ), 'mailhog' ) || ! empty( getenv( 'MH_OUTGOING_SMTP' ) ) || shell_exec( 'command -v mailhog' ) ) {
		$better_interceptor_active = true;
	}

	// MailCatcher
	if ( false !== stripos( ini_get( 'sendmail_path' ), 'catchmail' ) ) {
		$better_interceptor_active = true;
	}

	return $better_interceptor_active;
}

/**
 * Get the SMTP config for the current site.
 *
 * @return bool|mixed Boolean `false` on failure; An associative array on success.
 */
function get_current_site_smtp_config() {
	global $regolith_smtp;

	// Don't use `WP_HOME` because on Multisite it's always the root site.
	$current_site = parse_url( home_url(), PHP_URL_HOST );

	return empty( $regolith_smtp[ $current_site ]['hostname'] ) ? false : $regolith_smtp[ $current_site ];
}

/**
 * Configure emails to be sent via SMTP for better reliability.
 *
 * @param PHPMailer $phpmailer
 *
 * @throws phpmailerException
 */
function configure_smtp( $phpmailer ) {
	$config = get_current_site_smtp_config();

	if ( ! $config ) {
		return;
	}

	$phpmailer->IsSMTP();
	$phpmailer->SMTPAuth   = true;
	$phpmailer->SMTPSecure = 'tls';
	$phpmailer->Host       = $config['hostname'];
	$phpmailer->Port       = $config['port'];
	$phpmailer->Username   = $config['username'];
	$phpmailer->Password   = $config['password'];
}

/**
 * Set the default `From` and `Reply-To` headers for outbound emails.
 *
 * @param array $params
 *
 * @return array
 */
function maybe_set_reply_to( $params ) {
	$config = get_current_site_smtp_config();

	if ( ! $config ) {
		return $params;
	}

	// Normalize the headers to an array, since `wp_mail()` accepts them as an array or a string.
	if ( is_string( $params['headers'] ) ) {
		$params['headers'] = explode( "\n", str_replace( "\r\n", "\n", trim( $params['headers'] ) ) );
	}

	/*
	 * Whoever called `wp_mail()` knows more about the context of the email than this function does, so if they
	 * already set `Reply-To` header, then let's just trust that it's more appropriate than this default.
	 */
	foreach ( $params['headers'] as $header ) {
		if ( 'reply-to' === strtolower( substr( $header, 0, 8 ) ) ) {
			return $params;
		}
	}

	$params['headers'][] = sprintf( 'Reply-To: %s <%s>', $config['from_name'], $config['reply_to_email'] );

	return $params;
}

/**
 * Set the default name for the `From` header.
 *
 * @param string $name
 *
 * @return string
 */
function set_default_from_name( $name ) {
	/*
	 * Plugins that are sending mail will know the context of that message better than we can, so just accept their
	 * custom name if they have already overridden `wp_mail()`'s default name.
	 */
	if ( 'WordPress' !== $name ) {
		return $name;
	}

	$config = get_current_site_smtp_config();

	if ( $config ) {
		$name = $config['from_name'];
	} else {
		$name = get_bloginfo( 'name' );
	}

	return $name;
}

/**
 * Enforce the `From` address.
 *
 * It's fine to let plugins override the `From` name, but overriding the `From` address could easily result in
 * a value being set which will fail SPF/etc tests, making it look like we're forging the header, and getting the
 * message flagged as spam.
 *
 * @param string $address
 *
 * @return string
 */
function enforce_from_address( $address ) {
	$config = get_current_site_smtp_config();

	if ( $config ) {
		$address = $config['from_email'];
	}

	return $address;
}

/**
 * Log an error when `wp_mail()` fails.
 *
 * @param WP_Error $error
 */
function log_errors( $error ) {
	$log_message = sprintf(
		"%s: %s. Message data: %s",
		$error->get_error_code(),
		$error->get_error_message(),
		wp_json_encode( $error->get_error_data() )
	);

	trigger_error( $log_message, E_USER_ERROR );
}
