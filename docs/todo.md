# TODO List

## High

don't even need wp-cli.yml to find correct path?
	probably do if above web, but doulbe check

share content/cache/.htaccess and maybe a few other files so it doesn't get wiped out during deploy
    also ! to gitignore

set cache headers for browsers and cloudflare
	6 hours for homepage and other archives
	8 days for individual posts/pages ?
	8 days for content/*
	make sure to not override nocache headers

reconsider including https://github.com/roots/wp-password-bcrypt
	don't want to ship with repo b/c would have to maintain, always want latest like w/ other dependencies
	there's a copy in w.org repo, but not official so don't trust to be unmodified and to keep updated

send these headers?
	X-Xss-Protection
	X-Content-Type-Options
	Content-Security-Policy

during first install on production, deployer creates the wordpress folder, so wp-cli doesn't install wp, then rest of script fails
	maybe just use --force param

configure dev uploads to pull from production if not found
	don't need to b/c db points to those urls anyway?
	add to readme?

maybe write script to pull db from production and import into dev
	https://github.com/markjaquith/WP-Stack/blob/master/lib/tasks.rb
	or deployer.phar does that?
	what about security though? it'll contain sensitive things like password hashes that you don't want just floating around random dev environments locally
	add to readme?

setup pre-commit hook for codesniffer, pre-release for phpmd?
	needs to be setup server-side for proper enforcement?
	maybe just ship the scripts in /bin/git, and give instructions to `ln -s` to them to `echo sh /bin/git/pre-commit.sh >> .git/hooks/pre-commit`
	add to readme

setup rewrite rules so can still access from /wp-admin ? at least seutp redirects
	https://github.com/roots/bedrock/issues/58 (and links within)
	https://gist.github.com/danielbachhuber/9379135
	https://discourse.roots.io/t/recommended-subdomain-multisite-nginx-vhost-configuration-with-new-web-layout/1429/7

	possibly related:
		https://github.com/roots/bedrock/issues/47
		https://github.com/roots/bedrock/issues/180

	at the very least, can redirecty wp-admin(.*) to wordpress/wp-admin{$1}

themes still messed up on production
	maybe deploy problem?
	there's some internal caching that makes it hard to test
	i htink it'd be fixed if i installed a theme, added it to shared, then deployed, but something about having no extra themes makes it fail
	not mvp, because your sites have custom themes. just add simone as part of install and fix later
	when this is fixed, probably best to remove simone b/c don't actually use it
symlink for simone not being created properly on production
	fixed now? see what happens when try to setup new site

add wp-cli helper for bakcing up tables?

get wpsc working in mod_rewrite for homepage etc, rather than just php mode

ship default config for login security solution, wordfence, etc
	want it to enforce it all the time, so user can't change through wpadmin?
	or just want to be able to impose it during setting up new site, so don't have to go through config all over again?

	could store config files in config/plugins/foo.php
		then have filters read from there and use set_option filter
			faster than doing it from get_option
		or have wp-cli command to `wp option update foo bar`

	maybe use `wp dictator` to enforce
		would store config in config/plugins/foo.yml
		would need to write extension, or can it handle this already?

using wpcli for dependency management assumes that all dependencies are in the w.org repos
	is there a way to integrate plugins hosted on github or premium themes
	maybe need a combination of composer (but not wppackagist)

mu-plugins/common.php - run auto plugin updater faster, so it runs before wordfence sends an email that plugins are out of date


## Medium

if multisite, then look for mu-plugins/sites/hostname.php and include if exists

probably need to split install.md up into different scenarios
	setting up new dev environment for new site
	setting up new dev environment from existing regolith production site
	setting up new production environment from existing regolith dev environment

reconsider environmental variables for environment config
	could use apache `SetEnv WP_ENV production` in the dir above the folder where regolith is cloned
	how to get that to work automatically with wpcli?

reconsider adding log folder, it's nice to have them easily accessible from the IDE

should $deployer_environment be in enviornment.php? it's not specific to each envirronment. could be in common.php?

update deployer download to use ssl when available
	https://github.com/deployphp/deployer/issues/700

recipe is more a script than a config file, maybe move it to bin/deployer/recipe.php ?

should install deployer.phar to ~/bin instead of site_root/bin, b/c don't need 5 copies of it if have 5 sites
	need to update how deployer() detects current config folder  

setup a /monitor (or whatever) rewrite endpoint that sends nocache_headers, and update monitors to hit that
	neceessary b/c uptimerobot doesn't support cachebusters, so it's just hitting cloudflare for front-end checks


how to handle deploy when changes need to be made to environment.php?
what about when add new shared files? need to commit+deploy updated recipe before commit+deploy other?
	maybe fine now that running install_dependencies.sh after deploy?

wp super cache
	after deploys
		wp super-cache flush
			says worked, but didn't
			maybe just don't share cache directory? that'd automatically purge it, and it'd preload w/in 4 hours

		wp super-cache preload
			says not installed, but it is

		`wp package install wp-cli/wp-super-cache-cli` during install
			allow_url_fopen on shared hosting


## Low

add deploy task to ping slack channel
	already exists, just set it up and have it disabled by default
	https://github.com/deployphp/recipes/blob/bdcf49f8e409971b79583aeed618aa87ae714f93/docs/slack.md

move deploy recipe tasks to named functions for better readability?

need the .gitkeep files?
	shouldn't those folders get created during install?
	if don't need, can remove them from git repo

don't need to set ABSPATH in config/wordpress/common.php?
	always set by wp-load.php?
	or is it always set by common.php, so don't need to check if it's already been set?
	https://core.trac.wordpress.org/changeset/7971
	https://core.trac.wordpress.org/ticket/26592

why checking wp_default_theme in mu-plugins/0-bedrock.php?
    never had problems without that, unless that's why wp-cli would install things to the core folder
    if that was the cause, then update the issue just for anyone else that runs into it
	https://github.com/wp-cli/wp-cli/issues/1139

name releases something easier to parse than the datetimestampallshovedtogetherintoanunreadablemess


wp stack bit to hit uploads directly instead of streaming through php?

check that db creds are correct before running install script, otherwise errors out and have to delete wp before trying again

want to do anything about logging?
	configure logs to be in ./logs ? or at least recommend it? 
	not gonna work on shared hosting though maybe with symlinks

install: detect if plugins/themes already installed and run upgrade them instead of installing if htey are
isntall: modularize into functions

is there a way to set a help description for `wp regolith` without having a class for it?
	right now only have a description for `wp regolith purge-cloudflare-cache`

maybe also add environment to title on front/back end, to increase awareness