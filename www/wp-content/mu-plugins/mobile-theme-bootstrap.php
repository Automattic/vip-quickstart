<?php

add_filter( 'jetpack_check_mobile', 'wpcom_check_mobile' );
function wpcom_check_mobile( $check_mobile_result ) {
	global $current_blog;

	if ( $check_mobile_result && 1 == get_option('wp_mobile_disable') )
		return false;

	// iPad or Tablet
	if ( Jetpack_User_Agent_Info::is_ipad() ) {
		$check_mobile_result = get_option( 'wp_ipad_template' );
	} elseif ( Jetpack_User_Agent_Info::is_tablet() ) {
		$check_mobile_result = get_option( 'wp_tablet_template' );
	}

	return $check_mobile_result;
}

add_filter( 'jetpack_mobile_stylesheet', 'wpcom_mobile_stylesheet', 10, 2 );
function wpcom_mobile_stylesheet( $wp_mobile_stylesheet, $theme ) {
	$wp_mobile_stylesheet = get_option( 'wp_mobile_template' );

	if ( ! $wp_mobile_stylesheet ) {
		$should_serve_smart_template = jetpack_is_mobile( 'smart' );

		if ( $should_serve_smart_template ) {
			if ( ! $wp_mobile_stylesheet = get_option( 'wp_mobile_smart_template' ) ) {
				$wp_mobile_stylesheet = 'pub/minileven';
			}
		} else {
			if ( ! $wp_mobile_stylesheet = get_option( 'wp_mobile_dumb_template' ) ) {
				$wp_mobile_stylesheet = 'pub/wp-mobile';
			}
		}

		// P2 has its iPhone CSS
		if ( jetpack_is_mobile( 'smart' ) && in_array( $theme, array( 'pub/p2', 'vip/p2vipsupport' ) ) ) {
			$wp_mobile_stylesheet = $theme;
		}
	}

	// iPad or Tablet
	if ( Jetpack_User_Agent_Info::is_ipad() && get_option( 'wp_ipad_template' ) ) {
		$wp_mobile_stylesheet = get_option( 'wp_ipad_template' );
	} elseif ( Jetpack_User_Agent_Info::is_tablet() && get_option( 'wp_tablet_template' ) ) {
		$wp_mobile_stylesheet = get_option( 'wp_tablet_template' );
	}

	return $wp_mobile_stylesheet;
}

add_filter( 'jetpack_mobile_template', 'wpcom_mobile_template', 10, 2 );
function wpcom_mobile_template( $wp_mobile_template, $theme ) {
	$wp_mobile_template = get_option( 'wp_mobile_template' );

	if ( ! $wp_mobile_template ) {
		$should_serve_smart_template = jetpack_is_mobile( 'smart' );

		if ( $should_serve_smart_template ) {
			if ( ! $wp_mobile_template = get_option( 'wp_mobile_smart_template' ) ) {
				$wp_mobile_template = 'pub/minileven';
			}
		}
		else {
			if ( ! ( $wp_mobile_template = get_option( 'wp_mobile_dumb_template' ) ) ) {
				$wp_mobile_template = 'pub/wp-mobile';
			}
		}

		// P2 has its iPhone CSS
		if ( jetpack_is_mobile( 'smart' ) && 'pub/p2' == $theme ) {
			$wp_mobile_template = $theme;
		}
	}

	// iPad or Tablet
	if ( Jetpack_User_Agent_Info::is_ipad() && get_option( 'wp_ipad_template' ) ) {
		$wp_mobile_template = get_option( 'wp_ipad_template' );
	} elseif ( Jetpack_User_Agent_Info::is_tablet() && get_option( 'wp_tablet_template' ) ) {
		$wp_mobile_template = get_option( 'wp_tablet_template' );
	}

	// Is it a child theme?
	$wp_mobile_template = get_parent_theme( $wp_mobile_template );

	return $wp_mobile_template;
}

function get_parent_theme( $theme ) {
	$template = $theme;
	$theme_data = wp_get_theme( $theme );

	if ( $theme_data->exists() && '' != $theme_data->Template )
		$template = $theme_data->Template;

	return $template;
}

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
