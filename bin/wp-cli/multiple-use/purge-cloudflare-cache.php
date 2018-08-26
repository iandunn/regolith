<?php

namespace Regolith\Purge_CloudFlare_Cache;
use WP_CLI;

WP_CLI::add_command( 'regolith purge-cloudflare-cache', __NAMESPACE__ . '\purge_cloudflare_cache' );

/**
 * Purge CloudFlare's cache of the site
 *
 * We can pull the Auth-Email and Auth-Key from the CloudFlare plugin's settings, but they don't
 * store the zone ID, so we need to have that in our config.
 *
 * @param array $args
 * @param array $assoc_args
 */
function purge_cloudflare_cache( $args ) {
	if ( ! is_plugin_active( 'cloudflare/cloudflare.php' ) ) {
		WP_CLI::error( 'The CloudFlare plugin must be installed and configured.' );
	}

	$url = sprintf(
		'https://api.cloudflare.com/client/v4/zones/%s/purge_cache',
		REGOLITH_CLOUDFLARE_ZONE_ID
	);

	$request_parameters = array(
		'method' => 'DELETE',

		'headers' => array(
			'X-Auth-Email' => get_option( 'cloudflare_api_email' ),
			'X-Auth-Key'   => get_option( 'cloudflare_api_key'   ),
			'Content-Type' => 'application/json',
		),

		'body' => wp_json_encode( array( 'purge_everything' => true ) )
	);

	$response = wp_remote_request( $url, $request_parameters );

	if ( is_wp_error( $response ) ) {
		WP_CLI::error( $response->get_error_message() );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $body->success ) && true === $body->success ) {
		WP_CLI::success( 'Purged the cache successfully.' );
		return;
	}

	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
	} elseif ( isset( $body->errors[0]->message ) ) {
		$error_message = $body->errors[0]->message;
	} else {
		$error_message = 'Unknown error';
	}

	WP_CLI::error( $error_message );
}
