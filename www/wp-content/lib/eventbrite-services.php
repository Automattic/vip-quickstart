<?php
/*
Plugin Name: Eventbrite Services
Plugin URI: http://voceplatforms.com/
Description: Provides Eventbrite service, widgets, and features to supporting themes.
Author: Voce Communications
Author URI: http://voceplatforms.com/
Version: 1.2.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Load widgets (needs to happen before `init`).
 */
require( __DIR__ . '/eventbrite-widgets/eventbrite-widgets.php' );

/**
 * Load Eventbrite Services.
 */
function eventbrite_services_init() {
	// Fire up Keyring first.
	Keyring::init();

	// Load all remaining Eventbrite code.
	require( __DIR__ . '/eventbrite-api/eventbrite-api.php' );
	require( __DIR__ . '/voce-settings-api/voce-settings-api.php' );
	require( __DIR__ . '/eventbrite-settings/eventbrite-settings.php' );
	require( __DIR__ . '/suggested-pages-setup/suggested-pages-setup.php' );
	require( __DIR__ . '/tlc-transients/tlc-transients.php' );
	require( __DIR__ . '/php-calendar/calendar.php' );
}
add_action( 'init', 'eventbrite_services_init' );
