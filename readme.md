# Regolith

Regolith is a WordPress installation template that employs best practices, but is also tailored for less demanding projects, and aims to automate as much maintenance as possible.

* Organized file system layout
* Designed to work with Apache and shared hosting
* Version your custom code and configuration in Git
* Manage 3rd party plugin/theme dependencies with a simple text file and [WP-CLI](http://wp-cli.org/)
	* The list of dependencies is tracked in Git, but their code is not, so your repository stays lean and uncluttered
* Core/plugin/theme updates are installed automatically every hour (including major releases of Core)
* Deploy to production with [Deployer](http://deployer.org), and immediately run smoke tests to catch fatal errors
* Automatically backup production database on a customizable schedule, and before every deployment
* Optional configuration for Multisite with domain mapping
* Includes configuration and integration for several security and performance plugins/services
* Outputs a content flag designed for external monitoring services
* Displays the current environment in the Admin Bar (i.e., _development_ or _production_)


## Documentation

* [Installation](docs/install.md)
* [Reasoning for Decisions](docs/decisions.md)
* [Miscellaneous Notes](docs/miscellaneous.md)
* [TODO List](docs/todo.md)
