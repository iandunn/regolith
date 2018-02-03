<?php

/*
 * This WordPress installation is based on Regolith
 *
 * Check out the GitHub repository for documentation, maintenance instructions, design decisions, etc:
 *
 * https://github.com/iandunn/regolith
 */

define( 'REGOLITH_ROOT_DIR', dirname( __DIR__ ) );

require_once( REGOLITH_ROOT_DIR . '/config/environment.php'      );
require_once( REGOLITH_ROOT_DIR . '/config/wordpress.php'   );
require_once( ABSPATH . 'wp-settings.php' );
