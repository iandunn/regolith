#!/bin/bash

DEFAULT_COLOR=$(echo -en '\033[0m')
RED=$(echo -en '\033[00;31m')
GREEN=$(echo -en '\033[00;32m')
YELLOW=$(echo -en '\033[00;33m')

# Get the value of a Regolith configuration option.
#
# This is kind of icky. Another approach would be to parse the raw files with awk|sed, but that has its own
# problems, like dealing with the coding style (single vs double quotes, spacing etc, different ways to
# define a constant, etc. One advantage of this method is that it can handle runtime evaluation (i.e.,
# PHP sets `FORCE_SSL_ADMIN` based on the value of `REGOLITH_ENVIRONMENT`).
#
# A simpler approach would be to just have a BASH config file, where any needed values were duplicated from
# the PHP config files. While simpler, that would introduce the likely event of human error where a config
# value was updated in one place but not the other.
#
# Basically, passing values between PHP and BASH is gonna suck no matter what, and it seems like this method
# sucks in ways that are preferable.
#
# $1 - The name of the constant or variable. e.g., 'FOO_BAR' or '$foo_bar'.
function get_php_config() {
	local value
	local globalize=""

	# Tell Xdebug to not print the last call stack entry before the variable info, because otherwise it's
	# more difficult to parse out the data we need.
	local terse_var_dump="ini_set( 'xdebug.overload_var_dump', 0 );"

	# `wp eval` executes the code within a method, so global variables need to be explicitly brought into
	# the method scope.
	if [[ '$' = "${1:0:1}" ]]; then
		globalize="global $1;"
	fi

	local dump=$( wp eval "$terse_var_dump $globalize var_dump( $1 );" )
	local type=$( echo $dump | cut -d'(' -f 1 )

	case $type in
		"string" )
			value=$( echo $dump | cut -d'"' -f 2 )
		;;

		"bool" )
			value=$( echo $dump | cut -d'(' -f 2 )

			# Convert to BASH convention where any value means true and unset variable means false
			if [ 'true)' == $value ]; then
				value=1
			else
				value=
			fi
		;;

		* )
			error_message "Only strings and booleans are supported."
			exit 1
		;;
	esac

	echo "$value"
}

# Unit tests for get_php_config
function test_get_php_config() {
	const_string=$(     get_php_config "REGOLITH_ENVIRONMENT" )
	const_bool_true=$(  get_php_config "SAVEQUERIES"          )
	const_bool_false=$( get_php_config "FORCE_SSL_ADMIN"      )
	var_bool_true=$(    get_php_config '$cache_enabled'       )
	var_bool_false=$(   get_php_config '$cache_compression'   )
	var_string=$(       get_php_config '$table_prefix'        )

	echo "const string: $const_string"
	echo "var string: $var_string"

	if [ $const_bool_true ]; then
		echo "Success: const true is true"
	else
		echo "Failure: const true is false"
	fi

	if [ $const_bool_false ]; then
		echo "Failure: const false is true"
	else
		echo "Success: const false is false"
	fi

	if [ $var_bool_true ]; then
		echo "Success: var true is true"
	else
		echo "Failure: var true is false"
	fi

	if [ $var_bool_false ]; then
		echo "Failure: var false is true"
	else
		echo "Success: var false is false"
	fi
}

# The a success message.
#
# $1 - The message to print
# $2 - "terse" to avoid prefixing/postfixing the message with new lines
function success_message() {
	local newline="\n"

	if [ "terse" == $2 ]; then
		newline=""
	fi

	printf "$newline${GREEN}Success:${DEFAULT_COLOR} $1$newline"
}

# The a warning message.
#
# $1 - The message to print
function warning_message() {
	printf "\n${YELLOW}WARNING:${DEFAULT_COLOR} $1\n"
}

# The a failure message.
#
# $1 - The message to print
function error_message() {
	printf "\n${RED}ERROR:${DEFAULT_COLOR} $1\n"
}
