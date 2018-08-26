#!/bin/bash

#
# Install any dependencies that are missing from the current environment.
# Updates are handled by Core's auto-update mechanism.
#

ROOT_PATH=$( dirname $( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd ) )
ENVIRONMENT=$( grep 'REGOLITH_ENVIRONMENT' $ROOT_PATH/config/environment.php | awk -F "'" '{print $4}' )
PLUGINS=$( grep 'content/plugins/' $ROOT_PATH/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )
THEMES=$(  grep 'content/themes/'  $ROOT_PATH/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )

cd $ROOT_PATH

# Setup untracked files and folders
mkdir -p $ROOT_PATH/tmp
mkdir -p $ROOT_PATH/logs
touch $ROOT_PATH/logs/httpd-access.log
touch $ROOT_PATH/logs/httpd-errors.log
touch $ROOT_PATH/logs/php-errors.log

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

	# Activate whatever theme is available, since the bundled themes were deleted above.
	# Otherwise the user will get a confusing blank page on the front end, but no errors.
	wp theme activate $(wp theme list --format=csv |awk 'NR==2' |awk -F ',' '{print $1}')

	# This is required in order to make the REST API (and Regolith's endpoints) work.
	wp option update permalink_structure "/%postname%/"
fi

# Install plugins/themes
echo ""
wp plugin install $PLUGINS
echo ""
wp theme install $THEMES
