<?php

if ( ! defined( 'VIP_CUSTOM_MOBILE_TEMPLATE' ) )
    define( 'VIP_CUSTOM_MOBILE_TEMPLATE', '' );

if ( ! defined( 'VIP_CUSTOM_MOBILE_STYLESHEET' ) )
    define( 'VIP_CUSTOM_MOBILE_STYLESHEET', '' );

add_action( 'jetpack_modules_loaded', function() {
    if ( '' == VIP_CUSTOM_MOBILE_TEMPLATE || '' == VIP_CUSTOM_MOBILE_STYLESHEET )
        return;

    if ( ! jetpack_check_mobile() )
        return;

    do_action( 'mobile_setup' );

    // Remove Minileven's path overrides
    remove_filter( 'theme_root', 'minileven_theme_root' );
    remove_filter( 'theme_root_uri', 'minileven_theme_root_uri' );

    add_filter( 'jetpack_mobile_stylesheet', function( $stylesheet, $theme ) {
        return VIP_CUSTOM_MOBILE_STYLESHEET;
    }, 99, 2 );

    add_filter( 'jetpack_mobile_template', function ( $template, $theme ) {
            return VIP_CUSTOM_MOBILE_TEMPLATE;
    }, 99, 2 );
} );

add_action( 'mobile_setup', 'vip_maybe_include_functions_mobile_file' );
function vip_maybe_include_functions_mobile_file() {

    // Find the file in the current VIP theme
    $stylesheet      = get_stylesheet();
    $theme_root      = get_theme_root( $stylesheet );
    $stylesheet_path = "$theme_root/$stylesheet";
    $functions_path  = $stylesheet_path . '/functions-mobile.php';

    // Maybe include the functions-mobile.php file
    if ( file_exists( $functions_path ) && is_readable( $functions_path ) ) {
        require_once( $functions_path );
    }
}
