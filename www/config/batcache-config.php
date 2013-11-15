<?php

define( 'WP_CACHE', true );

// utility variables that are use a bunch of times
$_batcache_script_name = basename( $_SERVER['SCRIPT_NAME'] );

// VIP Quickstart: Fix module activation
$_module = isset( $_GET['module'] ) ? $_GET['module'] : null;

$__batcacheignore_args = array(); // which query params to ignore
$__batcacheignore_args2 = array(); // which query params to ignore
if ( isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING'] ) {
	$__query_args = explode( '&', $_SERVER['QUERY_STRING'] );
	$__batcacheignore_defaults = array(
		'hpt', 'eref', 'iref', 'fbid', 'om_rid', 'utm_source', 'utm_content', 'utm_medium', 'utm_campaign', 'utm_term', 'fb_xd_bust', 'fb_xd_fragment', 'npt', 'module', 'iid', 'icid', 'ncid', 'snapid', '_',
	);

	foreach( $__batcacheignore_defaults as $bkey => $__batcacheignore_default ) {
		foreach( $__query_args as $qkey => $__query_arg ) {
			if ( $__query_arg == $__batcacheignore_default || 0 === stripos( $__query_arg, "$__batcacheignore_default=" ) ) {
				$__batcacheignore_args[] = $__batcacheignore_default;
				break;
			}
		}
	}
}

// These are cookies that would normally prohibit caching, but
// we want to serve cached pages to these folks anyway
$batcache['noskip_cookies'] = array(
	'wordpress_test_cookie',
	'wordpress_eli',
	'wpcom-browser-extension-promos-chrome',
	'wpcom-browser-extension-promos-firefox',
	'wpcom_geo',
);

// These variables are for the default configuration. Domain-specific configs follow.

$batcache['max_age'] =  300; // Expire batcache items aged this many seconds (zero to disable supercache)
$batcache['remote']  =    0; // whether to replicate the cache across datacenters (req/sec not replicated)

$batcache['times']   =    4; // Only batcache a page after it is accessed this many times... (two or more)
$batcache['seconds'] =   60; // ...in this many whole seconds (zero to ignore this and use batcache immediately)

$batcache['group'] = 'supercache'; // Name of memcached group

// Unset all the explicitly ignored GET args to make caching more efficient
if ( $__batcacheignore_args ) {
	// batcache requires a normalized query string
	$_SERVER['QUERY_STRING'] = preg_replace( '#((' . implode('|', $__batcacheignore_args) . ')=[^&]*&?)#', '', $_SERVER['QUERY_STRING'] );
	// batcache requires an empty get superglobal
	foreach ( $__batcacheignore_args as $getarg )
		unset( $_GET[$getarg] );
}

// Special signup page
if ( '/wp-signup.php' == $_SERVER['PHP_SELF'] && isset( $_COOKIE['ref'] ) && '360' == $_COOKIE['ref'] ) {
	$batcache['max_age'] = 0; // disable batcache
}

// Don't cache PHP files except the root index.php
if ( substr($_SERVER['REQUEST_URI'], -4) == '.php' && ( '/index.php' != $_SERVER['REQUEST_URI'] ) ) {
	$batcache['max_age'] = 0; // disable batcache
}
if ( substr($_SERVER['REQUEST_URI'], -3) == '.js' ) {
	$batcache['max_age'] = 0; // disable batcache
}
if ( substr($_SERVER['REQUEST_URI'], -10) == 'robots.txt' ) {
	$batcache['max_age'] = 0; // disable batcache
}
/*  Disable batcache for /feed/ requests where there is no query string set
 *  We cache these responses in nginx and having multiple layers of caching
 *  with no batcache invalidations results in stale data being served and
 *  subsequently cached
 *  */
if ( $_SERVER['REQUEST_URI'] == '/feed/' &&  $_SERVER['QUERY_STRING'] == '' )
	$batcache['max_age'] = 0; // disable batcache

