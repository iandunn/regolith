#!/bin/bash

# Enter the settings of the _production_ server where the site will be deployed to.
#
# This assumes that you've used `ssh-copy-id` to copy your public key to the server instead of relying on
# password authentication.
#
# If you need to use additional SSH options in to connect to the host (like specifying a non-standard port or SSH
# key), you can do that by modifying your ~/.ssh/config file.
SSH_HOSTNAME="example.org"
SSH_USERNAME="example"
REPO_CHECKOUT="/home/example/websites/example.org"

# The following URLs will be check after the deploy, to make sure they still contain the
# REGOLITH_CONTENT_SENSOR_FLAG.
#
# Only include the protocol and domain name. Query arguments will be added by the deploy script.
SMOKE_TEST_URLS=(
	"https://example.org"
)

# Automatically derived settings. You don't need to edit these.
LOCAL_WP_PATH=$( get_php_config ABSPATH )
RELATIVE_LOCAL_WP_PATH=${LOCAL_WP_PATH/$REGOLITH_DIR\//""}
WP_PATH="$REPO_CHECKOUT/$RELATIVE_LOCAL_WP_PATH"
