<?php

namespace Regolith\Purge_Super_Cache;
use WP_CLI;

WP_CLI::add_command( 'regolith purge-super-cache', __NAMESPACE__ . '\purge_super_cache' );

/**
 * Purge WP Super Cache's cache of the site.
 *
 * There is a wp-super-cache-cli package for WP-CLI, but this is simpler than adding it as a dependency.
 *
 * @param array $args
 * @param array $assoc_args
 */
function purge_super_cache( $args, $assoc_args ) {
	global $file_prefix;

	if ( ! is_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
		WP_CLI::error( 'The WP Super Cache plugin must be activated.' );
	}

	wp_cache_clean_cache( $file_prefix, true );
	$cache_stats = wp_cache_regenerate_cache_file_stats();

	$cache_empty      = 0 === $cache_stats[ 'wpcache'    ][ 'cached' ] && 0 === $cache_stats[ 'wpcache'    ][ 'expired' ];
	$supercache_empty = 0 === $cache_stats[ 'supercache' ][ 'cached' ] && 0 === $cache_stats[ 'supercache' ][ 'expired' ];

	if ( $cache_empty && $supercache_empty ) {
		WP_CLI::success( 'Purged WP Super Cache successfully.' );
	} else {
		WP_CLI::error( "Couldn't purge WP Super Cache." );
	}
}
