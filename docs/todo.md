# TODO List

## High


jaquith is right, capistrano/deployer is overkill for these kinds of sites, and even for  ones bbigger than this
	https://markjaquith.wordpress.com/2018/02/12/updating-plugins-using-git-and-wp-cli/
	remove deployer and replace w/ simpler bash script to ssh to server and git pull
	can do rollback to script to print last 10 commits and select 1 to rollback to
	update install instructions, setup sample config, remove deployer stuff, merge new script/config from iandunn.localhost

easy to install, just setup db, git clone, edit config and htaccess then run install script
	automate everything else
	update install instructions

-----

add phpcs.xml and phpmd.xml
	try to just pull external
	setup hooks
	run phpcs and phpmd over the regolith code

port REGOLITH_MAINTENANCe_MODE from SM
	already did? just need to commit?
	make the content of the message a constant too, so it's in config rather than being hardcoded
	send a 503 header to indicate temporarily unavailable
	add note next to constant that enables, warn dev that not intended to protect sensitive content, see function doc for details 

add support for clearing php OPCache during deploy
	copy from iandunn.name


rotate files in {root}/logs

maybe integrate gravityscan

disable automatic upadte emails
	then disable thunderbird filters since they'll no longer be needed

theme updates not installing automatically
	maybe only on iandunn.name, but probably all of regolith
	probably delete wordpress/wp-content/themes anyway. if want one of those themes, can add it to the normal content dir, so that they're all in one place. simpler that way

mail through smtp
	maybe add something to regolith with best practices for setting sender, return-path, etc
	see wcorg-mailer::mail(), any mu-plugins you created, https://core.trac.wordpress.org/ticket/37736#comment:4
	might be different from what wcorg does b/c Envelope-FROM is set by server, rather than suggested by message Sender header
	add constant for mail from address, but leave mail from name alone?
	warn user that they must set the sender constant to a valid address or could cause failures?

	docs: setup spf so web server and mailchimp (or whoever) can send
	create bounce@, abuse@, postmaster@ address, set as return-path

	setup smtp address, and configure phpmailer to send through that rather than local sendmail?
	or maybe external service?
		https://www.mailgun.com/pricing is free, so why not?

		update docs
		setup config in a way that it will fallback to sendmail if no credentials for the domain

		add to readme as a feature
			smtp for reliably delivering transaction emails. i recommend using mailgun so you get additional features, but it'll work with any smtp account

add wp_mail_failed callback
	v1
		simple error_log()
			test that logs for both sendmail and SMTP
		maybe also send notice to admin_email via sendmail ? something else
		maybe retry. if using smtp, retry with sendmail?

	v2
		better to error log, but have some kind of professional error log anaylizer which can send notifications when critical errors detected
		research best practices

maybe remove the mail inteceptor and just assume that mailhog/mailcatcher available?
	good b/c removes unused code
	bad b/c could lead to other people running into problems
	maybe just document that you assume dev environments have mailcatcher/hog installed? that's unrealistic expectation for audience?
	probably remove it, but maybe keep some kind of failsafe, or at least document expectations

log error monitoring
	cant have pro thing like nagios on shared hosting
	maybe just want something simple like this
		bin/log-monitor.sh
		setup unix cron job to run continuously
		call every minute. if another process running, exit
		- maybe better way to run always besides cron?
		tails specified logs - php, wp, apache, mysql, others?
		if detect "fatal error" or other custom pattern, dumps that line to sep file along w/ timestamp, origin file
		every 5 min, send contents of that file to email addy. maybe truncate at 1mb or something
		try squash avoid duplicates into single, but add a note that there were multiple
		after sent, empty the file

	but instead of reinventing wheel, look for existing solution
		https://www.elastic.co/products/logstash
		http://nxlog-ce.sourceforge.net/
		http://www.fluentd.org/
		https://alioth.debian.org/projects/logcheck/
		https://sourceforge.net/projects/logwatch/
		https://mmonit.com/monit/
		https://github.com/etsy/logster

write a bash script to check the PHP error log for fatal errors
	if it detects one, it writes the current timestamp to a file in /tmp and sends an email w/ the error to alert you
	but if the previous timestamp was less than 1 hour ago, it won't send the email, so that you only get 1 email per hour
	then setup a unix cron job to run every minute
	maybe not. what scenarios does this protect against that uptimemonitor + REGOLITH_CONTENT_SENSOR_FLAG doesn't?


