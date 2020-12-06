<?php

namespace Regolith\Config\Wordfence;
use wfBlock;

defined( 'WPINC' ) || die();

/**
 * Get the configuration settings.
 *
 * @return array
 */
function get_settings() {
	return array(
		'blockedTime' => DAY_IN_SECONDS,

		// Max number of requests per minute.
		'maxGlobalRequests'   => 240,
		'maxRequestsCrawlers' => 240,
		'max404Crawlers'      => 120,
		'maxRequestsHumans'   => 120,
		'max404Humans'        => 60,

		'maxGlobalRequests_action'   => 'throttle',
		'maxRequestsCrawlers_action' => 'throttle',
		'max404Crawlers_action'      => 'throttle',
		'maxRequestsHumans_action'   => 'throttle',
		'max404Humans_action'        => 'throttle',
	);
}

/**
 * Define custom blocking rules.
 *
 * Note: It's best to also create these at CloudFlare, to stop them before they even reach the web server.
 * That'll cut down on resource usage on the server, and reduce the risk of a host disabling the site.
 * It's important to also have them here, though, in case CloudFlare is bypassed.
 *
 * `reason` - maximum length is 50 characters.
 * `ipRange`, `hostname`, `userAgent`, `referrer` - use `*` as wildcard.
 *
 * @return array[]
 */
function get_block_rules() {
	return array(
		array(
			'type'      => 'custom-pattern',
			'duration'  => wfBlock::DURATION_FOREVER,
			'reason'    => "Automated pentesting often causes hosting problems",
			'userAgent' => '*Fuzz Faster U Fool*',
		),
	);
}

