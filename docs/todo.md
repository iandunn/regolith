# TODO List

## High

htaccess redirect http->https doesn't work on /wordpress/wp-cron.php
	other places its not working?

multisite
	how to support domain name aliases?
		just need a function in sunrise?

	config/common
		maybe unset $docroot and $contentdirpath in common.php too instead of in wp-config?
			maybe move delpoyer-environment to a separate file so it's not included in wp and has to be unset?
			can't do that b/c it has sensitive info and shouldn't be tracked. too complicated if have more than 1 untracked file

	docs - still true that siteurl needs to end in /wordpress?
	    doesn't seem to be necessary on iandunn.localhost


reconsider including https://github.com/roots/wp-password-bcrypt
	decide whether want it to not

	if want it, how to manage dependency?
	see todo below for items outside w.org
	there's a copy in w.org repo, but not official so don't trust to be unmodified and to kept updated
		could watch commits / support to keep an eye on it, submit patches to update when its out of date
		still an attack vector if can't trust author to keep their account protected w/ strong password, etc

using wpcli for dependency management assumes that all dependencies are in the w.org repos
	is there a way to integrate plugins hosted on github or premium themes
	maybe need a combination of composer (but not wppackagist)

	don't want to track in repo b/c would have to maintain, and would clutter repo,
		always want latest like w/ other dependencies
	git submodule, but then have to manually update
		and submodules are a pain in the ass
	could install one of those generic github updater plugins, i think scribu or pippen wrote one you could trust

	maybe reconsider using composer, but would need to fix obstacles so maintenance isn't a hassle

setup rewrite rules so can still access from /wp-admin ? at least seutp redirects
	https://github.com/roots/bedrock/issues/58 (and links within)
	https://gist.github.com/danielbachhuber/9379135
	https://discourse.roots.io/t/recommended-subdomain-multisite-nginx-vhost-configuration-with-new-web-layout/1429/7

	possibly related:
		https://github.com/roots/bedrock/issues/47
		https://github.com/roots/bedrock/issues/180

	at the very least, can redirecty wp-admin(.*) to wordpress/wp-admin{$1}


after 2016-07-20, verify that backup is running on weekly cron job

share content/cache/.htaccess and maybe a few other files so it doesn't get wiped out during deploy
    also ! to gitignore

