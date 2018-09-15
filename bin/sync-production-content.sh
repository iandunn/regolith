#!/bin/bash

REGOLITH_DIR="$( dirname $( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd ) )"

source $REGOLITH_DIR/bin/helpers.sh
source $REGOLITH_DIR/config/deploy.sh


# Make sure all the required tools are available, and that the user understands the implications of using this.
function preflight_checks() {
	local environment=$( get_php_config 'REGOLITH_ENVIRONMENT' )
	local shell_commands=( 'ssh' 'wp' 'scp' 'gunzip' 'rsync' )

	if [ 'development' != "$environment" ]; then
		error_message "This should be run in a development environment, not in production."
		exit 1
	fi

	wp package path ivankruchkoff/wp-hammer &> /dev/null
	if [ $? != 0 ]; then
		error_message "This script requires the \`wp hammer\` command. \nPlease install it with \`wp package install ivankruchkoff/wp-hammer\` "
		exit 1
	fi

	for i in ${shell_commands[@]}; do
		command -v ${i} &> /dev/null

		if [ $? != 0 ]; then
			error_message "This script requires the \`$i\` command. \nPlease install it."
			exit 1
		fi
	done

	warning_message "This script will delete your local database and uploads, and replace them with copies from the production environment. It will attempt to scrub sensitive data -- like user passwords and email addresses -- to prevent them from being stored in local dev environments. Before running this, you should audit any sensitive data stored in your database and uploads, and then customize the script to scrub them. After you run it, check the results to make sure nothing sensitive was missed."
	warning_message "This doesn't support sanitizing a Multisite database yet."
	printf "\nAre you ready to proceed? (y/N) "
	read proceed

	if [ 'y' != "$proceed" ]; then
		printf "\nAborting.\n"
		exit 1
	fi
}

# Fetch a fresh copy of the production database and overwrite the local database with it.
function import_database() {
	printf "\n## Importing database...\n\n"

	# Strip color codes from the message so we can more easily check for "Success" substring
	local remote_db=$( ssh -tq $SSH_USERNAME@$SSH_HOSTNAME "wp regolith backup-database --path=$WP_PATH | tr -d '[:cntrl:]'" )

	if [ 'Success' != "${remote_db:0:7}" ]; then
		error_message "Failed to create a remote database backup. Aborting."
		exit 1
	fi

	remote_db=$( echo $remote_db | awk -F "'" '{print $2}' )

	local local_db=$REGOLITH_DIR/tmp/$(basename -- "$remote_db")

	scp $SSH_USERNAME@$SSH_HOSTNAME:$remote_db.gz $local_db.gz

	if [[ $? -eq 1 ]]; then
		error_message "Failed to download remote database backup. Aborting."
		exit 1
	fi

	gunzip $local_db.gz
	wp db import $local_db

	# Overwrite the file before deleting it, since it contains sensitive information.
	# This is unnecessary on SSDs, but those are not ubiquitous yet.
	# @link https://superuser.com/a/1208987/121091
	rm -Pf $local_db
}

# Update contents to match development site URLs, etc
#
# This isn't necessary for most things, because a lot is based on the `WP_HOME` and `WP_SITE_URL` constants
# hardcoded in config files. There are a few things that need it, though, like custom links in menus.
function localize_database() {
	printf "\n## Replacing production references with local environment values...\n\n"

	# Getting production hostname via SSH, because `wp_options.home` is bypassed by `WP_HOME`
	local remote_home=$( ssh -q $SSH_USERNAME@$SSH_HOSTNAME "wp option get home --path=$WP_PATH" )
	local local_home=$( get_php_config 'WP_HOME' )

	wp search-replace $remote_home $local_home --all-tables --report-changed-only
}

