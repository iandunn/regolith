<?php

namespace Regolith\Login_Security_Solution;
defined( 'WPINC' ) || die();

/**
 * Get the configuration for Login Security Solution.
 *
 * @return array
 */
function get_settings() {
	return array(
		'admin_email'                       => REGOLITH_DEV_NOTIFICATIONS,
		'block_author_query'                => '0',
		'deactivate_deletes_data'           => '0',
		'disable_logins'                    => '0',
		'idle_timeout'                      => '0',
		'login_fail_minutes'                => '120',
		'login_fail_tier_2'                 => '5',
		'login_fail_tier_3'                 => '10',
		'login_fail_tier_dos'               => '500',
		'login_fail_notify'                 => '50',
		'login_fail_notify_multiple'        => '0',
		'login_fail_breach_notify'          => '6',
		'login_fail_breach_pw_force_change' => '6',
		'login_fail_delete_interval'        => '0',
		'login_fail_delete_days'            => '120',
		'pw_change_days'                    => '0',
		'pw_change_grace_period_minutes'    => '15',
		'pw_complexity_exemption_length'    => '20',
		'pw_dict_file'                      => '/usr/share/dictd/gcide.index',
		'pw_length'                         => '14',
		'pw_reuse_count'                    => '0',
	);
}
