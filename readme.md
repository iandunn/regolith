# Regolith

Regolith is a WordPress installation template that employs best practices, but is also tailored for less demanding projects.

* Organized file system layout
* Designed to work with Apache and shared hosting
* Version your custom code and configuration in Git
* Manage 3rd party plugin/theme dependencies with a simple text file and [WP-CLI](http://wp-cli.org/)
	* 3rd party plugins/themes are not stored in Git, so your repository stays lean and uncluttered.
* Core/plugin/theme updates are installed automatically (including major releases of Core)
* Deploy to production with [Deployer](http://deployer.org)
* Optional configuration for Multisite with domain mapping
* Includes configuration and integration for several security and performance plugins/services
* Outputs a content flag designed for external monitoring services


## Requirements / Installation

See [install.md](docs/install.md).


## Reasoning

In geology, regolith is the layer of loose material that lies above the firm bedrock.

This project was inspired by [Bedrock](https://github.com/roots/bedrock), but the tooling and workflow choices are a bit looser, to fit my personal side-projects, where more rigorous tools and workflows would be overkill and inconvenient.

* **WP-CLI over Composer:** Composer is great, but it feels a bit unnecessary if you always want to be running the latest versions of Core and plugins/themes.
	* Core updates are very safe and backwards-compatible, and the plugins/themes I use are too, so I'm comfortable updating automatically. Monitoring can catch fatal errors immediately, and on the rare occasional that something breaks, it's not the end of the world.
	* Plugin and theme dependencies are already stored in `.gitignore`, so that's used as the canonical list of dependencies. Installation and deployment scripts feed that list into WP-CLI to make sure new environments and production have all the dependencies.
    * Also, [WPackagist.org doesn't have a way to verify packages](https://github.com/outlandishideas/wpackagist/issues/169), so I don't feel comfortable using it.
* **Deployer over Capistrano:** This gets rid of the need to have Ruby installed. Also, the deployment recipes can be written in PHP, and can natively include the configuration files.
* **Apache over Nginx:** Nginx is great, but most shared hosting servers run Apache.
* **Configuration files over environmental variables:** Environment variable feel kind of clunky when you have multiple sites running under the same user. Instead, all sensitive configuration values are stored in an isolated file, which is ignored by Git.
