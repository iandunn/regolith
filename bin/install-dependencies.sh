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

# Make sure folders exists
mkdir $ROOT_PATH/logs
mkdir $ROOT_PATH/tmp

# Install Core
if [[ ! -d $ROOT_PATH/web/wordpress ]]; then
	echo ""
	wp core download

	# The default content directory isn't used by Regolith, and Core won't update it, so it'll just sit there with
	# old (and potentially vulnerable) plugins/themes.
	rm -rf $ROOT_PATH/web/wordpress/wp-content/
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
	curl -L --output $DEPLOYER_PATH --progress-bar https://deployer.org/releases/v4.3.1/deployer.phar
	chmod +x $DEPLOYER_PATH
fi

# Update Git submodules
git submodule init
git submodule update --remote
