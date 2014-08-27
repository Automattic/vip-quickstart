<?php

function sanity_check_alloptions( $alloptions ) {

	if ( defined( 'WP_CLI' ) && WP_CLI )
		return $alloptions;

	// Warn should *always* be =< die
	$alloptions_size_warn  =  750000;
	$alloptions_size_die   = 1000000; // 1000000 ~ 1MB, too big for memcache
	
	static $alloptions_size = null; // Avoids repeated cache requests
	if ( !$alloptions_size )
		$alloptions_size = wp_cache_get( 'alloptions_size' );
	
	if ( $alloptions_size > $alloptions_size_die ) {
		sanity_check_alloptions_die( $alloptions_size, $alloptions );
	}
	
	if ( !$alloptions_size ) {
		$alloptions_size = strlen( serialize( $alloptions ) );
		wp_cache_add( 'alloptions_size', $alloptions_size, '', 60 );
		if ( $alloptions_size > $alloptions_size_warn ) {
			if ( $alloptions_size > $alloptions_size_die )
				sanity_check_alloptions_die( $alloptions_size, $alloptions );
				
			// Warn if we haven't died already
			sanity_check_alloptions_notify( $alloptions_size, $alloptions );
		}
	}
	
	return $alloptions;
}
add_filter( 'alloptions', 'sanity_check_alloptions' );

function sanity_check_alloptions_die( $size, $alloptions ) {
	sanity_check_alloptions_notify( $size, $alloptions, true );
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Something went wrong &#8212; Option Error</title>
<style type="text/css">
h1 {
	font-weight: normal;
	font-size: 40px;
}
body {
line-height: 1.6em; font-family: Georgia, serif; width: 390px; margin: auto;
text-align: center;
}
.message {
	font-size: 22px;
	width: 400px;
	margin: 10px auto;
}
</style>
<script type="text/javascript" src="//stats.wp.com/wpcom.js?22"></script>
<script type="text/javascript">
_error = 'big-alloptions';
fzd();
</script>
</head>
<body>
<h1>Uh Oh!</h1>

<div class="message">

<p>Something has gone wrong with our servers. It&#8217;s probably Matt&#8217;s fault.</p>

<p>We&#8217;ve just been notified of the problem.</p>

<p>Hopefully this should be fixed ASAP, so kindly reload in a few minutes and things should be back to normal.</p> 

</div>
</body>
</html>
	<?php
	exit;
}

function sanity_check_alloptions_notify( $size, $alloptions, $blocked = false ) {
	global $wpdb, $current_blog;

	// Rate limit the alerts to avoid flooding
	if ( false !== wp_cache_get( 'alloptions', 'throttle' ) )
		return;

	wp_cache_add( 'alloptions', 1, 'throttle', 5 * MINUTE_IN_SECONDS );

	if ( $blocked )
		$msg = "This site is now BLOCKED from loading until option sizes are under control.";
	else
		$msg = "Site will be blocked from loading if option sizes get too much bigger.";

	$log = esc_url( $_SERVER['HTTP_HOST'] ) . " - {$wpdb->blogid} options is up to " . number_format( $size ) . ' ' . $msg . ' #vipoptions'; 
	error_log( $log );
}
