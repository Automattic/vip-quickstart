<?php

/**
 * Disable the infinite footer for Android devices, as it causes a browser crash
 * See http://wp.me/p2jjvm-qh
 *
 * @param array $settings
 * @uses Jetpack_User_Agent_Info
 * @filter infinite_scroll_settings
 * @return array
 */
function infinite_scroll_filter_settings( $settings ) {
    $ua_info = new Jetpack_User_Agent_Info();
    if ( $ua_info->is_android() )
        $settings['footer'] = false;

    return $settings;
}
add_filter( 'infinite_scroll_settings', 'infinite_scroll_filter_settings' );

/**
 * Only apply IS to homepage on dotcom.
 * Plugin supports all archives as part of Jetpack release.
 *
 * @param bool $supported
 * @uses is_home
 * @filter infinite_scroll_archive_supported
 * @return bool
 */
function infinite_scroll_archive_supported( $supported ) {
    return is_home();
}
add_filter( 'infinite_scroll_archive_supported', 'infinite_scroll_archive_supported' );

add_action( 'infinite_scroll_credit', 'wpcom_infinite_scroll_custom_credits' );
function wpcom_infinite_scroll_custom_credits( $credit ) {
    if ( function_exists( 'wpcom_is_vip' ) && wpcom_is_vip() )
        $credit = sprintf( __( 'Powered by <a href="%s" rel="generator">WordPress.com VIP</a>' ), 'http://vip.wordpress.com?ref=is-footer' );
    return $credit;
}

if ( class_exists( 'The_Neverending_Home_Page' ) ) :
/**
 * Show Post Flair on Infinite Scroll queries, only if it should show for regular queries as well.
 *
 * @param bool $show
 * @param bool $default
 * @uses The_Neverending_Home_Page::got_infinity
 * @filter post_flair_should_show
 * @return bool
 */
function wpcom_infinite_scroll_show_postflair( $show, $default ) {
    if ( $default && The_Neverending_Home_Page::got_infinity() )
        $show = true;

    return $show;
}

add_filter( 'post_flair_should_show', 'wpcom_infinite_scroll_show_postflair', 10, 2 );
endif;
