<?php
/*
WP-Cache Config Sample File

See wp-cache.php for author details.
*/

if ( ! function_exists( 'regolith_get_cache_page_secret' ) ) {
	/**
	 * Get the cache page secret via a constant from `environment.php`, because it's sensitive, and having it there
	 * makes it more obvious than if it were buried here.
	 *
	 * Fall back to a random string, for backwards-compatibility with sites that deployed regolith before this was
	 * added. This will break the cache page functionality, because the value will be different on every request,
	 * but that's ok. The functionality is not essential, and it can be easily restored by setting the constant.
	 *
	 * A constant string wasn't used, because there isn't a good source of input that is constant, unique, and
	 * private, while not being sensitive. Sensitive data (like NONCE_SALT) could be derived if the value of
	 * `$cache_page_secret` were exposed. If that value was exposed, the attacker would most likely have access
	 * to more sensitive things as well, like `wp-config.php`, but there could be scenarios where they don't, so
	 * this is erring on the side of caution.
	 *
	 * @return string
	 */
	function regolith_get_cache_page_secret() {
		if ( defined( 'REGOLITH_WP_SUPER_CACHE_SECRET' ) ) {
			$cache_page_secret = REGOLITH_WP_SUPER_CACHE_SECRET;
		} else {
			try {
				$cache_page_secret = bin2hex( random_bytes( 32 ) );
			} catch ( Exception $exception ) {
				$cache_page_secret = sha1( uniqid( microtime() . mt_rand(), true ) );
			}
		}

		return $cache_page_secret;
	}
}

/*
 * There's usually no need for caching in development environments, and it's often a hassle to work around it.
 *
 * For the occasions when it's needed for testing, this can be temporarily changed locally.
 */
if ( 'production' === REGOLITH_ENVIRONMENT ) {
	$cache_enabled       = true;
	$super_cache_enabled = true;
	$cache_rebuild_files = true;
} else {
	$cache_enabled       = false;
	$super_cache_enabled = false;
	$cache_rebuild_files = false;
}

$wp_cache_preload_on           = 1;
$wp_cache_preload_taxonomies   = 1;
$wp_cache_preload_email_volume = 'many';
$wp_cache_preload_email_me     = 0;
$wp_cache_preload_interval     = 240;
$wp_cache_preload_posts        = '2';
$dismiss_htaccess_warning      = 1;
$cache_schedule_interval       = 'hourly';
$cache_gc_email_me             = 0;
$cache_time_interval           = '3600';
$cache_scheduled_time          = '00:00';
$cache_schedule_type           = 'time';
$wp_cache_refresh_single_only  = '0';
$wp_cache_make_known_anon      = 0;
$wp_cache_mod_rewrite          = 1;
$wp_cache_front_page_checks    = 1;
$wp_cache_mfunc_enabled        = 0;
$wp_supercache_304             = 0;
$wp_cache_no_cache_for_get     = 0;
$wp_cache_disable_utf8         = 0;
$cache_page_secret             = regolith_get_cache_page_secret();

$wp_cache_home_path = '/wordpress/'; //Added by WP-Cache Manager
$wp_cache_slash_check = 1; //Added by WP-Cache Manager

if ( ! defined( 'WPCACHEHOME' ) ) {
	define( 'WPCACHEHOME', WP_CONTENT_DIR . "/plugins/wp-super-cache/" );
}

$cache_compression   = false;
$cache_max_time      = 86400;
//$use_flock           = true; // Set it true or false if you know what to use
$cache_path          = WP_CONTENT_DIR . '/cache';
$file_prefix         = 'wp-cache-';
$ossdlcdn            = 0;

// Array of files that have 'wp-' but should still be cached
$cache_acceptable_files    = array( 'wp-comments-popup.php', 'wp-links-opml.php', 'wp-locations.php' );
$cache_rejected_uri        = array( 0 => 'wp-.*\\.php', 1 => 'index\\.php', );
$cache_rejected_user_agent = array(
	0 => 'bot',
	1 => 'ia_archive',
	2 => 'slurp',
	3 => 'crawl',
	4 => 'spider',
	5 => 'Yandex'
);

// Disable the file locking system.
// If you are experiencing problems with clearing or creating cache files
// uncommenting this may help.
$wp_cache_mutex_disabled = 1;

// Just modify it if you have conflicts with semaphores
$sem_id = 341791314;

if ( '/' != substr( $cache_path, - 1 ) ) {
	$cache_path .= '/';
}

$wp_cache_mobile           = 0;
$wp_cache_mobile_whitelist = 'Stand Alone/QNws';
$wp_cache_mobile_browsers  = 'Android, 2.0 MMP, 240x320, AvantGo, BlackBerry, Blazer, Cellphone, Danger, DoCoMo, Elaine/3.0, EudoraWeb, hiptop, IEMobile, iPhone, iPod, KYOCERA/WX310K, LG/U990, MIDP-2.0, MMEF20, MOT-V, NetFront, Newt, Nintendo Wii, Nitro, Nokia, Opera Mini, Palm, Playstation Portable, portalmmm, Proxinet, ProxiNet, SHARP-TQ-GX10, Small, SonyEricsson, Symbian OS, SymbianOS, TS21i-10, UP.Browser, UP.Link, Windows CE, WinWAP';

// change to relocate the supercache plugins directory
$wp_cache_plugins_dir = WPCACHEHOME . 'plugins';

// set to 1 to do garbage collection during normal process shutdown instead of wp-cron
$wp_cache_shutdown_gc     = 0;
$wp_super_cache_late_init = 0;

// uncomment the next line to enable advanced debugging features
$wp_super_cache_advanced_debug          = 0;
$wp_super_cache_front_page_text         = '';
$wp_super_cache_front_page_clear        = 0;
$wp_super_cache_front_page_check        = 0;
$wp_super_cache_front_page_notification = '0';

$wp_cache_object_cache       = 0;
$wp_cache_anon_only          = 0;
$wp_supercache_cache_list    = 0;
$wp_cache_debug_to_file      = 0;
$wp_super_cache_debug        = 0;
$wp_cache_debug_level        = 5;
$wp_cache_debug_ip           = '';
$wp_cache_debug_log          = REGOLITH_ROOT_DIR . '/logs/wp-super-cache.php';
$wp_cache_debug_email        = '';
$wp_cache_pages["search"]    = 0;
$wp_cache_pages["feed"]      = 0;
$wp_cache_pages["category"]  = 0;
$wp_cache_pages["home"]      = 0;
$wp_cache_pages["frontpage"] = 0;
$wp_cache_pages["tag"]       = 0;
$wp_cache_pages["archives"]  = 0;
$wp_cache_pages["pages"]     = 0;
$wp_cache_pages["single"]    = 0;
$wp_cache_pages["author"]    = 0;
$wp_cache_hide_donation      = 0;
$wp_cache_not_logged_in      = 1;
$wp_cache_clear_on_post_edit = 0;
$wp_cache_hello_world        = 0;
$wp_cache_mobile_enabled     = 0;
$wp_cache_cron_check         = 0;
