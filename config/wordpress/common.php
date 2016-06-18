<?php

$table_prefix       = 'wp_';
$document_root_path = '/web';
$content_dir_path   = '/content';

define( 'DB_NAME',    'regolith.localhost' );
define( 'DB_USER',    'username'           );
define( 'DB_CHARSET', 'utf8'               );
define( 'DB_COLLATE', ''                   );

define( 'REGOLITH_ROOT_DIR',            dirname( dirname( __DIR__ ) )                               );
define( 'WP_SITEURL',                   WP_HOME . '/wordpress'                                      );
define( 'WP_CONTENT_URL',               WP_HOME . $content_dir_path                                 );
define( 'WP_CONTENT_DIR',               REGOLITH_ROOT_DIR . $document_root_path . $content_dir_path );
define( 'WPCACHEHOME',                  WP_CONTENT_DIR . '/plugins/wp-super-cache/'                 );

define( 'WP_CACHE',                     true            );
define( 'FORCE_SSL_ADMIN',              true            );
define( 'DISALLOW_FILE_EDIT',           true            );
define( 'REGOLITH_CONTENT_SENSOR_FLAG', 'Monitor-WP-OK' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . "$document_root_path/wordpress/" );
}
