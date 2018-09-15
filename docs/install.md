## Requirements

* Regolith is designed to work with **Apache**, but Nginx will also work fine with some minimal modifications.
* You'll also need **SSH access** to your production server.
* **Git** and **WP-CLI** must be installed in all environments. If they're not installed on production, but you do have SSH access, then you can probably install them manually inside your home directory.
* **PHP 7.0+**. If you can't use that, you could make some minor code changes to make it compatible with PHP 5.3.
* Unix-based operating systems (Linux, OS X, etc) in all environments. It may be possible to run on Windows with Cygwin, but I haven't tested it.

If your host doesn't have Git or WP-CLI installed, check [the troubleshooting guide](./troubleshooting.md).


## Troubleshooting

If you run into any problems, check [the troubleshooting guide](./troubleshooting.md).


## Installing Regolith

### Setup Local Development Environment

1. Create the database and database user account. Import any existing data.
1. `git clone` this repository to a folder one level above Apache's `DocumentRoot`.
	1. For example, if `DocumentRoot` is `/home/jane-development/example.test/public_html`, then clone Regolith to `/home/jane-development/example.test`.
1. Configure Apache's `DocumentRoot` to be the `web/` folder.
1. (optional) Configure your web server to store PHP/Apache/etc logs in the `logs/` folder.
1. Update all the default configuration.
	1. Copy `config/environment-sample.php` to `config/environment.php`.
	1. Update the values in `config/*`, `.gitignore`, and anything else you want to customize.
	1. _Warning:_ Before enabling the HSTS header in `.htaccess`, make sure the site has an active SSL certificate, and that you understand the consequences of HSTS. You may also need to tweak it to include subdomains, preloading, etc.
	1. Add any plugins and themes you want to install to `.gitignore`. That file acts as the central and canonical list of dependencies, and other scripts (like `bin/install-dependencies.sh`) will extract the items from there.
		1. _Warning:_ Make sure all 3rd-party plugins/themes are in `.gitignore`, and that custom ones are **not** there. See the notes in `.gitignore` for details.
	1. Update the path in `web/.user-sample.ini` and move it to `web/.user.ini`, then restart php-fpm.
1. Run `./bin/install-dependencies.sh` to install dependencies and perform other setup tasks.
1. Visit the site to make sure everything is loading correctly.
	1. If you're seeing a blank page on the front end, log in to the back end and make sure a valid theme is activated.
1. If you're setting up a Multisite install, run through the steps in [the Multisite installation notes](./install-multisite.md).
1. Once you've verified that everything is setup correctly in your local environment:
    1. Run `git remote set-url origin {your_repo_url}` and commit your changes.
    1. `git push` to your site's repository.
	1. At this point, your repo is independent of Regolith. You can manually merge in updates if you want, but that isn't necessary.


### Setup Production Environment

1. Create the production database and database user account. Import any existing data.
1. Generate an SSH key pair if you don't already have one, and copy your public key to the server.
	1. `ssh-keygen -t rsa -b 4096 -C "your_email@example.com"`
	1. `ssh-copy-id -i ~/.ssh/mykey user@host`
	1. At this point, you should be able to `ssh user@host` without entering a password.
1. `ssh` to your production server and `cd` to the site's root directory (e.g., `cd /home/jane-production/example.org`)
1. `git clone` your remote repository to a folder one level above Apache's `DocumentRoot`.
	1. For example, if `DocumentRoot` is `/home/jane-production/example.org/public_html`, then clone Regolith to `/home/jane-production/example.org`.
1. Configure Apache's `DocumentRoot` to be the `web/` folder.
	1. If your host doesn't let you change that, you can replace the existing `DocumentRoot` folder with a symlink to the `web` folder.
1. (optional) Configure your web server to store PHP/Apache/etc logs in the `logs/` folder.
1. Upload a copy of your local `environment.php` to the production server's `config/` folder, and update the values to match production's config.
    1. This is required because `environment.php` is never checked in to source control (because it contains sensitive and machine-specific information).
1. Update any paths in `web/.user.ini`
1. Run `bin/install-dependencies.sh`
1. Visit the site to make sure everything is loading correctly.
	1. If you're seeing a blank page on the front end, log in to the back end and make sure a valid theme is activated.
1. From your local development environment, run `bin/deploy.sh` to test the deployment process.
    1. For added convenience, you can optionally add a `deploy` function to your `~/.bashrc` file, and have it automatically call the `deploy` script regardless of which directory you're in. That way you don't have to specify the path to the folder. You can also make it support multiple sites as well, so that you can use a single consistent command for all sites you work on, even if they use different deployment mechanisms. See [iandunn/dotfiles/.bashrc](https://github.com/iandunn/dotfiles/blob/6d02e3b774f1d34677399a7480f6726c46d90743/.bashrc#L158-L209) for an example.
1. Setup HTTP content sensors with a monitoring service -- like [Uptime Robot](https://uptimerobot.com/) -- to look for the value of `REGOLITH_CONTENT_SENSOR_FLAG` in `https://example.org/wp-login.php` and `https://example.org/?s={timestamp}`.
	1 The timestamp serves as a cachebuster. If the monitoring service doesn't allow timestamp tokens, then you can also use Super Cache's `donotcachepage` parameter along with the value of `REGOLITH_WP_SUPER_CACHE_SECRET`.
1. (optional) [Tweak OPCache settings](https://tideways.com/profiler/blog/fine-tune-your-opcache-configuration-to-avoid-caching-suprises).
1. (optional) Setup CloudFlare, including Page Rules to cache dynamic content.


## Regular Usage

After you have Regolith setup in your local and production environments, you can use a traditional workflow, like this example: 

1. Make and test your code changes in your local environment.
1. `git add`, `git commit`, `git push`
1. `bin/deploy.sh`
1. Verify the changes work on production.

When you want to refresh you development environment from production, you can run `bin/sync-production-content.sh`, and then repeat the process above to deploy more code.
