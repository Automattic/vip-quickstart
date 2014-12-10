<?php

if ( file_exists( dirname( __FILE__ ) . '/local-sunrise.php' ) ) {
	require_once( dirname( __FILE__ ) . '/local-sunrise.php' );
}

// If we can't identify the current site, assume it's blog_id = 1
if ( ! get_site_by_path( $_SERVER['HTTP_HOST'], '/' ) ) {
	$current_blog = $wpdb->get_row( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = 1 LIMIT 1" );
	$current_site = $wpdb->get_row( "SELECT * from {$wpdb->site} WHERE id = '{$current_blog->site_id}' LIMIT 0,1" );
	$current_site->blog_id 	= 1;
	$current_site->domain 	= $_SERVER['HTTP_HOST'];
}
