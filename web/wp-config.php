<?php

require_once( dirname( __DIR__ ) . '/config/environment.php'                          );
require_once( dirname( __DIR__ ) . '/config/wordpress/common.php'                     );
require_once( dirname( __DIR__ ) . '/config/wordpress/'. REGOLITH_ENVIRONMENT .'.php' );

// These are no longer necessary, so don't let them clutter the global space
unset( $deployer_environment );
unset( $document_root_path   );
unset( $content_dir_path     );

require_once( ABSPATH . 'wp-settings.php' );
