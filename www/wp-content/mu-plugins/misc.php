<?php

header( "X-hacker: If you're reading this, you should visit automattic.com/jobs and apply to join the fun, mention this header." );

// Hide Plugins menu
add_action( 'admin_menu', function() {
	global $_wp_menu_nopriv;

	remove_menu_page( 'plugins.php' );

	if ( ! current_user_can( 'manager_network' ) )
		$_wp_menu_nopriv['plugins.php'] = true;
});

// Hide Custom Fields metabox
add_action( 'do_meta_boxes', function() {
	remove_meta_box( 'postcustom', get_post_type(), 'normal' );
});
