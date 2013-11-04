<?php

define( 'WPCOM_VIP_FLUSH_REWRITE_RULES_SECRET', 'NjI5NzU3MjZkNTcwZGNkY2I0MDNiNTcwN2ZjYzVmNzkzYTk1ZmVhMzY1OWE1Njgz' );

/**
 * Initiate the process to flush a site's rewrite rules
 */
function wpcom_initiate_flush_rewrite_rules( $_blog_id = null ) {

	if ( is_null( $_blog_id ) )
		$_blog_id = get_current_blog_id();

	$args = array(
		'action' => 'wpcom-vip-flush-rewrite-rules',
		'secret' => WPCOM_VIP_FLUSH_REWRITE_RULES_SECRET,
		);
	$request_url = add_query_arg( $args, get_home_url( $_blog_id ) );
	wp_remote_get( $request_url, array( 'blocking' => false ) );
}

/**
 * Always enable custom rewrite rules in VIP Quickstart
 */
function wpcom_theme_has_custom_rewrite_rules( $stylesheet = null ) {
	return true;
}

/**
 * When a VIP switches their theme, make a request to flush and reload their rules
 * It's less than ideal to do a remote request, but all of the new theme's code
 * won't be loaded on this request
 */
add_action( 'switch_theme', 'rri_wpcom_action_switch_theme' );
function rri_wpcom_action_switch_theme( $new_name ) {

	if ( !wpcom_theme_has_custom_rewrite_rules() )
		return;

	wpcom_initiate_flush_rewrite_rules();
}

/**
 * Flush rewrite rules for a given VIP
 * Most likely called from a post-commit job
 *
 * Usage: http://site.com/?action=wpcom-vip-flush-rewrite-rules&secret={WPCOM_VIP_FLUSH_REWRITE_RULES_SECRET}
 */
function wpcom_vip_handle_flush_rewrite_rules() {

	if ( ! isset( $_GET['action'] ) || ! $_GET['action'] == 'wpcom-vip-flush-rewrite-rules' )
		return;

	// Pass the secret key check
	if ( !isset( $_GET['secret' ] ) || $_GET['secret'] != WPCOM_VIP_FLUSH_REWRITE_RULES_SECRET )
		return;

	global $wp_rewrite;

	/**
	 * VIPs and other themes can declare the permastruct, tag and category bases in their themes.
	 * This is done by filtering the option. To ensure we're getting the proper data, refresh.
	 *
	 * However, wpcom_vip_refresh_wp_rewrite() noops the values in the database so we only want to run it
	 * if the permastructs are defined in the theme (e.g. not Enterprise)
	 */
	if ( ( defined( 'WPCOM_VIP_CUSTOM_PERMALINKS' ) && WPCOM_VIP_CUSTOM_PERMALINKS )
		|| ( defined( 'WPCOM_VIP_CUSTOM_CATEGORY_BASE' ) && WPCOM_VIP_CUSTOM_CATEGORY_BASE )
		|| ( defined( 'WPCOM_VIP_CUSTOM_TAG_BASE' ) && WPCOM_VIP_CUSTOM_TAG_BASE ) )
		wpcom_vip_refresh_wp_rewrite();

	/**
	 * We can't use flush_rewrite_rules( false ) in this context because
	 * on WPCOM it deletes the transient representation of rewrite_rules, not the option.
	 * For now, we need to do some code replication.
	 */
	$wp_rewrite->matches = 'matches';
	$wp_rewrite->rewrite_rules();
	update_option( 'rewrite_rules', $wp_rewrite->rules );

	wp_die( __( 'Rewrite rules have been flushed for ' . get_site_url() ) );
	exit;
}
add_action( 'init', 'wpcom_vip_handle_flush_rewrite_rules', 99999 );

/**
 * VIPs and other themes can declare the permastruct, tag and category bases in their themes.
 * This is done by filtering the option.
 *
 * To ensure we're using the freshest values, and that the option value is available earlier
 * than when the theme is loaded, we need to get each option, save it again, and then
 * reinitialize wp_rewrite.
 *
 * This is most commonly used in our code to flush rewrites
 */
function wpcom_vip_refresh_wp_rewrite() {
	global $wp_rewrite;

	// Permastructs available in the options table and their core defaults
	$permastructs = array(
		'permalink_structure' => '/%year%/%monthnum%/%day%/%postname%/',
		'category_base' => '',
		'tag_base' => '',
		);
	foreach( $permastructs as $option_key => $default_value ) {
		$filter = 'pre_option_' . $option_key;
		$callback = '_wpcom_vip_filter_' . $option_key;

		$option_value = get_option( $option_key );

		$reapply = has_filter( $filter, $callback );
		// If this value isn't filtered by the VIP, used the default wpcom value
		if ( !$reapply )
			$option_value = $default_value;
		else
			remove_filter( $filter, $callback, 99 );
		// Save the precious
		update_option( $option_key, $option_value );
		// Only reapply the filter if it was applied previously
		// as it overrides the option value with a global variable
		if ( $reapply )
			add_filter( $filter, $callback, 99 );
	}

	// Reconstruct WP_Rewrite and make sure we persist any custom endpoints, etc.
	$old_values = array();
	$custom_rules = array(
		'extra_rules',
		'non_wp_rules',
		'endpoints',
		);
	foreach( $custom_rules as $key ) {
		$old_values[$key] = $wp_rewrite->$key;
	}
	$wp_rewrite->init();
	foreach( $custom_rules as $key ) {
		$wp_rewrite->$key = array_merge( $old_values[$key], $wp_rewrite->$key );
	}
}
