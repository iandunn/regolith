# Install Multisite

First, follow [the normal install instructions](./install.md), and then run through these additional steps when it asks you to.

This is designed to work with subdomain installs or custom domain names, so you'll need to do some extra work if you want subdirectories.

Each site should have `/wordpress` appended to the `siteurl` option; e.g., `https://example.org/wordpress`.

If you're using custom domain names instead of subdomains, you don't need the Domain Mapping plugin, just set each site's `homeurl` and `siteurl` to the custom domain name.


### .htaccess

Update with Multisite's rewrite rules (from the Network Setup wizard).


### config/wordpress/common.php

1. Set `$is_multisite = true`


### config/deploy.sh

Add any extra URLs that you want to test after deployment.
