#!/bin/bash

REGOLITH_DIR="$( dirname $( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd ) )"

source $REGOLITH_DIR/bin/helpers.sh
source $REGOLITH_DIR/config/deploy.sh


# Pull the latest Git commits.
function update_git_checkout() {
	git_result=$( ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "git -C $REPO_CHECKOUT pull --recurse-submodules" )
	echo "$git_result"

	if [[ $git_result = *"master is up to date"* ]] || [[ $git_result = *"Fast-forwarded master to"* ]]; then
		git submodule update
		success_message "Git checkout has been updated.\n" "terse"
	else
		error_message "Could not update Git checkout. Aborting deployment."
		exit 1
	fi
}

# Run post-deployment smoke tests to make sure nothing is obviously broken
#
# This relies on the REGOLITH_CONTENT_SENSOR_FLAG, just like external monitoring does.
function smoke_test() {
	local content_sensor_flag=$( get_php_config REGOLITH_CONTENT_SENSOR_FLAG )

	# Check both the front and back ends.
	# Use a cachebuster to bypass static page caching and OPCache.
	local query_params=(
		"wp-login.php?cachebust=$(date +%s)"
		"?s=$(date +%s)"
	)

	printf "\n## Checking test URLs for content sensor flag..."

	for i in ${!SMOKE_TEST_URLS[@]}; do
		for j in ${!query_params[@]}; do
			local url=${SMOKE_TEST_URLS[$i]}/${query_params[$j]}

			if detect_content_sensor $url $content_sensor_flag; then
				echo ""
				success_message "Found the content flag in $url." "terse"
			else
				error_message "The content flag is missing from $url."
			fi
		done
	done
}

# Check a single URL for the content sensor flag
#
# Returns `0` if found, `1` if missing
function detect_content_sensor() {
	local url=$1
	local content_sensor_flag=$2
	local page_source=$( curl --location --silent $url )

	[[ $page_source = *"$content_sensor_flag"* ]]
}


printf "## Backing up database...\n"
ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "wp regolith backup-database --path=$WP_PATH"

printf "\n## Pulling latest Git commits...\n"
update_git_checkout

printf "\n## Installing dependencies..."
ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "bash $REPO_CHECKOUT/bin/install-dependencies.sh"

printf "\n## Purging caches...\n"
ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "wp regolith reset-opcache          --path=$WP_PATH"
ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "wp regolith purge-super-cache      --path=$WP_PATH"
ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "wp regolith purge-cloudflare-cache --path=$WP_PATH"

smoke_test

echo ""
