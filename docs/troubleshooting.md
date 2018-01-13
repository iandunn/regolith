# Troubleshooting

* Make sure you have the **latest version of WP-CLI** installed, and that `which wp` points to it.
	* If your if host has old versions of Git, WP-CLI, etc installed globally, then you may need to install the latest versions into your `~/bin` directory and `export PATH="$HOME/bin:$PATH"`

* If you're using **CloudFlare**, make sure you have Regolith's deployment configuration setup to connect to the origin IP directly, instead of being proxied through CloudFlare. CloudFlare blocks SSH, so you won't be able to deploy through their proxy.

* If you're having trouble cloning Git repos during deployment:
	* Make sure you can connect via SSH, e.g. `ssh -vvv git@gitlab.com`.
	* [Some hosts block port `22` to GitLab and other platforms](https://stackoverflow.com/questions/45619796/i-cant-clone-gitlab-repo-from-siteground-ssh-session/48242510#48242510), etc. That StackOverflow thread has a solution, by using port `443` instead. That is specific to GitLab, though. Other repository hosts might not work, or might need a different solution.
	* If you're still having trouble after verifying that you can connect with SSH, try [creating a deploy key](https://docs.gitlab.com/ce/ssh/README.html#deploy-keys) specifically for the repo and production server.
		* This shouldn't be necessary, since Deployer is setup to forward your local credentials through SSH agent, but :shrug:.
		* Make sure the key only has `read` access to the specific repository you need to clone, _not_ all repositories, and _not_ `write` access.
		* **DO NOT UPLOAD YOUR PRIVATE SSH KEY TO THE PRODUCTION SERVER**. That would be akin to uploading a plain-text file with all your passwords in it.

* If WordFence says that the WAF still needs to be configured, even though you've setup `auto_prepend_file`, your host may not support `.user.ini` files. These changes are necessary on SiteGround, and may be helpful for other hosts:
	* Change `.user.ini` to `php.ini` everywhere
	* Copy `/usr/local/php72/lib/php.ini` to `~/website/shared/web/php.ini`, and customize the `auto_prepend_file` directive.
		* Simply adding an empty `php.ini` file with the `auto_prepend_file` value won't work, and will cause WP Admin to send a `Location: http://` header.
		* You should periodically diff your custom file against the canonical one and merge changes, especially when changing PHP versions.
	* Add the following line to `~/website/shared/web/php.ini`: `SetEnv PHPRC /home/username/public_html/php.ini`.
		* This is necessary in order for the `php.ini` file to apply to subfolders recursively.

* [Search for an existing issue](https://github.com/iandunn/regolith/issues) on GitHub, or [create a new one](https://github.com/iandunn/regolith/issues/new).
