<?php

/*
 * Make sure this exists and is writable by PHP.
 *
 * Note: There's a small chance that an error will occur before this directive is set. If that happens, then the
 * error will be logged to PHP's default `error_log` instead. If your host allows, you can avoid that by
 * configuring your web server to log errors to the same file specified here.
 */
ini_set( 'error_log', REGOLITH_ROOT_DIR . '/logs/php-errors.log' );

$table_prefix       = 'wp_';
$document_root_path = '/web';
$content_dir_path   = '/content';
$is_multisite       = false;

define( 'DB_NAME',    'regolith.localhost' );
define( 'DB_USER',    'username'           );
define( 'DB_CHARSET', 'utf8mb4'            );
define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );

$safe_server_name = $_SERVER['SERVER_NAME'] ?? parse_url( WP_HOME, PHP_URL_HOST );
$safe_server_name = preg_replace( '[^\w\-.]', '', $safe_server_name ); // See footnote in https://stackoverflow.com/a/6474936/450127.

if ( $is_multisite ) {
	define( 'WP_CONTENT_URL',       'https://' . $safe_server_name . $content_dir_path );
	define( 'MULTISITE',            true                                               );
	define( 'SUBDOMAIN_INSTALL',    true                                               );
	define( 'COOKIE_DOMAIN',        null                                               ); // Allow it to be set dynamically based on the current domain.
	define( 'DOMAIN_CURRENT_SITE',  parse_url( WP_HOME, PHP_URL_HOST )                 );
	define( 'PATH_CURRENT_SITE',    '/'                                                );
	define( 'SITE_ID_CURRENT_SITE', 1                                                  );
	define( 'BLOG_ID_CURRENT_SITE', 1                                                  );
} else {
	define( 'WP_CONTENT_URL', WP_HOME . $content_dir_path );
}

define( 'WP_SITEURL',          WP_HOME . '/wordpress'                                      );
define( 'REGOLITH_BACKUP_DIR', REGOLITH_ROOT_DIR . '/backups'                              );
define( 'WP_TEMP_DIR',         REGOLITH_ROOT_DIR . '/tmp'                                  ); // Avoid leaking data in shared /tmp.
define( 'WP_CONTENT_DIR',      REGOLITH_ROOT_DIR . $document_root_path . $content_dir_path );
define( 'WPCACHEHOME',         WP_CONTENT_DIR . '/plugins/wp-super-cache/'                 );

define( 'WP_CACHE',                     true             );
define( 'DISALLOW_FILE_EDIT',           true             );
define( 'DISALLOW_UNFILTERED_HTML',     true             );
define( 'REGOLITH_CONTENT_SENSOR_FLAG', 'Monitor-WP-OK'  );
define( 'REGOLITH_BACKUP_INTERVAL',     60 * 60 * 24 * 7 ); // In seconds.
define( 'REGOLITH_BACKUPS_TO_KEEP',     50               ); // Includes scheduled backups and backups made before every deployment.
define( 'REGOLITH_GOOGLE_ANALYTICS_ID', 'UA-000000000-0' );
define( 'REGOLITH_MAINTENANCE_MODE',    false            ); // Note: This is not intended to hide content. See `Regolith\Miscellaneous\coming_soon_page()` for details.

define( 'REGOLITH_MAINTENANCE_MODE_MESSAGE', sprintf( '
	<p>%s is currently undergoing maintenance, but please check back soon.</p>
	<!-- %s -->',
	$safe_server_name,
	REGOLITH_CONTENT_SENSOR_FLAG
) );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . "$document_root_path/wordpress/" );
}

define( 'WP_DEBUG',     true );
define( 'WP_DEBUG_LOG', REGOLITH_ROOT_DIR . '/logs/php-errors.log' );

switch ( REGOLITH_ENVIRONMENT ) {
	case 'development':
		define( 'WP_DEBUG_DISPLAY', true );
		define( 'SAVEQUERIES',     true );
		define( 'SCRIPT_DEBUG',    true );
		define( 'FORCE_SSL_ADMIN', false );
		break;

	case 'production':
		ini_set( 'display_errors', 0 ); // Setting this *and* `WP_DEBUG_DISPLAY` to protect against edge cases.

		define( 'WP_DEBUG_DISPLAY', false );
		define( 'SCRIPT_DEBUG',     false );
		define( 'FORCE_SSL_ADMIN',  true  );

		break;
}

// These are no longer necessary, so don't let them clutter the global space.
unset( $document_root_path    );
unset( $content_dir_path      );
unset( $is_multisite          );
unset( $safe_server_name      );
