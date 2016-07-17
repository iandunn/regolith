<?php

/*
 * This WordPress installation is based on Regolith
 *
 * Check out the GitHub repository for documentation, maintenance instructions, design decisions, etc:
 *
 * https://github.com/iandunn/regolith
 */

require_once( dirname( __DIR__ ) . '/config/environment.php'                          );
require_once( dirname( __DIR__ ) . '/config/wordpress/common.php'                     );
require_once( dirname( __DIR__ ) . '/config/wordpress/'. REGOLITH_ENVIRONMENT .'.php' );

require_once( ABSPATH . 'wp-settings.php' );
