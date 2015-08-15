<?php
/*
add following line of code to local-config.php to activate:
require_once( __DIR__ . '/wp-content/mu-plugins/pmc-qa.php');
*/

/**
 * This class is reponsible for setting up theme for qa site where there are multiple versions
 * of the theme that are served over a subdomain, eg. http://[site-slug].qa.pmc.com, http://qa.[site-slug].com
 * The main subdomain default wp content is located /srv/www/wp-content/
 * The QA branch admin url will be http://[qa-branch].[site-slug].qa.pmc.com/wp-admin
 * the QA branch site url will be http://[qa-branch].qa.[site-slug].com
 * The QA branch wp content is located at /srv/www/wp-content-sites/
 *
 */


// execute this code in local-config before anything else
if ( ! function_exists( 'add_action' ) ) {

	// set flag to indicate code has been run
	define('PMC_THEME_SWITCH',true);

	// use anonymous function to create a local scope to avoid global variables conflict
	call_user_func(function(){
		$network_domain    = 'qa.pmc.com';
		$site_slug         = '';
		$prefix            = '';
		$is_network_domain = false;
		$redirect_to       = false;

		if ( $network_domain == $_SERVER['HTTP_HOST'] ) {
			return;
		}

		if ( preg_match( '/(?:(.+)\.)?([^.]+)\.'. preg_quote( $network_domain ).'$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
			$site_slug         = $matches[2];
			$prefix            = $matches[1];
			$is_network_domain = true;
		}
		elseif ( preg_match( '/(?:(.+)\.)?qa\.([^.]+)\.com$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
			$site_slug = $matches[2];
			$prefix    = $matches[1];
		}

		if ( !empty( $site_slug ) ) {
			if ( preg_match('/^\/wp-admin/', $_SERVER['REQUEST_URI'] ) ) {
				if ( preg_match('/^\/wp-admin\/network/', $_SERVER['REQUEST_URI'] ) ) {
					if ( $network_domain != $_SERVER['REQUEST_URI'] ) {
						$redirect_to = 'http' . ( !empty( $_SERVER['HTTPS'] ) ? 's' : '') .'://'. $network_domain;
					}
				} elseif ( ! $is_network_domain ) {
					$redirect_to = 'http' . ( !empty( $_SERVER['HTTPS'] ) ? 's' : '') .'://'. ( $prefix ? $prefix .'.' : '' ) ."{$site_slug}.{$network_domain}";
				}
			} else {
				if ( $is_network_domain ) {
					$redirect_to = 'http' . ( !empty( $_SERVER['HTTPS'] ) ? 's' : '') .'://'. ( $prefix ? $prefix .'.' : '' ) ."qa.{$site_slug}.com";
				}
			}

			if ( $redirect_to && ! ( defined('WP_CLI') && WP_CLI ) ) {
				$redirect_to .= $_SERVER['REQUEST_URI'];
				header('Location: '. $redirect_to, true, 302);
				die();
			}

			$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = "{$site_slug}.{$network_domain}";
		}

		if ( ! empty( $prefix ) ) {
			$_SERVER['HTTP_HOST_PREFIX'] = $prefix;
			define( 'WWW_DIR', dirname(dirname( __DIR__ )) );
			define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] );
			define( 'WP_CONTENT_DIR', WWW_DIR . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
			define( 'WP_CONTENT_URL', WP_HOME . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
			define( 'WP_PLUGIN_DIR', WWW_DIR .'/wp-content/plugins' );
			define( 'WP_PLUGIN_URL', WP_HOME . '/wp-content/plugins' );
			define( 'WPMU_PLUGIN_DIR', WWW_DIR . '/wp-content/mu-plugins' );
			define( 'WPMU_PLUGIN_URL', WP_HOME . '/wp-content/mu-plugins' );

			if ( ! file_exists( WP_CONTENT_DIR ) ) {
				echo 'Error: Site content folder not found for qa branch '. $_SERVER['HTTP_HOST_PREFIX'];
				die();
			}
		}

	});

}
// Only load and activate plugin only if code has been referenced by local-config.php
elseif ( defined('PMC_THEME_SWITCH') && PMC_THEME_SWITCH ) {
	if ( ! class_exists( 'PMC_Theme_Switch' ) ) {

		final class PMC_Theme_Switch {
			private static $_instance = false;
			private $_theme_directory = false;
			private $_host_prefix = false;

			public static function get_instance() {
				if ( empty( self::$_instance ) ) {
					self::$_instance = new PMC_Theme_Switch();
				}
				return self::$_instance;
			}

			public function __construct() {
				$this->_init();
			}

			private function _init() {

				add_action( 'init', array( $this, 'action_init' ) );

			}

			public function action_init() {
				remove_all_filters( 'intermediate_image_sizes' );
				remove_all_filters( 'send_headers' );

				if ( !empty( $_SERVER['HTTP_HOST_PREFIX'] ) ) {
					$this->_host_prefix = $_SERVER['HTTP_HOST_PREFIX'];
					// quickstart mu plugins not playing nice in function wpcom_vip_quickstart_fix_domain
					remove_all_filters( 'site_url' );
					remove_all_filters( 'home_url' );
					add_filter( 'home_url', array( $this, 'filter_url' ) );
					add_filter( 'site_url', array( $this, 'filter_url' ) );
					add_filter( 'theme_root_uri', array( $this, 'filter_url' ) );
					add_filter( 'plugins_url', array( $this, 'filter_url' ) );
					add_filter( 'admin_url', array( $this, 'filter_url' ) );
					add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 2 );
				}
			}

			public function filter_login_url( $login_url, $redirect ) {

				$url_parts = parse_url( $login_url );
				if ( !empty( $url_parts['query'] ) ) {
					$args = wp_parse_args( $url_parts['query'] );
					$args['redirect_to'] = $this->filter_url( $redirect );
					$url_parts['query'] = http_build_query( $args );

					$login_url = $url_parts['scheme'] .'://' . $url_parts['host'];

					if ( !empty( $url_parts['path'] ) ) {
						$login_url .= $url_parts['path'];
					}

					if ( !empty( $url_parts['query'] ) ) {
						$login_url .= '?' . $url_parts['query'];
					}

					if ( !empty( $url_parts['fragment'] ) ) {
						$login_url .= '#' . $url_parts['fragment'];
					}

				}

				return $login_url;
			}

			public function filter_url( $url ) {
				if ( empty( $this->_host_prefix ) ) {
					return $url;
				}
				$url_parts = wp_parse_args( parse_url( $url ), array('path'=>'', 'scheme' => 'http', 'host' => $_SERVER['HTTP_HOST'] ) );
				$url = $url_parts['scheme'] . '://' . $this->_host_prefix . '.' . $_SERVER['HTTP_HOST'] . $url_parts['path'];
				if ( !empty( $url_parts['query'] ) ) {
					$url .= '?'. $url_parts['query'];
				}
				unset( $url_parts );
				return $url;
			}

		}
	}
	PMC_Theme_Switch::get_instance();
}
// EOF