add support for multiple plugin directories
	plugins-custom
	plugins-external - gitignore dependencies go here
	plugins-local    - gitignore the whole folder. this might take care of the `add dev environment dependenies` task. wouldn't be tracked, but no big deal?

	maybe look at implementing the non-ui parts of https://github.com/chrisguitarguy/WP-Plugin-Directories
		that'd be simpler than maintaining symlinks

	if use wordcamp.dev approach
		add a link to the final symlink script to the wpstackexchange answer

	add to readme
		"better folder structure, including support for [multiple plugin directories](https://wordpress.stackexchange.com/a/233581/3898)
		need docs on how to setup multiple plugin dirs? no b/c the symlink script'll have to be run automatically during install and deploys?
			well, still need something telling them to run it manually when adding new custom plugins?
		want something in design decisions about multiple plugin dirs?
			can focus on custom plugins without being cluttered/distracted by externals


	maybe setup better plugins folder layout
		https://wordpress.stackexchange.com/a/233581/3898
		it'd be nice to do symlink script as wp-cli command
			probably fine as long as wp-cli commands registered in mu-plugins
		how would this interact w/ the `shared` folder symlinks on production?
			probably need to update deployment recipe to handle it


composer
	automatically get latest stable, instead of having to specify version
		akismet.zip just links to trunk, not to the latest stable
		published stable zip always has version number in it
		is there a akismet-latest.zip that redirects to latest, jsut like there is for core?
			if not, maybe add one
	use wporg repo directly, instead of packagist

don't need wp-config symlink once 4.6?
	https://core.trac.wordpress.org/ticket/26592
	could remove deployer task, update config/wordpress/common to be canonical source?\
	no, needs to be in index.php?

htaccess redirect http->https doesn't work on /wordpress/wp-cron.php
	other places its not working?

multisite
	how to support domain name aliases?
		just need a function in sunrise?
		er, actually, just setup redirects in htaccess? no reason to add complexity to wp layer

	docs - still true that siteurl needs to end in /wordpress?
	    doesn't seem to be necessary on iandunn.localhost


reconsider including https://github.com/roots/wp-password-bcrypt
	decide whether want it to not

	if want it, how to manage dependency?
	see todo below for items outside w.org
	there's a copy in w.org repo, but not official so don't trust to be unmodified and to kept updated
		could watch commits / support to keep an eye on it, submit patches to update when its out of date
		still an attack vector if can't trust author to keep their account protected w/ strong password, etc

	probably not worth it, unless you solve composer problems for other tasks
		https://roots.io/wordpress-password-security-follow-up/

using wpcli for dependency management assumes that all dependencies are in the w.org repos
	is there a way to integrate plugins hosted on github or premium themes
	maybe need a combination of composer (but not wppackagist)

	don't want to track in repo b/c would have to maintain, and would clutter repo,
		always want latest like w/ other dependencies
	git submodule, but then have to manually update
		and submodules are a pain in the ass
		actually, now you can can track latest with `git submodule add -b master {url}`
		works for publish-iandunn-2017. don't need to .gitignore it either :)
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
	also ones recommended by https://securityheaders.io/ and https://observatory.mozilla.org/

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
		https://github.com/10up/wp-hammer looks nice, but it copies the db locally before pruning user pw hashes, etc, so not great for security/privacy
	add to readme?


	build a script to pull prod database into dev environment
		need to change passwords to 'password' to avoid exposing them in a less secure environment
		maybe change emails too to avoid accidentally sending messages to real users from dev if mailhog isn't setup, but already have mu-plugin to prevent that, so probalgby fine
		need to change urls from foo.org to foo.localhost
			maybe have config var to define `$dev_tld = 'localhost'` to assist with that

setup pre-commit hook for codesniffer, pre-release for phpmd?
	needs to be setup server-side for proper enforcement?
	maybe just ship the scripts in /bin/git, and give instructions to `ln -s` to them to `echo sh /bin/git/pre-commit.sh >> .git/hooks/pre-commit`
		probably better: https://github.com/Automattic/wp-calypso/blob/f219e05c834edbee92515a25648bb42086576ffb/docs/coding-guidelines/javascript.md#setting-up-githooks
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
	__probably just remove wp's theme folder and use the custom one entirely, see notes in other todos__

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

maybe assign `$regolith_smtp` inside a `switch() {}` so that aliases etc can easily reuse creds

remove overwritten symlink tasks from deploy recipe now that https://github.com/deployphp/deployer/issues/503 is fixed

	
deploy task to `chmod -w config/plugins/wp-super-cache.php` so WPSC doesn't overwrite it w/ bad values. add comment to top of file explaning why its' unwritable on prod, and to chmod +w to made mods, commit changes, then chmod -w to lock them in place again
	keep in mind migrating away from delpoyer, so wait until that's done, then add this to whatever script wraps around `git pull`



## Medium

add support for deploying to ssh ports other than 22. already done for silencedmajority.org, just need to port/test

