<?php

namespace Regolith\Google_Auth_EUA;
defined( 'WPINC' ) || die();

/**
 * Get the configuration for Google Authenticator - Encourage User Activation.
 *
 * @return array
 */
function get_settings() {
	return array(
		'mode' => 'nag',
	);
}
