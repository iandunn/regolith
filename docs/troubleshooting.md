# Troubleshooting

* Make sure you have the **latest version of WP-CLI** installed, and that `which wp` points to it.
	* If your if host has old versions of Git, WP-CLI, etc installed globally, then you may need to install the latest versions into your `~/bin` directory and `export PATH="$HOME/bin:$PATH"`

* If you're using **CloudFlare**, make sure you have Regolith's deployment configuration setup to connect to the origin IP directly, instead of being proxied through CloudFlare. CloudFlare blocks SSH, so you won't be able to deploy through their proxy.