maybe remove web/wordpress/wp-content folder, since can install default themes/plugins in web/content if really want them
	could be nice to avoid issues w/ bundled themes/plugins not being updated b/c bugs?
	would core re-add it, or start using web/content by default?
	would need to remove the register_theme_directory() call in muplugin
	yeah, probably want to do this
	if so, then update bin/install-dependencies.sh to `rm -rf wordpress/wp-content` after the `Install Core` section
		do it even if core is already installed, in case an update re-installs the folder. shouldn't happen but be safe
	also remove is_core_theme() and caller in regolith-updates.php?

maybe add install instructions to set file system permissions
	could do in install-dependencies
	maybe also set for wp mods:
		define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
		define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
	also see w.ogr hosting best practices github article on permissions - taylor levett's github i think

ship default config for more plugins
	subscribe to comments
	vaultpress - although merging into jetpack?
	cloudflare - move some of it from environment.php to config/plugins/cloudflare.php? probably. er, no b/c those are sensitive values?
	wordfence
		it turns out wordfence is... special
		stores in custom database table, uses over-engineered set of classes to access/set
		no custom filters to modify defaults or actual values
			could send a PR, but they don't have a github repo or anything. could ask on forums 
		wp doesn't have a filter in `wp_cache_get('alloptions', 'wordfence');`
		could do a cron job to overwrite values in the database? that's fraking awful, but may be the best way

rename environment.php to something like secrets.php to make it more obvious that it's for sensitive things
	non-sensitive things taht are environment specific are already in config/wordpress/development.php or production.php

watch wptv video for https://2017.london.wordcamp.org/session/wrapping-a-modern-php-architecture-around-a-legacy-wordpress-site/
	see if want to integrate any ideas and best practices

add config constant for google analytics ID, then add your mu plugin function
	port from SM
	
also look at other common mu-plugins from mt cluster sites functinoality and add those

get wpsc working in mod_rewrite for homepage etc, rather than just php mode

regolith\backup_database\rotate_files includes deployment backups
	that can create problem is deploying frequently
	e.g., instead of having 50 weeks of scheduled backups, would have 1 week of scheduled and 49 deploy backups from the past few days
	maybe update the logic to only delete a file if it's past the number_to_keep AND it's older than REGOLITH_MIN_BACKUP_AGE
	in that case, might be better to rewrite command to use glob() to get files and use php to determine which ones to keep

add an open-source license to readme
	gpl? MIT? unlicense?

probably need to split install.md up into different scenarios
	setting up new dev environment for new site
	setting up new dev environment from existing regolith production site
	setting up new production environment from existing regolith dev environment

reconsider environmental variables for environment config
	could use apache `SetEnv WP_ENV production` in the dir above the folder where regolith is cloned
	how to get that to work automatically with wpcli?

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

setup sso for multisite?
	https://github.com/humanmade/Mercator/blob/master/sso.php

add cron job to run `wp db optimize` once a week
	backup db first, maybe increase default # of stored backups since this'll add a lot more
	er, maybe not -- https://www.xaprb.com/blog/2010/02/07/how-often-should-you-use-optimize-table/



## Low

maybe setup calvalcade, but probably not a clear benefit for this type of site

monitoring flag should be later. right now there are things like admin bar running after it, which would break and wouldn't be detected

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
	4.6 makes changes to this

why checking wp_default_theme in mu-plugins/0-bedrock.php?
    never had problems without that, unless that's why wp-cli would install things to the core folder
    if that was the cause, then update the issue just for anyone else that runs into it
	https://github.com/wp-cli/wp-cli/issues/1139

name releases something easier to parse than the datetimestampallshovedtogetherintoanunreadablemess
	doesn't look like that's possible with deployer?

check that db creds are correct before running install script, otherwise errors out and have to delete wp before trying again


maybe create an empty `tmp | temp | temporary` folder in /
    sometimes nice to have those files easily acceessible
    don't want wp_get_temp() to pick wp-content b/c security
    set wp-config constant to tell wp_get_temp to use it

install: modularize into functions

is there a way to set a help description for `wp regolith` without having a class for it?
	right now only have a description for individual commands
	might work if you just add a 'regolith' command that does nothing, but has the docs

maybe also add environment to title on front/back end, to increase awareness
	probably don't add it to production, just dev

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

add install-deps.sh to screenshots page

show active mu-plugins on indidivual site wp-admin/plugins.php pages
	normally mu are only shown in the network admin, but regolith will activate site-specific plugins on each site, so it's nice to see that they're active

look through https://codex.wordpress.org/User:Hakre/Technical_Installation
	it's old, but may have some things that are still useful

