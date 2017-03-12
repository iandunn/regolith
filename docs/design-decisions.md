# Design Decisions

In geology, regolith is the layer of loose material that lies above the firm bedrock.

This project was inspired by [Bedrock](https://github.com/roots/bedrock), but the tooling and workflow choices are a bit looser, to fit my personal side-projects, where more rigorous tools and workflows would be overkill and inconvenient.

* **WP-CLI over Composer:** Composer is great, but it feels a bit unnecessary if you always want to be running the latest versions of Core and plugins/themes.
	* Core updates are very safe and backwards-compatible, and the plugins/themes I use are too, so I'm comfortable updating automatically. Monitoring can catch fatal errors immediately, and on the rare occasional that something breaks, it's not the end of the world.
	* Plugin and theme dependencies already need to be stored in `.gitignore`, so that's used as the canonical list of dependencies. Installation and deployment scripts feed that list into WP-CLI to make sure new environments and production have all the dependencies.
	* Also, [WPackagist.org doesn't have a way to verify packages](https://github.com/outlandishideas/wpackagist/issues/169), so I don't feel comfortable using it.
* **Deployer over Capistrano:** This gets rid of the need to have Ruby installed. Also, the deployment recipes can be written in PHP, and can natively include the configuration files.
* **Apache over Nginx:** Nginx is great, but most shared hosting servers run Apache.
* **Configuration files over environmental variables:** Apache doesn't have a simple way to import environment variable, so Bedrock recommends just putting the values in the Virtual Host config or `.htaccess`. Shared hosting users don't have access to the vhost config, and putting the values in `.htaccess` defeats the whole purpose of using environment variables in the first place. Instead, all sensitive configuration values are stored in [an isolated file](../config/environment-sample.php), which is ignored by Git.