if ( $_SERVER['REQUEST_URI'] == '/sitemap.xml' &&  $_SERVER['QUERY_STRING'] == '' )
	$batcache['max_age'] = 0; // disable batcache


// Mobile batcache
if( jetpack_is_mobile() && isset( $_COOKIE['akm_mobile'] ) )
	$batcache['unique']['mobile-force'] = $_COOKIE['akm_mobile'] == 'true' ? 'true' : 'false'; // the akm_mobile cookie is used to force the desktop version of the site when on a mobile device

if ( Jetpack_User_Agent_Info::is_blackbeberry() ) {
	$batcache['unique']['mobile'] = 'blackberry';
} elseif ( Jetpack_User_Agent_Info::is_WindowsPhone7() ) {
		$batcache['unique']['mobile'] = 'windows-phone7';
} elseif ( Jetpack_User_Agent_Info::is_S60_OSSBrowser() ) {
	$batcache['unique']['mobile'] = 'dumb';
} elseif ( in_array( jetpack_is_mobile( 'smart', true ), array( 'iphone', 'ipod' ) ) ) {
	$batcache['unique']['mobile'] = 'iphone';
} elseif ( jetpack_is_mobile( 'smart' ) ) {
	$batcache['unique']['mobile'] = 'smart';
} elseif ( jetpack_is_mobile( 'dumb' ) ) {
	$batcache['unique']['mobile'] = 'dumb';
}

// iPad
if ( Jetpack_User_Agent_Info::is_ipad() ) {
	if ( false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'safari' ) )
		$batcache['unique']['ipad'] = 'ipad-safari';
	else
		$batcache['unique']['ipad'] = 'ipad';
} elseif ( Jetpack_User_Agent_Info::is_tablet() ) {
	// Tablets
	// Should be treated differently from mobile and iPad
	$batcache['unique']['tablet'] = 'tablet';
}

// UppSite / MySiteApp
if ( ( isset($_SERVER['HTTP_USER_AGENT']) && false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'mysiteapp' ) ) || isset( $_COOKIE[ 'uppsite_theme_select' ] ) )
	$batcache['max_age'] = 0; // disable batcache

// Chrome Frame
if ( isset($_SERVER['HTTP_USER_AGENT']) && false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'chromeframe' ) )
	$batcache['unique']['chromeframe'] = true;

// Liveblog
if ( preg_match( '%__liveblog_\d+/\d+/\d+|/liveblog/\d+/\d+/?$%', $_SERVER['REQUEST_URI'] ) ) {
	// Liveblog requests include the current time (to the second), so it's important that we don't
	// wait and that we cache starting from the first request
	$batcache['seconds'] = 0;
	$batcache['times'] = 0;
	// A cached entry should be requested only for a couple of seconds (+/- clock errors), we don't need the whole 5 minutes
	$batcache['max_age'] = 30;
}

// GET param caching: start
// Never cache if there is a query string unless the script is whitelisted here
// But, some sites/features have GET params deeply integrated with their sites for various reasons. Add overrides below to cache them
$_batcache_cache_get_requests = false;

// Finally, should we cache GET requests?
if ( ! empty( $_GET ) && false === $_batcache_cache_get_requests ) {
	$batcache['max_age'] = 0;
}
// GET param caching: end

// Finally, blanket exceptions override all other configs

// Never batcache when run from CLI
if ( isset( $_SERVER[ 'argv' ] ) )
	$batcache['max_age'] = 0;

// CampTix restricted content token cookie
if ( isset( $_COOKIE['tix_view_token'] ) )
	$batcache['max_age'] = 0;

$batcache['headers']['X-nananana'] = 'Batcache';

unset( $_batcache_script_name );

// VIP Quickstart: Fix module activation
$_GET['module'] = $_module;

// UNCOMMENT THIS LINE TO DISABLE batcache -- KEEP THIS LAST
// $batcache['max_age'] = 0;
