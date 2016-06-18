<?php

/*
 * This installation is based on https://github.com/iandunn/regolith. See that URL for background information
 * and details.
 */

/*
 * There has to be an `index.php` in the web root for this setup to work. So this is just a future-proof wrapper for
 * Core's `index.php`.
 */
require_once( __DIR__ . '/wordpress/index.php' );
