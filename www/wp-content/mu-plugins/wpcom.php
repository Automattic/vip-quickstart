<?php

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
add_filter( 'intermediate_image_sizes', function() {
    return array();
});
