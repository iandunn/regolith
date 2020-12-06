<?php

/*
Plugin Name: Regolith - Plugin Config
Description: Override plugin configuration stored in the database with values from version-controlled config files.
Version:     0.1
Author:      Ian Dunn
Author URI:  https://iandunn.name
*/

namespace Regolith\Plugin_Config;
use Regolith\Config\Wordfence;
use wfConfig, wfBlock;

defined( 'WPINC' ) || die();

/*
 * This approach has several benefits over using the database:
 *
 * - Configuration is guaranteed to be correct. It can't be messed up by non-technical admins, etc.
 * - It's automatically applied when provisioning a new environment, and is always consistent across all
 *   environments. You don't have to remember to set them, or what to set them to.
 * - When changes are made, it's tracked in version control, so you always know that a change was made, when it
 *   was made, who made it, and why (assuming you write informative commit messages).
 */

require_once( REGOLITH_ROOT_DIR . '/config/plugins/google-auth-eua.php'         );
require_once( REGOLITH_ROOT_DIR . '/config/plugins/login-security-solution.php' );
require_once( REGOLITH_ROOT_DIR . '/config/plugins/wordfence.php'               );

add_action( 'muplugins_loaded', __NAMESPACE__ . '\override_individual_settings' );
add_action( 'init',             __NAMESPACE__ . '\schedule_cron_jobs'           );

add_filter( 'pre_option_gaeua_settings',                  'Regolith\Google_Auth_EUA\get_settings'         );
add_filter( 'pre_option_login-security-solution-options', 'Regolith\Login_Security_Solution\get_settings' );

// Apply whenever settings might be changed by a user, and also periodically as a backup.
add_action( 'admin_head-wordfence-1_page_WordfenceWAF', __NAMESPACE__ . '\apply_wordfence_configuration' );
add_action( 'regolith_apply_configuration',             __NAMESPACE__ . '\apply_wordfence_configuration' );


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

/**
 * Schedule cron jobs.
 */
function schedule_cron_jobs() {
	if ( ! wp_next_scheduled( 'regolith_apply_configuration' ) ) {
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'regolith_apply_configuration' );
	}
}

/**
 * Set WordFence configuration.
 *
 * It requires it's own treatment because it uses custom database tables instead of WP's APIs.
 *
 * @todo Delete all the _permanent_ blocks, so that they have to be set in configuration. But:
 *       - Don't delete ones the existing canonical ones (based on matching the `reason`), because that would
 *         wipe out the `hits` stat, which is useful to have.
 *       - Leave _temporary_ blocks that WordFence creates itself.
 */
function apply_wordfence_configuration() {
	if ( ! is_plugin_active( 'wordfence/wordfence.php' ) ) {
		return;
	}

	$settings              = Wordfence\get_settings();
	$canonical_block_rules = Wordfence\get_block_rules();
	$existing_block_rules  = wfBlock::allBlocks( true );
	$existing_reasons      = wp_list_pluck( $existing_block_rules, 'reason' );

	foreach ( $settings as $key => $value ) {
		wfConfig::set( $key, $value );
	}

	foreach ( $canonical_block_rules as $canonical_rule ) {
		/*
		 * Don't insert duplicates of rules that already exist.
		 *
		 * @todo This doesn't account for multiple rules having identical reasons.
		 */
		if ( in_array( $canonical_rule['reason'], $existing_reasons ) ) {
			continue;
		}

		wfBlock::create( $canonical_rule );
	}
};
