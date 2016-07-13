<?php

if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL || 1 === get_current_blog_id() ) {
    if ( ! defined( 'QUICKSTART_DISABLE_CONCAT' ) || ! QUICKSTART_DISABLE_CONCAT ) {
	require __DIR__ . '/http-concat/cssconcat.php';
	require __DIR__ . '/http-concat/jsconcat.php';
    }
}

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
add_filter( 'intermediate_image_sizes', 'wpcom_intermediate_sizes' );
function wpcom_intermediate_sizes ( $sizes ) {
    if ( ! defined( 'JETPACK_DEV_DEBUG' ) || ! JETPACK_DEV_DEBUG ) {
	    return array();
    }

    return $sizes;
}

// Check alloptions on every pageload
add_action( 'init', function() {
    $alloptions = wp_cache_get( 'alloptions', 'options' );
    $alloptions = apply_filters( 'alloptions', $alloptions );
});

// Load wpcom global.css
add_action( 'wp_head', 'global_css', 5 );

function global_css() {
	$scheme = is_ssl() ? 'https' : 'http';

	// wp_head action + echo are used instead of wp_enqueue_style, because these stylesheets must be loaded before the others
	wp_enqueue_style( 'h4-global', esc_url( $scheme . '://s0.wp.com/wp-content/themes/h4/global.css' ), array() );

	if ( is_rtl() )
		wp_enqueue_style( 'h4-global-rtl', esc_url( $scheme . '://s0.wp.com/wp-content/themes/h4/global-rtl.css' ), array() );
}

function wpcom_force_ssl_home_urls_in_content_when_secure( $content ) {
	if ( is_admin() ) {
		return $content;
	}

	if ( is_ssl() ) {
		return str_replace( home_url( '/', 'http' ), home_url( '/', 'https' ), $content );
	}

	return str_replace( home_url( '/', 'https' ), home_url( '/', 'http' ), $content );
}
add_filter( 'the_content', 'wpcom_force_ssl_home_urls_in_content_when_secure' );
add_filter( 'comment_text', 'wpcom_force_ssl_home_urls_in_content_when_secure' );
add_filter( 'widget_text', 'wpcom_force_ssl_home_urls_in_content_when_secure' );

wp_oembed_add_provider(
	'#https?://[^.]+\.(wistia\.com|wi\.st)/(medias|embed)/.*#',
	'https://fast.wistia.com/oembed',
	true
);

/**
 * Load a WordPress.com theme compat file, if it exists.
 */
function wpcom_load_theme_compat_file() {
    if ( ( ! defined( 'WP_INSTALLING' ) || 'wp-activate.php' === $GLOBALS['pagenow'] ) ) {
	// Many wpcom.php files call $themecolors directly. Ease the pain.
	global $themecolors;

	$template_path   = get_template_directory();
	$stylesheet_path = get_stylesheet_directory();
	$file            = '/inc/wpcom.php';

	// Look also in /includes as alternate location, since premium theme partners may use that convention.
	if ( ! file_exists( $template_path . $file ) && ! file_exists( $stylesheet_path . $file ) ) {
	    $file = '/includes/wpcom.php';
	}

	// Include 'em. Child themes first, just like core.
	if ( $template_path !== $stylesheet_path && file_exists( $stylesheet_path . $file ) ) {
	    include_once( $stylesheet_path . $file );
	}

	if ( file_exists( $template_path . $file ) ) {
	    include_once( $template_path . $file );
	}
    }
}
// Hook early so that after_setup_theme can still be used at default priority.
add_action( 'after_setup_theme', 'wpcom_load_theme_compat_file', 0 );


/**
 * Some slugs shouldn't be used on WordPress.com, as they conflict with actual resources
 */

add_filter( 'wp_unique_post_slug_is_bad_hierarchical_slug', 'wpcom_reserved_page_slugs', 10, 5 );

function wpcom_reserved_page_slugs( $is_reserved, $slug, $post_type ) {
	$reserved_page_slugs = array(
		'admin',
		'async-jobs',
		'bin',
		'blog-search',
		'botd',
		'conf',
		'ejabberd_',
		'error-docs',
		'forums-plugins',
		'forums-theme',
		'gadgets',
		'i',
		'imgpress',
		'login',
		'public-charts',
		'wlw',
		'wp-admin',
		'wp-content',
		'wp-includes'
	);

	$available_custom_post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );

	if ( ! empty( $available_custom_post_types ) ) {
		foreach( $available_custom_post_types as $acpt ) {
			$cpt_obj = get_post_type_object( $acpt );

			if ( ! empty( $cpt_obj ) && isset( $cpt_obj->rewrite ) && isset( $cpt_obj->rewrite['slug'] ) ) {
				$reserved_page_slugs[] = $cpt_obj->rewrite['slug'];
			}
		}
	}

	if ( 'page' == $post_type && in_array( $slug, $reserved_page_slugs ) ) {
		$is_reserved = true;
	}

	return $is_reserved;
}

// Allow hassle-free Liveblog testing in QS
if ( ! function_exists( 'wpcom_vip_is_liveblog_enabled' ) ) {
	function wpcom_vip_is_liveblog_enabled() {
		return true;
	}
}