# Remove any sensitive data, since leaving it in dev environments is a security/privacy risk.
#
# Ideally this would be done before downloading the new database to the local machine, but unfortunately that's
# just not practical.
#
# For tables with significantly sensitive or unpredictable data, a safelist is used instead of blocklist, because
# it's safer. A blocklist is still used for benign tables, though, to reduce the maintenance burden.
function sanitize_database() {
	printf "\n## Sanitizing database...\n\n"

	local options_safelist="
		'active_plugins', 'active_sitewide_plugins', 'admin_email', 'allowedthemes', 'avatar_default', 'avatar_rating',
		'blacklist_keys', 'blog_charset', 'blog_public', 'blogdescription', 'blogname', 'can_compress_scripts',
		'category_base', 'category_children', 'close_comments_days_old', 'close_comments_for_old_posts',
		'comment_max_links', 'comment_moderation', 'comment_order', 'comment_registration', 'comment_whitelist',
		'comments_notify', 'comments_per_page', 'cron', 'current_theme', 'dashboard_widget_options', 'date_format',
		'db_version', 'default_category', 'default_comment_status', 'default_comments_page', 'default_email_category',
		'default_link_category', 'default_ping_status', 'default_pingback_flag', 'default_post_format', 'default_role',
		'embed_size_h', 'embed_size_w', 'finished_splitting_shared_terms', 'fresh_site', 'gmt_offset', 'hack_file', 'home',
		'html_type', 'image_default_align', 'image_default_link_type', 'image_default_size', 'initial_db_version',
		'large_size_h', 'large_size_w', 'link_manager_enabled', 'links_updated_date_format', 'medium_large_size_h',
		'medium_large_size_w', 'medium_size_h', 'medium_size_w', 'moderation_keys', 'moderation_notify', 'nav_menu_options',
		'new_admin_email', 'page_comments', 'page_for_posts', 'page_on_front', 'permalink_structure', 'ping_sites',
		'post_count', 'posts_per_page', 'posts_per_rss', 'recently_activated', 'recently_edited', 'require_name_email',
		'rewrite_rules', 'rss_use_excerpt', 'show_avatars', 'show_comments_cookies_opt_in', 'show_on_front',
		'sidebars_widgets', 'site_icon', 'siteurl', 'start_of_week', 'sticky_posts', 'stylesheet', 'stylesheet_root',
		'tag_base', 'template', 'template_root', 'theme_switched', 'thread_comments', 'thread_comments_depth',
		'thumbnail_crop', 'thumbnail_size_h', 'thumbnail_size_w', 'time_format', 'timezone_string', 'uninstall_plugins',
		'upload_path', 'upload_url_path', 'uploads_use_yearmonth_folders', 'use_balanceTags', 'use_smilies', 'use_trackback',
		'users_can_register', 'wp_page_for_privacy_policy', 'wp_user_roles', 'WPLANG' "

	local user_meta_safelist="
		'admin_color', 'closedpostboxes_dashboard', 'closedpostboxes_dashboard-network', 'closedpostboxes_page',
		'closedpostboxes_post', 'comment_shortcuts', 'description', 'dismissed_wp_pointers',
		'edit_category_per_page', 'edit_comments_per_page', 'edit_post_per_page', 'edit_post_tag_per_page',
		'first_name', 'last_name', 'locale', 'managenav-menuscolumnshidden', 'meta-box-order_dashboard-network',
		'meta-box-order_post', 'metaboxhidden_dashboard', 'metaboxhidden_dashboard-network',
		'metaboxhidden_nav-menus', 'metaboxhidden_page', 'metaboxhidden_post', 'nickname', 'primary_blog',
		'rich_editing', 'screen_layout_post', 'show_admin_bar_front', 'show_try_gutenberg_panel',
		'show_welcome_panel', 'source_domain', 'use_ssl', 'wp_dashboard_quick_press_last_post_id' "


	wp hammer -f users.user_pass=password,users.user_email='redacted+__ID__@example.org'

	# @todo Replace with `wp hammer -f comments.comment_author_email='redacted+__comment_ID__@example.org'`
	# when https://github.com/10up/wp-hammer/pull/9 is merged
	wp db query "UPDATE $(wp db prefix)comments SET comment_author_email = 'redacted@example.org' "

	# Note that production's `nonce_key|salt` rows will be deleted, and then fresh random values will be
	# re-created as a by-product of running `wp db query`.
	#
	# @ todo Replace with `wp hammer -s options,usermeta` when https://github.com/10up/wp-hammer/pull/12
	# is merged.
	wp db query "
		DELETE FROM $(wp db prefix)options
		WHERE
			option_name NOT IN ( $options_safelist ) AND
			option_name NOT LIKE 'widget_%' AND
			option_name NOT LIKE 'theme_mods_%'
	"

	wp db query "
		DELETE FROM $(wp db prefix)usermeta
		WHERE
			meta_key NOT IN ( $user_meta_safelist ) AND
			meta_key NOT LIKE '%_capabilities' AND
			meta_key NOT LIKE '%_user_level'
	"

	wp db query "UPDATE $(wp db prefix)postmeta SET meta_value = 'redacter@example.org' WHERE meta_key = '_sg_subscribe-to-comments' "
	# todo if exists: wp db query "UPDATE $(wp db prefix)frm_item_metas SET meta_value = 'redacted' "

	success_message "Sanitized database."
}

# Sync `wp-content/uploads`.
function import_uploads() {
	printf "\n## Syncing uploads...\n\n"

	local root_dir=$( get_php_config REGOLITH_ROOT_DIR )
	local content_dir=$( get_php_config WP_CONTENT_DIR )
	local relative_content_dir=${content_dir/$root_dir\//""}

	rsync -rave "ssh -l $SSH_USERNAME" --delete "$SSH_HOSTNAME:$REPO_CHECKOUT/$relative_content_dir/uploads/" "$content_dir/uploads/"

	success_message "Synced uploads."
}

preflight_checks
import_database
localize_database
sanitize_database
import_uploads
