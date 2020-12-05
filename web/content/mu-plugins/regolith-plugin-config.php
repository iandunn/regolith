<?php

/*
Plugin Name: Regolith - Plugin Config
Description: Override plugin configuration stored in the database with values from version-controlled config files.
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Plugin_Config;

defined( 'WPINC' ) || die();

/*
 * This approach has several benefits over using the database:
 *
 * - Configuration is guaranteed to be correct. It can't be messed up by non-technical admins, etc.
 * - It's automatically applied when provisioning a new environment, and is always consistent across all
 *   environments.
 * - When changes are made, it's tracked in version control, so you always know that a change was made, when it
 *   was made, who made it, and why (assuming you write good commit messages ;) ).
 */

require_once( REGOLITH_ROOT_DIR . '/config/plugins/google-auth-eua.php'         );
require_once( REGOLITH_ROOT_DIR . '/config/plugins/login-security-solution.php' );

add_action( 'muplugins_loaded', __NAMESPACE__ . '\override_individual_settings' );

add_filter( 'pre_option_gaeua_settings',                  'Regolith\Google_Auth_EUA\get_settings'         );
add_filter( 'pre_option_login-security-solution-options', 'Regolith\Login_Security_Solution\get_settings' );


/**
 * Register callbacks to override individual settings.
 *
 * This works similarly to the `pre_option_{option_name}` callbacks above, but for plugins that create separate
 * options for each individual setting, rather than grouping them together under a single option. Looping through
 * them and programmatically registering callbacks is more elegant and maintainable than manually registering
 * dozens of callbacks.
 */
function override_individual_settings() {
	//$foo             = Regolith\Foo\get_settings();
	//$plugin_settings = compact( 'foo' );
	$plugin_settings = array(); // This line is just to avoid a PHP error when there aren't any. Replace it with the example above.

	foreach ( $plugin_settings as $plugin => $settings ) {
		foreach ( $settings as $option_name => $option_value ) {
			add_filter( 'pre_option_' . $option_name, function() use ( $option_value ) {
				return $option_value;
			} );
		}
	}
}
