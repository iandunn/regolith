#!/bin/bash

#
# Install any dependencies that are missing from the current environment.
# Updates are handled by Core's auto-update mechanism.
#

REGOLITH_DIR=$( dirname $( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd ) )
ENVIRONMENT=$( grep 'REGOLITH_ENVIRONMENT' $REGOLITH_DIR/config/environment.php | awk -F "'" '{print $4}' )
PLUGINS=$( grep 'content/plugins/' $REGOLITH_DIR/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )
THEMES=$(  grep 'content/themes/'  $REGOLITH_DIR/.gitignore |awk -F '/' '{print $5}' | tr '\n' ' ' )

cd $REGOLITH_DIR

# Setup untracked files and folders
mkdir -p $REGOLITH_DIR/tmp
mkdir -p $REGOLITH_DIR/logs
touch $REGOLITH_DIR/logs/httpd-access.log
touch $REGOLITH_DIR/logs/httpd-errors.log
touch $REGOLITH_DIR/logs/php-errors.log

# Install Core
if [[ ! -d $REGOLITH_DIR/web/wordpress/wp-admin ]]; then
	echo ""
	wp core download

	# The default content directory isn't used by Regolith, and Core won't update it, so it'll just sit there with
	# old (and potentially vulnerable) plugins/themes.
	rm -rf $REGOLITH_DIR/web/wordpress/wp-content/
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
