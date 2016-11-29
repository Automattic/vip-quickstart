<?php

// Hide Custom Fields metabox
add_action( 'do_meta_boxes', function() {
	remove_meta_box( 'postcustom', get_post_type(), 'normal' );
});

// Upload size limit is 1GB
add_filter( 'upload_size_limit', function() {
	return 1073741824; // pow( 2, 30 )
});

// No upload limit for VIPs
add_filter( 'pre_site_option_upload_space_check_disabled', function(){
	return 1;
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

/**
 * Wrap core WordPress embeds in a div to allow easier targeting via CSS. Replicates WP.com behaviour
 */
add_filter( 'oembed_dataparse', 'wpcom_wrap_embeds_in_a_div', 5, 3 );
function wpcom_wrap_embeds_in_a_div( $html, $data, $url ) {
	if ( ! empty( $data->provider_name ) && ( 'video' == $data->type || 'rich' == $data->type ) ) {
		$html = '<div class="embed-' . esc_attr( strtolower( sanitize_html_class( $data->provider_name ) ) ) . '">' . $html . '</div>';
	}

	return $html;
}
