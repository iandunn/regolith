<?php

/*
 * Make sure this exists and is writable by PHP.
 *
 * Note: There's a small chance that an error will occur before this directive is set. If that happens, then the
 * error will be logged to PHP's default `error_log` instead. If you hosting allows, you can avoid that by
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

if ( $is_multisite ) {
	$safe_server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : parse_url( WP_HOME, PHP_URL_HOST );
	$safe_server_name = preg_replace( '[^\w\-.]', '', $safe_server_name ); // See footnote in https://stackoverflow.com/a/6474936/450127

	define( 'WP_CONTENT_URL',       'https://' . $safe_server_name . $content_dir_path );
	define( 'MULTISITE',            true                                               );
	define( 'SUBDOMAIN_INSTALL',    true                                               );
	define( 'COOKIE_DOMAIN',        null                                               ); // allow it to be set dynamically based on the current domain
	define( 'DOMAIN_CURRENT_SITE',  parse_url( WP_HOME, PHP_URL_HOST )                 );
	define( 'PATH_CURRENT_SITE',    '/'                                                );
	define( 'SITE_ID_CURRENT_SITE', 1                                                  );
	define( 'BLOG_ID_CURRENT_SITE', 1                                                  );
} else {
	define( 'WP_CONTENT_URL', WP_HOME . $content_dir_path );
}

define( 'REGOLITH_BACKUP_DIR', REGOLITH_ROOT_DIR . '/backups'                              );
define( 'WP_SITEURL',          WP_HOME . '/wordpress'                                      );
define( 'WP_CONTENT_DIR',      REGOLITH_ROOT_DIR . $document_root_path . $content_dir_path );
define( 'WPCACHEHOME',         WP_CONTENT_DIR . '/plugins/wp-super-cache/'                 );

define( 'WP_CACHE',                     true             );
define( 'DISALLOW_FILE_EDIT',           true             );
define( 'DISALLOW_UNFILTERED_HTML',     true             );
define( 'REGOLITH_CONTENT_SENSOR_FLAG', 'Monitor-WP-OK'  );
define( 'REGOLITH_BACKUP_INTERVAL',     60 * 60 * 24 * 7 ); // in seconds
define( 'REGOLITH_BACKUPS_TO_KEEP',     50               ); // includes scheduled backups and backups made before every deployment

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . "$document_root_path/wordpress/" );
}

switch( REGOLITH_ENVIRONMENT ) {
	case 'development':
		define( 'SAVEQUERIES',     true );
		define( 'WP_DEBUG',        true );
		define( 'SCRIPT_DEBUG',    true );
		define( 'FORCE_SSL_ADMIN', false );
		break;

	case 'production':
		ini_set( 'display_errors', 0 );

		define( 'WP_DEBUG_DISPLAY', false );
		define( 'SCRIPT_DEBUG',     false );
		define( 'FORCE_SSL_ADMIN',  true  );

		break;
}

// These are no longer necessary, so don't let them clutter the global space
unset( $document_root_path    );
unset( $content_dir_path      );
unset( $regolith_is_multisite );
unset( $safe_server_name      );

if ( ! class_exists( '\Deployer\Deployer' ) ) {
	unset( $deployer_environment );
}
