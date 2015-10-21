<?php

if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL || 1 === get_current_blog_id() ) {
    if ( ! defined( 'QUICKSTART_DISABLE_CONCAT' ) || ! QUICKSTART_DISABLE_CONCAT ) {
	require __DIR__ . '/http-concat/cssconcat.php';
	require __DIR__ . '/http-concat/jsconcat.php';
    }
}

// Add X-hacker header
add_action( 'send_headers', function() {
	header( "X-hacker: If you're reading this, you should visit automattic.com/jobs and apply to join the fun, mention this header." );
});

add_action( 'admin_menu', function() {
	// Hide Plugins menu
	remove_menu_page( 'plugins.php' );

	// Hide Permalinks menu
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );
});

// Turn on global terms
add_filter( 'global_terms_enabled', '__return_true' );

// Disable automatic creation of intermediate images
add_filter( 'intermediate_image_sizes', function( $sizes ) {
    if ( ! defined( 'JETPACK_DEV_DEBUG' ) || ! JETPACK_DEV_DEBUG )
	return array();

    return $sizes;
});

// Check alloptions on every pageload
add_action( 'init', function() {
    $alloptions = wp_cache_get( 'alloptions', 'options' );
    $alloptions = apply_filters( 'alloptions', $alloptions );
});

// Load wpcom global.css
add_action( 'wp_head', 'global_css', 5 );

function global_css() {
	// wp_head action + echo are used instead of wp_enqueue_style, because these stylesheets must be loaded before the others
	wp_enqueue_style( 'h4-global', 'http://s0.wp.com/wp-content/themes/h4/global.css', array() );

	if ( is_rtl() )
		wp_enqueue_style( 'h4-global-rtl', 'http://s0.wp.com/wp-content/themes/h4/global-rtl.css', array() );
}

function require_lib( $slug ) {
	if ( !preg_match( '|^[a-z0-9/_.-]+$|i', $slug ) ) {
		trigger_error( "Cannot load a library with invalid slug $slug.", E_USER_ERROR );
		return;
	}
	$basename = basename( $slug );

	if ( defined( 'ABSPATH' ) && ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' ); // no trailing slash, full paths only - WP_CONTENT_URL is defined further down
	}

	$lib_dir = WP_CONTENT_DIR . '/lib';
	$lib_dir = apply_filters( 'require_lib_dir', $lib_dir );
	$choices = array(
		"$lib_dir/$slug.php",
		"$lib_dir/$slug/0-load.php",
		"$lib_dir/$slug/$basename.php",
	);
	foreach( $choices as $file_name ) {
		if ( is_readable( $file_name ) ) {
			require_once $file_name;
			return;
		}
	}
	trigger_error( "Cannot find a library with slug $slug.", E_USER_ERROR );
}
