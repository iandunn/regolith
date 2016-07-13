# Install Multisite

First, follow [the normal install instructions](./install.md), and then run through these additional steps when it asks you to.

This is designed to work with subdomain installs, so you'll need to do some extra work if you want subdirectories.

Each individual site needs to be configured to have `/wordpress` at the end of the Site URL; e.g., `https://example.org/wordpress`.


### .htaccess

Update with Multisite's rewrite rules


### config/wordpress/common.php

1. Update values like normal
1. Add Multisite constants. You can define DOMAIN_CURRENT_SITE dynamically:

		define( 'DOMAIN_CURRENT_SITE',  parse_url( WP_HOME, PHP_URL_HOST ) );

1. Add/update this code:

		$safe_server_name = preg_replace( '[^\w\-.]', '', $_SERVER['SERVER_NAME'] ); // See footnote about SERVER_NAME in https://stackoverflow.com/a/6474936/450127
		define( 'WP_CONTENT_URL', 'https://' . $safe_server_name . basename( WP_CONTENT_DIR ) );
		define( 'COOKIE_DOMAIN', null ); // allow it to be set dynamically based on the current domain
		unset( $safe_server_name );

### config/environment.php

Add any extra URLs that you want to test during deployment to `$deployer_environment['additional_test_urls']`. Make sure to include a cache-busting parameter when necessary; e.g., `'https://example.org/?s=' . time()`.
