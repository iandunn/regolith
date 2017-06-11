## Requirements

* Regolith is designed to work with **Apache**
* You'll also need **SSH access** to your production server
* **Git** and **WP-CLI** must be installed in all environments

If your host doesn't have Git or WP-CLI installed, check [the troubleshooting guide](./troubleshooting.md).


## Installing Regolith

1. In your development environment, clone this repository 1 level above Apache's `DocumentRoot`.
	1. For example, if `DocumentRoot` is `/home/jane-development/foo.org/public_html`, then clone Regolith to `/home/jane-development/foo.org`.
1. If you have an existing database, import it.
1. Review/update all the default configuration.
	1. Copy `config/environment-sample.php` to `config/environment.php`
	1. Update the values in `config/*`, `.gitignore`, and anything else you want to customize.
	1. If you use CloudFlare, add [your zone ID](https://blog.cloudflare.com/cloudflare-tips-frequently-used-cloudflare-ap/#comment-2486013580) to `environment.php` and uncomment the `purge_cloudflare` task in `config/deployer/regolith-recipe.php`.
	1. _Warning:_ Before enabling the HSTS header in `.htaccess`, make sure the site has an active SSL certificate, and that you understand the consequences of HSTS. You may also need to tweak it to include subdomains, preloading, etc.
	1. Plugin and theme dependencies are managed by simply adding them to `.gitignore`. That file acts as the central and canonical list of dependencies. `install-dependencies.sh` and `regolith-recipe.php` extract the items from there.
		1. _Warning:_ Make sure all 3rd-party plugins/themes are in .gitignore, and that no custom ones are there. See the notes in that file for details.
1. Run `./bin/install-dependencies.sh` to install dependencies and perform other setup tasks.
1. If you're setting up a Multisite install, run through the steps in [the Multisite installation notes](./install-multisite.md).
1. Install the deployment wrapper script:
	1. `cp /home/jane-development/foo.org/bin/deployer/deployer ~/bin/deployer`
	1. `chmod +x ~/bin/deployer`
	1. If `~/bin` isn't already in your `$PATH`, then `echo "PATH=\$PATH:~/bin" >> ~/.bash_profile`
	1. You can now call `deployer` from any subdirectory of the site.
1. Update the path in `web/.user-sample.ini` and copy it to `web/.user.ini`, then restart php-fpm (or wait for your host to do it automatically).
1. Once you've verified that everything is setup correctly in your local environment:
    1. Run `git remote set-url origin {your_repo_url}` and commit your changes.
    1. `git push` to your site's repository.
    1. Run `deployer deploy` to deploy the site to production.
    1. `ssh` to your production server and `cd` to the site's root directory (e.g., `cd /home/jane-production/foo.org`)
    1. Run `ln -snf ./current/web public_html`, so that Apache's DocumentRoot will always link to the current release's `web` folder.
        1. On many hosts, [it's important to make it a relative symlink](https://iandunn.name/trouble-symlinking-documentroot-on-shared-hosting/).
    1. Copy and paste the contents of environment.php (down to the bottom of the salts) to the file on the production server: /home/jane-production/foo.org/current/config/environment.php and change the details to suit the production server. This is required because environment.php is never checked in to source control as it contains sensitive and machine specific information.
1. Setup HTTP content sensors with a monitoring service -- like [Uptime Robot](https://uptimerobot.com/) -- to look for the value of `REGOLITH_CONTENT_SENSOR_FLAG` in `{production_domain}/wordpress/wp-login.php` and `{production_domain}/?s={timestamp}`.

At this point, your repo is independent of Regolith. You can manually merge in updates if you want, but that isn't necessary.


## Troubleshooting

If you run into any problems, check [the troubleshooting guide](./troubleshooting.md).
