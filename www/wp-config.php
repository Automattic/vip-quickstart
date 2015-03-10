<?php

/**
 * Local mods
 */
if ( file_exists( __DIR__ . '/local-config.php' ) ) {
	require __DIR__ . '/local-config.php';
}

if ( ! defined( 'DB_NAME' ) ) {
	define( 'DB_NAME', 'wordpress' );
}

if ( ! defined( 'DB_USER' ) ) {
	define( 'DB_USER', 'wordpress' );
}

if ( ! defined( 'DB_PASSWORD' ) ) {
	define( 'DB_PASSWORD', 'wordpress' );
}

define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/** Disable Automatic core updates. */
define( 'WP_AUTO_UPDATE_CORE', false );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
if ( ! defined( 'WPLANG' ) ) {
	define( 'WPLANG', '' );
}

define( 'WP_DEBUG', true );
define( 'SAVEQUERIES', true );

if ( ! defined( 'JETPACK_DEV_DEBUG' ) ) {
	define( 'JETPACK_DEV_DEBUG', true );
}

/* Content Directory */
define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/wp-content' );
define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/wp-content' );

define( 'MULTISITE', true );
define( 'SUNRISE', true );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

if ( ! defined( 'DOMAIN_CURRENT_SITE' ) ) {
	define( 'DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST'] );
}

if ( ! defined( 'SUBDOMAIN_INSTALL' ) ) {
	define( 'SUBDOMAIN_INSTALL', false );
}

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'pub/twentyfourteen' );
}

define( 'WP_MAX_MEMORY_LIMIT', '1024M' );

/* That's all, stop editing! Happy blogging. */

// Use the latest Jetpack user-agent detection if we have it
if ( file_exists( __DIR__ . '/wp-content/plugins/jetpack/class.jetpack-user-agent.php' ) ) {
	require_once( __DIR__ . '/wp-content/plugins/jetpack/class.jetpack-user-agent.php' );
} else {
	require __DIR__ . '/config/is-mobile.php';
}

require __DIR__ . '/config/batcache-config.php';
require __DIR__ . '/config/roles.php';
require __DIR__ . '/config/vip-config.php';

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
