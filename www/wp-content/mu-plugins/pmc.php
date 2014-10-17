<?php

add_action('wp_feed_options', function( $feed, $url ) {
	$feed->set_cache_duration( 12 * HOUR_IN_SECONDS );
}, 10, 2);

add_action( 'muplugins_loaded', function() {
	if ( WP_DEBUG ) {
		error_reporting( E_ALL );
	}
	
	$_SERVER['SERVER_NAME'] = parse_url( get_home_url(), PHP_URL_HOST );
	
	if ( defined('WP_CLI') && WP_CLI ) {
		// force display errors
		if ( !ini_get( 'display_errors' ) ) {
			ini_set( 'display_errors', '1' );
		}
		ob_implicit_flush(true);
		if( 0 < ob_get_level() ) {
			ob_flush();
			ob_end_flush();
		}
	}

} );

add_filter('validate_current_theme', '__return_false');
add_filter( 'pre_site_transient_update_core', '__return_null' );

if ( !isset ( $GLOBALS['pagenow'] ) ) {
	$GLOBALS['pagenow'] = '';
}

/**
 * Alter the timeout on cron requests from 0.01 to 0.5. Something about
 * the Vagrant and/or Ubuntu setup doesn't like these self requests 
 * happening so quickly.
 */
add_filter( 'cron_request', 'jf_cron_request', 10, 1 );
function jf_cron_request( $cron_request ) {
	$cron_request['args']['timeout'] = (float) 0.5;
	return $cron_request;
}

