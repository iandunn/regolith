<?php

/** @global array $deployer_environment */

require_once( 'recipe/common.php'                          );
require_once( dirname( __DIR__ ) . '/environment.php'      );
require_once( dirname( __DIR__ ) . '/wordpress/common.php' );

/**
 * Get the directories that are shared across releases
 *
 * `.gitignore` is the canonical list of plugin/theme dependencies.
 *
 * @return array
 */
function get_shared_directories() {
	$potential_dependencies = file( REGOLITH_ROOT_DIR . '/.gitignore' );
	$plugin_dependencies    = preg_grep( '#content\/plugins\/#', $potential_dependencies );
	$theme_dependencies     = preg_grep( '#content\/themes\/#',  $potential_dependencies );

	$other_shared = array(
		'web/wordpress',
		'web/content/uploads',
		'web/content/wflogs',
	);

	$shared_directories = array_merge( $other_shared, $plugin_dependencies, $theme_dependencies );

	return array_map( 'trim', $shared_directories );
}

set( 'repository', $deployer_environment['repository'] );

set( 'shared_files', [
		'config/environment.php'
	]
);

set( 'shared_dirs', get_shared_directories() );

foreach ( $deployer_environment['servers'] as $environment => $settings ) {
	server( $environment, $settings['hostname'] )
		->user( $settings['username'] )
		->forwardAgent()
		->env( 'deploy_path', $settings['deploy_path'] );
}


/**
 * Create a symlink for wp-config.php in the shared folder
 *
 * Because WordPress is installed in the shared folder, it looks for wp-config there, instead of in the current
 * release folder. The symlink tells it the right place to look.
 */
task( 'deploy:symlink_wp_config', function() {
	$change_directory = 'cd {{deploy_path}}/shared/web';
	$create_symlink   = 'ln -snf ../../current/web/wp-config.php wp-config.php';

	run( "$change_directory && $create_symlink" );
} )->desc( 'Create symlink for wp-config.php' );

/**
 * Create symlinks for shared directories and files.
 *
 * This is a copy of the default `deploy:shared` task, but modified to create relative symlinks. See
 * `deploy:symlink` for details.
 */
task( 'deploy:shared', function() {
	$sharedPath = "{{deploy_path}}/shared";

	foreach ( get( 'shared_dirs' ) as $dir ) {
		// Remove from source.
		run( "if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi" );

		// Create shared dir if it does not exist.
		run( "mkdir -p $sharedPath/$dir" );

		// Create path to shared dir in release dir if it does not exist.
		// (symlink will not create the path and will fail otherwise)
		run( "mkdir -p `dirname {{release_path}}/$dir`" );

		// Symlink shared dir to release dir
		$change_directory = 'cd {{release_path}}/' . dirname( $dir );
		$parent_levels    = str_repeat( '../', substr_count( $dir, '/' ) + 2 );
		$create_symlink   = "ln -nfs {$parent_levels}shared/$dir " . basename( $dir );

		run( "$change_directory && $create_symlink" );
	}

	foreach ( get( 'shared_files' ) as $file ) {
		$dirname = dirname( $file );
		// Remove from source.
		run( "if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi" );
		// Ensure dir is available in release
		run( "if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi" );

		// Create dir of shared file
		run( "mkdir -p $sharedPath/" . $dirname );

		// Touch shared
		run( "touch $sharedPath/$file" );

		// Symlink shared dir to release dir
		$change_directory = 'cd {{release_path}}/' . dirname( $file );
		$parent_levels    = str_repeat( '../', substr_count( $file, '/' ) + 2 );
		$create_symlink   = "ln -sfn {$parent_levels}shared/$file " . basename( $file );

		run( "$change_directory && $create_symlink" );
	}
} )->desc( 'Creating symlinks for shared files' );