set cache headers for browsers and cloudflare
	6 hours for homepage and other archives
	8 days for individual posts/pages ?
	8 days for content/*
	make sure to not override nocache headers

send these headers?
	X-Xss-Protection
	X-Content-Type-Options
	Content-Security-Policy

during first install on production, deployer creates the wordpress folder, so wp-cli doesn't install wp, then rest of script fails
	maybe just use --force param

configure dev uploads to pull from production if not found
	don't need to b/c db points to those urls anyway?
	add to readme?
	jaquith's WP Stack has something you can copy?

maybe write script to pull db from production and import into dev
	https://github.com/markjaquith/WP-Stack/blob/master/lib/tasks.rb
	or deployer.phar does that?
	what about security though? it'll contain sensitive things like password hashes that you don't want just floating around random dev environments locally
	add to readme?

setup pre-commit hook for codesniffer, pre-release for phpmd?
	needs to be setup server-side for proper enforcement?
	maybe just ship the scripts in /bin/git, and give instructions to `ln -s` to them to `echo sh /bin/git/pre-commit.sh >> .git/hooks/pre-commit`
	add to readme

setup pre-deploy hook to run any automated tests that are available?

themes still messed up on production
	maybe deploy problem?
	there's some internal caching that makes it hard to test
	i htink it'd be fixed if i installed a theme, added it to shared, then deployed, but something about having no extra themes makes it fail
	not mvp, because your sites have custom themes. just add simone as part of install and fix later
	when this is fixed, probably best to remove simone b/c don't actually use it
symlink for simone not being created properly on production
	fixed now? see what happens when try to setup new site

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

setup file backups
	config/custom code/etc is in version control, which is good enough
	dependencies are managed by wpcli, so don't need to worry about those
	uploads and maybe a few other things aren't currently covered, though
		best way to do that?
		they don't belong in version-control
		rsync to pull down into dev environments?
			could do that after deploy, or maybe it'd be a separate task
			would rather run it by cron so don't rely on remembering to run it manually, but dev vm won't always be available or have requests hitting wp-cron
	also want to backup the sql backup files
		keeping on production not completely safe
		hosts don't always have backups, so if something happens to production then might not be able to get restore them
			host could screw something up, developer might accidentally delete home dir, etc
		need to encrypt them or something, though, because don't want user password hashes getting copied to insecure location
			maybe rely on backup solution for that, or maybe build into `wp regolith backup-database`
	maybe this is better suited for something outside of regolith
	if do this, then don't really need to have script to direct content 404s to production


## Medium

remove overwritten symlink tasks from deploy recipe when https://github.com/deployphp/deployer/issues/503 is fixed

get wpsc working in mod_rewrite for homepage etc, rather than just php mode

regolith\backup_database\rotate_files includes deployment backups
	that can create problem is deploying frequently
	e.g., instead of having 50 weeks of scheduled backups, would have 1 week of scheduled and 49 deploy backups from the past few days
	maybe update the logic to only delete a file if it's past the number_to_keep AND it's older than REGOLITH_MIN_BACKUP_AGE
	in that case, might be better to rewrite command to use glob() to get files and use php to determine which ones to keep

add an open-source license to readme
	gpl? MIT? unlicense?

if multisite, then look for mu-plugins/sites/hostname.php and include if exists

probably need to split install.md up into different scenarios
	setting up new dev environment for new site
	setting up new dev environment from existing regolith production site
	setting up new production environment from existing regolith dev environment

reconsider environmental variables for environment config
	could use apache `SetEnv WP_ENV production` in the dir above the folder where regolith is cloned
	how to get that to work automatically with wpcli?

reconsider adding log folder, it's nice to have them easily accessible from the IDE

update deployer download to use ssl when available
	https://github.com/deployphp/deployer/issues/700

should install deployer.phar to ~/bin instead of site_root/bin, b/c don't need 5 copies of it if have 5 sites
	need to update how deployer() detects current config folder
also want to install deployer alias script to ~/bin

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

add dev environment dependenies
	e.g., debug-bar, debug-bar-cron, etc
	install-dependencies.sh already knows which environment it is, so not hard to add extra variable for dev plugins and install those if dev environment
	how to handle in .gitignore, though?
		how to distinguish from regular?
		any problems having then ignored by git? attacker could add to production and wouldn't notice, but if they can do that you're already screwed
		would be another reason to consider using composer, if can get over the obstacles to it


## Low

if multisite, maybe automatically add front- and back-end url for each site to smoke:tests
	would be too much if had lots of sites
		could maybe cap it at 3 sites chosen by random
		and then user could still add extra ones in $deployer_environment if they wanted to

maybe have a 'ongoing maintenance' section in docs
	talks about things like adding new dependencies after install - careful b/c of note in .gitignore

send PR to subscribe-to-comments to fix php warnings/notices

add deploy task to ping slack channel
	already exists, just set it up and have it disabled by default
	https://github.com/deployphp/recipes/blob/bdcf49f8e409971b79583aeed618aa87ae714f93/docs/slack.md
	need to pull that library into bin/deployer/recipes or something, and keep updated
		maybe another reason to switch to composer? see other tasks

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
	doesn't look like that's possible with deployer?

check that db creds are correct before running install script, otherwise errors out and have to delete wp before trying again

want to do anything about logging?
	configure logs to be in ./logs ? or at least recommend it? 
	not gonna work on shared hosting though maybe with symlinks
	it'd be nice to have them in {root}/logs folder, but can't change apache location through htaccess
	could put php there by ini_set() in config files, but then any errors that happened earlier would be in different log, and probably go unnoticed

install: modularize into functions

is there a way to set a help description for `wp regolith` without having a class for it?
	right now only have a description for individual commands

maybe also add environment to title on front/back end, to increase awareness
	probably don't add it to production, just dev/staging

block_updates_for_custom_extensions() - better to remove it before it gets sent to w.org
	that way doesn't mess with active_installs stat, doesn't leak private info
	https://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/

block_updates_for_custom_extensions - update if #32101-core is merged

protect against dev committing 3rd party plugin to repo instead of listing as 3rd party dependency
	could cause lots of problems, see note in .gitignore
	maybe cron job to check all custom plugin/theme slugs to see if any also exist in w.org repo
		could probably reuse the update_plugins site transient since it already has info?
		need to hook in before block_updates_for_custom_extensions() changes it though
	if they do, show an admin_notice warning to avoid that and make sure they're properly classified in .gitignore

allow_dev_network_upgrades - problem is bigger than just network upgrades?
	there are other instances of local requests, but never seen anything that mattered
	maybe need better solution that works for all cases
	maybe just install the cert for the server so that it recognizes it as valid? kind of a hassle though
