<?php

/* Path to the WordPress codebase you'd like to test. Add a backslash in the end. */
define( 'ABSPATH', "/srv/www/wp/" );
define( 'WP_CONTENT_DIR', "/srv/www/wp-content" );

// Test with multisite enabled.
define( 'WP_TESTS_MULTISITE', true );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** MySQL settings ** //

// This configuration file will be used by the copy of WordPress being tested.
// wordpress/wp-config.php will be ignored.

// WARNING WARNING WARNING!
// These tests will DROP ALL TABLES in the database with the prefix named below.
// DO NOT use a production database or one that is shared with something else.

define( 'WP_DEFAULT_THEME', 'pub/twentyfifteen' );

define( 'DB_NAME', 'wptests' );
define( 'DB_USER', 'wptests' );
define( 'DB_PASSWORD', 'wptests' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix  = 'wptests_';   // Only numbers, letters, and underscores please!

define( 'WP_TESTS_DOMAIN', 'viptests.dev' );
define( 'WP_TESTS_EMAIL', 'admin@viptests.dev' );
define( 'WP_TESTS_TITLE', 'Fake Blog' );
define( 'WP_CACHE_KEY_SALT', 'viptest' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );

