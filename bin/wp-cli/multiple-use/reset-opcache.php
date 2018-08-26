<?php

namespace Regolith\Reset_OPCache;
use WP_CLI;

WP_CLI::add_command( 'regolith reset-opcache', __NAMESPACE__ . '\reset_opcache' );

/**
 * Reset OPCache's cache of the site.
 *
 * @param array $args
 * @param array $assoc_args
 */
function reset_opcache( $args, $assoc_args ) {
	$request_args = array(
		'headers' => array(
			/*
			 * Can't use just plain `Authorization` because CloudFlare strips it before the request is forwarded
			 * to the origin server.
			 */
			'X-Regolith-Authorization' => REGOLITH_OPCACHE_RESET_KEY,
		)
	);

	$response = wp_remote_post( rest_url( 'regolith/v1/reset_opcache' ), $request_args );
	$body     = json_decode( wp_remote_retrieve_body( $response ) );

	if ( 'success' === $body ) {
		WP_CLI::success( 'Reset OPCache successfully.' );
	} else {
		$error = $body->message ?? 'Failed to reset OPCache.';

		WP_CLI::error( $error );
	}
}
