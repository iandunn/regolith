# Regolith

Regolith is a WordPress installation template that employs best practices, but is also tailored for less demanding projects, and aims to automate as much maintenance as possible.

* Organized file system layout.
* Designed to work with Apache and shared hosting.
* Version your custom code and configuration in Git.
	* Includes configuration and integration for several security and performance plugins/services.
	* Includes optional configuration for Multisite with domain mapping. Automatically loads site-specific mu-plugins.
* Manage 3rd party plugin/theme dependencies with a simple text file and [WP-CLI](http://wp-cli.org/).
	* The list of dependencies is tracked in Git, but their code is not, so your repository stays lean and uncluttered.
* Core/plugin/theme updates are installed automatically every hour (including major releases of Core).
* Deploy to production with a simple shell script, which automatically backs up the database, pulls the latest Git commits, purges caches, and runs smoke tests to catch fatal errors.
* Send transactional emails via SMTP for better reliability.
* Automatically backup production database on a customizable schedule, and before every deployment.
* Run a script to import the production database and uploads into your local development environment. The local database is sanitized to remove passwords, email addresses, etc.
* Support for bin scripts and custom WP-CLI commands.
* Outputs a content flag designed for external monitoring services.
* Displays the current environment in the Admin Bar (i.e., _development_ or _production_).

[Check out the screenshots page](docs/screenshots.md) to see some of the above features.

## Documentation

* [Installation](docs/install.md)
* [Design Decisions](docs/design-decisions.md)
* [Miscellaneous Notes](docs/miscellaneous-notes.md)
* [TODO List](docs/todo.md)
