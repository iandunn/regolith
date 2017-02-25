#!/bin/bash

#
# Install any dependencies that are missing from the current environment.
# Updates are handled by Core's auto-update mechanism.
#

ROOT_PATH=$( dirname $( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd ) )
ENVIRONMENT=$( grep 'REGOLITH_ENVIRONMENT' $ROOT_PATH/config/environment.php | awk -F "'" '{print $4}' )
PLUGINS=$( grep 'content/plugins/' $ROOT_PATH/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )
THEMES=$(  grep 'content/themes/'  $ROOT_PATH/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )
DEPLOYER_PATH="$ROOT_PATH/bin/deployer/deployer.phar"

# Install Core
if [[ ! -d $ROOT_PATH/web/wordpress ]]; then
	echo ""
	wp core download

	# The default plugins directory isn't used by Regolith, and Core won't update it, so it'll just sit there with
	# an old (and potentially vulnerable) version of Akismet
	rm -rf $ROOT_PATH/web/wordpress/wp-content/plugins
fi

wp core is-installed
if [[ $? -eq 1 ]]; then
	echo -e "\nSetting up database tables. Enter site values:"
	wp core install --prompt --skip-email
fi

# Install plugins/themes
echo ""
wp plugin install $PLUGINS
echo ""
wp theme install $THEMES

# Install Deployer
if [[ 'development' = $ENVIRONMENT && ! -f $DEPLOYER_PATH ]]; then
	echo "Downloading Deployer..."
	curl -L --output $DEPLOYER_PATH --progress-bar http://deployer.org/deployer.phar
	chmod +x $DEPLOYER_PATH
fi

# Update Git submodules
git submodule init
git submodule update --remote
