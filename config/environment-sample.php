<?php

/*
 * This file should never be publicly exposed, or checked into version control.
 *
 * Some of the values are critically sensitive, and it's also intended to have values that are
 * specific to the individual machine running it (e.g., different developers would have different
 * database passwords on their machines.
 *
 * The production copy of this file won't be modified during deployment, so any changes you make to it
 * will need to be manually adjusted on production. In some cases you can make those changes on
 * production before deployment in a forwards-compatible way, by defining a value based on the path
 * to the current release, etc.
 */

define( 'REGOLITH_ENVIRONMENT',            'development'                );
define( 'WP_HOME',                         'https://regolith.localhost' );
define( 'DB_HOST',                         'localhost'                  );
define( 'DB_PASSWORD',                     'password'                   );
define( 'REGOLITH_MAIL_INTERCEPT_ADDRESS', 'foo@example.org'            );
define( 'REGOLITH_CLOUDFLARE_ZONE_ID',      ''                          );

// https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

$deployer_environment = array(
	'repository' => 'git@gitlab.com:username/regolith-production.net.git',

	'servers' => array(
		'production' => array(
			'hostname'    => 'regolith-production.net',
			'origin_ip'   => '',    // If using an HTTP proxy like CloudFlare, enter your origin server IP. Otherwise, leave blank.
			'username'    => 'regolith',
			'deploy_path' => '/var/www/regolith-production.net',
		),
	),
);