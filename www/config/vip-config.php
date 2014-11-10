<?php

/**
 * Disable unfiltered HTML; this a default in Multisite, but we should extra enforce it
 */
define( 'DISALLOW_UNFILTERED_HTML', true );

/**
 * Disable file editing and WordPress updates
 */
define('DISALLOW_FILE_MODS', true);

/**
 * (Sort-of) Compat with wpcom-geo config
 *
 * On WP.com, this is automatically detected for the visiting user.
 * Here, we fake it for performance reasons.
 */
if ( defined( 'GEOIP_COUNTRY_CODE_OVERRIDE' ) ) {
	$_SERVER[ "GEOIP_COUNTRY_CODE" ] = GEOIP_COUNTRY_CODE_OVERRIDE;
} else {
	$_SERVER[ "GEOIP_COUNTRY_CODE" ] = 'us';
}