/**
 * Create symlink to last release.
 *
 * This is a copy of the default `deploy:symlink` task, but modified to create relative symlinks.
 *
 * See https://github.com/deployphp/deployer/issues/503
 *
 * On many shared hosting platforms, the user is chroot'd into a child directory, so absolute symlinks
 * fail because they require permission to execute on each folder in the path, all the way up to /
 *  - https://unix.stackexchange.com/a/21339/18886
 *  - https://gist.github.com/mkrisher/74721/revisions#diff-01cf30e04b9c4489ed9e927b99e3b66dR45
 *
 * Also, the symlinks need to be set relative to the symlink's target directory.
 *  - https://unix.stackexchange.com/a/15285/18886
 *  - https://askubuntu.com/a/524635
 */
task( 'deploy:symlink', function() {
	$relative_release_path = '.' . substr(
		env( 'release_path' ),
		strlen( env( 'deploy_path' ) )
	);
	env( 'relative_release_path', $relative_release_path );

	run( "cd {{deploy_path}} && ln -sfn {{relative_release_path}} current" ); // Atomic override symlink.
	run( "cd {{deploy_path}} && rm release" ); // Remove release link.
} )->desc( 'Creating symlink to release' );

/**
 * Make sure all dependencies are installed on production
 */
task( 'deploy:install_dependencies', function() {
	run( "bash {{release_path}}/bin/install-dependencies.sh" );
} )->desc( 'Install any new plugin and theme dependencies' );;

/**
 * Purge CloudFlare's cache of the site
 */
task( 'purge_cloudflare', function() {
	writeln( run( "cd {{release_path}} && ~/bin/wp regolith purge-cloudflare-cache" )->toString() );
} )->desc( "Purge CloudFlare's cache" );

/**
 * Rollback to previous release.
 *
 * This is a copy of the default `rollback` task, but modified to create relative symlinks. See
 * `deploy:symlink` for details.
 */
task( 'rollback', function() {
	$releases = env( 'releases_list' );

	if ( isset( $releases[1] ) ) {
		$releaseDir = "./releases/{$releases[1]}";

		// Symlink to old release.
		run( "cd {{deploy_path}} && ln -nfs $releaseDir current" );

		// Remove release
		run( "rm -rf ./releases/{$releases[0]}" );

		writeln( "Rollback to `{$releases[1]}` release was successful." );
	} else {
		writeln( "<comment>No more releases you can revert to.</comment>" );
	}
} )->desc( 'Rollback to previous release' );

/**
 * Check production for fatal errors
 *
 * @see Must_Use\Common\content_sensor_flag()
 */
task( 'tests:smoke', function() {
	global $deployer_environment;

	$all_passed           = true;
	$development_hostname = parse_url( WP_HOME, PHP_URL_HOST );
	$production_hostname  = $deployer_environment['servers']['production']['hostname'];
	$cache_buster         = '/?s=' . time();

	$urls = array(
		str_replace( $development_hostname, $production_hostname, WP_HOME    ) . $cache_buster,
		str_replace( $development_hostname, $production_hostname, WP_SITEURL ) . '/wp-login.php',
	);

	foreach ( $urls as $url ) {
		$curl_response = runLocally( "curl --silent $url" );

		$success = false !== strpos( $curl_response, REGOLITH_CONTENT_SENSOR_FLAG );
		$status  = $success ? 'found' : 'not found';
		$tag     = $success ? 'info'  : 'error';

		writeln( sprintf(
			'%s: flag was <%s>%s</%s>',
			$url,
			$tag,
			$status,
			$tag
		) );

		if ( ! $success ) {
			$all_passed = false;
		}
	}

	if ( ! $all_passed ) {
		writeln( "<error>D'oh! It looks like something went wrong. You might wanna rollback...</error>" );
	}
} )->desc( 'Running smoke tests' );


/**
 * Main task
 */
task( 'deploy', [
	'deploy:prepare',
	'deploy:symlink_wp_config',
	'deploy:release',
	'deploy:update_code',
	'deploy:shared',
	'deploy:writable',
	'deploy:symlink',
	'deploy:install_dependencies',
	'cleanup',
] )->desc( 'Deploy the current site to production' );

after( 'deploy',  'success'          );
after( 'success', 'current'          );
//after( 'success', 'purge_cloudflare' );
after( 'success', 'tests:smoke'      );

//after( 'rollback', 'purge_cloudflare' );
after( 'rollback', 'tests:smoke'      );
