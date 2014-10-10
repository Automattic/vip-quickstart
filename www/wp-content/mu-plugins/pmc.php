<?php

add_action('wp_feed_options', function( $feed, $url ) {
	$feed->set_cache_duration( 12 * HOUR_IN_SECONDS );
}, 10, 2);

add_action( 'muplugins_loaded', function() {
	if ( WP_DEBUG ) {
		error_reporting( E_ALL );
	}
} );

add_filter('validate_current_theme', '__return_false');
add_filter( 'pre_site_transient_update_core', '__return_null' );

if ( !isset ( $GLOBALS['pagenow'] ) ) {
	$GLOBALS['pagenow'] = '';
}
