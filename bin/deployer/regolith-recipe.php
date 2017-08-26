<?php

namespace Deployer;

ini_set( 'display_errors', 1 );

require_once( 'recipe/common.php'                                            );
require_once( dirname( dirname( __DIR__ ) ) . '/config/environment.php'      );
require_once( dirname( dirname( __DIR__ ) ) . '/config/wordpress/common.php' );

/**
 * Initialize
 */
function initialize( $environment ) {
	set_variables( $environment );
	register_servers( $environment['servers'] );
	register_task_actions();
}

/*
 * Setup Deployer variables
 *
 * @param array $environment
 */
function set_variables( $environment ) {
	set( 'regolith_environment', $environment               );
	set( 'repository',           $environment['repository'] );
	set( 'shared_dirs',          get_shared_directories()   );

	set( 'shared_files', [
			'config/environment.php',
			'web/content/cache/.htaccess',
			'web/.user.ini',
		]
	);
}

/**
 * Get the directories that are shared across releases
 *
 * `.gitignore` is the canonical list of plugin/theme dependencies.
 *
 * @return array
 */
function get_shared_directories() {
	$potential_dependencies = file( dirname( dirname( __DIR__ ) ) . '/.gitignore', FILE_IGNORE_NEW_LINES );
	$plugin_dependencies    = preg_grep( '#content\/plugins\/#', $potential_dependencies );
	$theme_dependencies     = preg_grep( '#content\/themes\/#',  $potential_dependencies );

	$other_shared = array(
		'backups',
		'web/wordpress',
		'web/content/uploads',
		'web/content/wflogs',
	);

	$shared_directories = array_merge( $other_shared, $plugin_dependencies, $theme_dependencies );

	foreach ( $shared_directories as $index => $directory ) {
		$shared_directories[ $index ] = trim( $directory, '/' );
	}

	return $shared_directories;
}

/**
 * Register servers to deploy to
 *
 * @param array $servers
 */
function register_servers( $servers ) {
	foreach ( $servers as $stage => $settings ) {
		$hostname = $settings['origin_ip'] ?: $settings['hostname'];

		server( $stage, $hostname )
			->user( $settings['username'] )
			->forwardAgent()
			->set( 'deploy_path', $settings['deploy_path'] );
	}
}

/**
 * Register tasks to run in relation to other tasks
 */
function register_task_actions() {
	after( 'deploy',  'success' );
	after( 'success', 'current' );

	if ( defined( 'REGOLITH_CLOUDFLARE_ZONE_ID' ) && REGOLITH_CLOUDFLARE_ZONE_ID ) {
		after( 'success',  'purge_cloudflare' );
		after( 'rollback', 'purge_cloudflare' );
	}

	after( 'success',  'tests:smoke' );
	after( 'rollback', 'tests:smoke' );
}

/**
 * Create a symlink for wp-config.php in the shared folder
 *
 * Because WordPress is installed in the shared folder, it looks for wp-config there, instead of in the current
 * release folder. The symlink tells it the right place to look.
 */
task( 'deploy:symlink_wp_config', function() {
	$make_directory   = 'mkdir -p {{deploy_path}}/shared/web';
	$change_directory = 'cd {{deploy_path}}/shared/web';
	$create_symlink   = 'ln -snf ../../current/web/wp-config.php wp-config.php';

	run( "$make_directory && $change_directory && $create_symlink" );
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
		get( 'release_path' ),
		strlen( get( 'deploy_path' ) )
	);
	set( 'relative_release_path', $relative_release_path );

	run( "cd {{deploy_path}} && ln -sfn {{relative_release_path}} current" ); // Atomic override symlink.
	run( "cd {{deploy_path}} && rm release" ); // Remove release link.
} )->desc( 'Creating symlink to release' );

/**
 * Backup the database
 *
 * These won't be automatically restored during a `deploy:rollback` task, because that's not always desired. If
 * you want to rollback the database, you'll need to do it manually.
 */
task( 'backup_database', function() {
	$current_folder = get( 'deploy_path' ) . '/current';

	/*
	 * Return early if this is the first deploy to production
	 *
	 * run() throws an exception if the command exits with an error code, so we can't just call
	 * `run( test -d $current_folder )`.
	 */
	if ( 'return' === run( "if [[ ! -d $current_folder ]]; then echo 'return' ; fi" )->toString() ) {
		writeln( "<info>Database not backed up because the current release couldn't be found.</info>" );
		return;
	}

	writeln( run( "cd $current_folder && wp regolith backup-database" )->toString() );
} )->desc( "Backup the database" );

/**
 * Make sure all dependencies are installed on production
 */
task( 'deploy:install_dependencies', function() {
	run( "cd {{release_path}} && bash {{release_path}}/bin/install-dependencies.sh" );
} )->desc( 'Install any new plugin and theme dependencies' );;

/**
 * Purge CloudFlare's cache of the site
 */
task( 'purge_cloudflare', function() {
	writeln( run( "cd {{release_path}} && wp regolith purge-cloudflare-cache" )->toString() );
} )->desc( "Purge CloudFlare's cache" );

/**
 * Rollback to previous release.
 *
 * This is a copy of the default `rollback` task, but modified to create relative symlinks. See
 * `deploy:symlink` for details.
 */
task( 'rollback', function() {
	$releases = get( 'releases_list' );

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
 * @see \Regolith\Miscellaneous\content_sensor_flag()
 */
task( 'tests:smoke', function() {
	$environment = get( 'regolith_environment' );

	$all_passed           = true;
	$development_hostname = parse_url( WP_HOME, PHP_URL_HOST );
	$production_hostname  = $environment['servers']['production']['hostname'];
	$cache_buster         = '/?s=' . time();

	$urls = array(
		str_replace( $development_hostname, $production_hostname, WP_HOME    ) . $cache_buster,
		str_replace( $development_hostname, $production_hostname, WP_SITEURL ) . '/wp-login.php',
	);
	$urls = array_merge( $urls, $environment['additional_test_urls'] );

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
	'backup_database',
	'deploy:symlink_wp_config',
	'deploy:release',
	'deploy:update_code',
	'deploy:shared',
	'deploy:writable',
	'deploy:symlink',
	'deploy:install_dependencies',
	'cleanup',
] )->desc( 'Deploy the current site to production' );

initialize( $deployer_environment );
