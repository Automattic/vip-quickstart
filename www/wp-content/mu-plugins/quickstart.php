<?php

// On server installs (such as the QS AMI), we need to support arbitrary domains, so filter urls
// to the current HTTP_HOST
add_filter( 'site_url', 'wpcom_vip_quickstart_fix_domain', 9999, 4 );
add_filter( 'home_url', 'wpcom_vip_quickstart_fix_domain', 9999, 4 );

function wpcom_vip_quickstart_fix_domain( $url, $path, $scheme = null, $blog_id = null ) {
	// Only apply this customization to blog 1 (which is the default installed by QS)
	if ( ! is_null( $blog_id ) && 1 != $blog_id ) {
		return $url;
	}

	$host = parse_url( $url, PHP_URL_HOST );

	if ( $host != $_SERVER['HTTP_HOST'] ) {
		$url = str_replace( $host, $_SERVER['HTTP_HOST'], $url );
	}

	return $url;
}

// Required to prevent infinite loop redirections from /wp-admin/network when the domain does not match 
// what is in the DB, for example, in the AWS AMI
add_filter( 'redirect_network_admin_request', '__return_false' );
