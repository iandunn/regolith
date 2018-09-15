<?php

namespace Regolith\Backup_Database;
use WP_CLI;

WP_CLI::add_command( 'regolith backup-database', __NAMESPACE__ . '\backup_database' );

/**
 * Backup the database
 *
 * This uses `wp db export` for the actual dump, but has additional logic for compression, rotation, etc.
 *
 * @param array $args
 * @param array $assoc_args
 */
function backup_database( $args ) {
	$backup_file = sprintf( '%s/%s-%s.sql', REGOLITH_BACKUP_DIR, DB_NAME, time() );

	WP_CLI::launch( 'mkdir -p ' . escapeshellarg( REGOLITH_BACKUP_DIR ) );
	WP_CLI::run_command( array( 'db', 'export',   $backup_file        ) );
	WP_CLI::launch( 'gzip '     . escapeshellarg( $backup_file        ) );

	rotate_files( REGOLITH_BACKUP_DIR );
}

/**
 * Rotate old backup files
 *
 * @param $backup_folder
 */
function rotate_files( $backup_folder ) {
	$rotate_command = sprintf(
		'%s | %s | %s',
		"ls -r --color=never $backup_folder/*.sql.gz",
		'tail -n +' . ( absint( REGOLITH_BACKUPS_TO_KEEP ) + 1 ),
		'xargs -d "\n" rm --'
	);

	/*
	 * Don't exit on failure, because `xargs` passes an empty string to `rm` when there aren't
	 * REGOLITH_BACKUPS_TO_KEEP files yet, causing `rm` to fail. That's expected behavior.
	 */
	WP_CLI::launch( $rotate_command, false );
}
