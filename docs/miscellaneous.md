# WP-CLI Commands

Any `.php` files placed in `bin/wp-cli/multiple-use` will automatically be registered as WP-CLI commands scripts, so you can just run `wp {foo} {bar}`. See `purge-cloudflare-cache.php` as an example.

One-off scripts can be placed in `bin/wp-cli/single-use` and called with `wp eval-file bin/wp-cli/single-use/example.php`

The reasoning behind that setup is that `wp help` shouldn't be cluttered with dozens of commands that were only used once, but commands that are used regularly should be documented there.
