<?php

/*
 * This file should never be publicly exposed, or checked into version control.
 *
 * Some of the values are critically sensitive, and it's also intended to have values that are
 * specific to the individual machine running it (e.g., different developers would have different
 * database passwords on their machines).
 *
 * The production copy of this file won't be modified during deployment, so any changes you make to it
 * will need to be manually adjusted on production. In some cases you can make those changes on
 * production before deployment in a forwards-compatible way, by using feature flags, detecting if a
 * file you're about to deploy exists, etc.
 */

define( 'REGOLITH_ENVIRONMENT',            'development'                );  // 'development' or 'production'.
define( 'WP_HOME',                         'https://regolith.localhost' );
define( 'DB_HOST',                         'localhost'                  );
define( 'DB_PASSWORD',                     'password'                   );
define( 'REGOLITH_DEV_NOTIFICATIONS',      'foo@example.org'            );
define( 'REGOLITH_WP_SUPER_CACHE_SECRET',  'replace me with  strong password' );
define( 'WPCOM_API_KEY',                   ''                           ); // Akismet API key.
define( 'REGOLITH_CLOUDFLARE_ZONE_ID',     ''                           );
define( 'REGOLITH_OPCACHE_RESET_KEY',      ''                           );

/*
 * Always set/change these with `wp config shuffle-salts` instead of using api.wordpress.org. Generating them
 * locally is safer, because the keys are never exposed to the Internet, your web browser
 * (and its 3rd-party extensions), etc.
 */
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

/*
 * Add a subarray here for every site that should send mail via SMTP. If you're not using Multisite, then there'll
 * only be one subarray.
 *
 * _WARNING_: This should almost always be left empty in local development environments. If you configure it in one,
 * then outbound email will always bypass MailHog, MailCatcher, `Regolith\Miscellaneous\intercept_outbound_mail()`,
 * etc. That's typically not what you want in a development environment, since it could lead to real users getting
 * test emails. Not only is that confusing and unprofessional, in some cases it can also leak sensitive information.
 */
$regolith_smtp = array(
	/*
	'regolith.iandunn.localhost' => array(
		'hostname'       => 'smtp.mailgun.org',
		'port'           => 587,
		'username'       => 'postmaster@mailgun.regolith-production.org',
		'password'       => 'password',
		'from_name'      => 'Jane Doe',
		'from_email'     => 'postmaster@mailgun.regolith-production.org',
		'reply_to_email' => 'jane@regolith-production.org',
	),
	*/
);
