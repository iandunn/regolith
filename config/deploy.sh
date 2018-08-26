#!/bin/bash

# Enter the settings of the _production_ server where the site will be deployed to.
#
# If you need to use additional SSH options in to connect to the host (like specifying a non-standard port or SSH
# key), you can do that by modifying your ~/.ssh/config file.

SSH_HOSTNAME="example.org"
SSH_USERNAME="example"
REPO_CHECKOUT="/home/example/websites/example.org"
WP_PATH="$REPO_CHECKOUT/web/wordpress"

# The following URLs will be check after the deploy, to make sure they still contain the
# REGOLITH_CONTENT_SENSOR_FLAG.
#
# Only include the protocol and domain name. Query arguments will be added by the deploy script.
SMOKE_TEST_URLS=(
	"https://example.org"
)
