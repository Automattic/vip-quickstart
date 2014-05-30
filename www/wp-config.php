<?php

/**
 * Local mods
 */
if ( file_exists( __DIR__ . '/local-config.php' ) )
    require __DIR__ . '/local-config.php';

/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
if ( ! defined( 'DB_NAME' ) )
    define('DB_NAME', 'wordpress');

/** MySQL database username */
if ( ! defined( 'DB_USER' ) )
    define('DB_USER', 'wordpress');

/** MySQL database password */
if ( ! defined( 'DB_PASSWORD' ) )
    define('DB_PASSWORD', 'wordpress');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
if ( ! defined( 'WPLANG' ) )
    define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', true);
define('SAVEQUERIES', true);

if ( ! defined( 'JETPACK_DEV_DEBUG' ) )
    define('JETPACK_DEV_DEBUG', true);

if ( ! defined( 'MP6_STYLE_GUIDE' ) )
    define('MP6_STYLE_GUIDE', true);

/* Content Directory */
define( 'WP_CONTENT_DIR', dirname( __FILE__ ) . '/wp-content' );
define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/wp-content' );

define('MULTISITE', true);
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

if ( ! defined( 'DOMAIN_CURRENT_SITE' ) )
    define('DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST']);

if ( ! defined( 'SUBDOMAIN_INSTALL' ) )
    define('SUBDOMAIN_INSTALL', false);

if ( ! defined( 'WP_DEFAULT_THEME' ) )
	define('WP_DEFAULT_THEME', 'pub/twentyfourteen');

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
if ( !defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
