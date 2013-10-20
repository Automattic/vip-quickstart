<?php

// Add X-hacker header
add_action( 'send_headers', function() {
	header( "X-hacker: If you're reading this, you should visit automattic.com/jobs and apply to join the fun, mention this header." );
});

// Hide Plugins menu
add_action( 'admin_menu', function() {
	remove_menu_page( 'plugins.php' );
});

// Hide Custom Fields metabox
add_action( 'do_meta_boxes', function() {
	remove_meta_box( 'postcustom', get_post_type(), 'normal' );
});

// Upload size limit is 1GB
add_filter( 'upload_size_limit', function() {
	return 1073741824; // pow( 2, 30 )
});
