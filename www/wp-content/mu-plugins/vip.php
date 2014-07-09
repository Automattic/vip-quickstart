<?php

// Hide Custom Fields metabox
add_action( 'do_meta_boxes', function() {
	remove_meta_box( 'postcustom', get_post_type(), 'normal' );
});

// Upload size limit is 1GB
add_filter( 'upload_size_limit', function() {
	return 1073741824; // pow( 2, 30 )
});

// Use VIP Theme Review by default
add_filter( 'vip_scanner_default_review', function( $default, $review_types ) {
  return array_search( 'VIP Theme Review', $review_types );
}, 10, 2 );

// Submit themes to VIP support
add_filter( 'vip_scanner_email_to', 'vip_scanner_email_to' );
function vip_scanner_email_to() {
  // Disabled email submission.
  // return 'vip-support@wordpress.com';
}

// Change the default password, yo
add_action( 'admin_notices', function() {
	$current_user = wp_get_current_user();

	if ( 'wordpress' != $current_user->user_login )
		return;

	if ( ! wp_check_password( 'wordpress', $current_user->user_pass, $current_user->ID ) )
		return;

	?>

	<div class="error">
        <p><?php _e( '<strong>Warning:</strong> Change the default password.' ); ?></p>
    </div>

	<?php
});
