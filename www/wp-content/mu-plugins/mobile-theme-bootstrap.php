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
