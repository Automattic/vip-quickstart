<?php

/*
add following line of code to local-config.php to activate:
require_once( __DIR__ . '/wp-content/mu-plugins/pmc-branch-switch-config.php');
*/

/**
 * This plugin is reponsible for setting up theme for qa site where there are multiple versions
 * of the theme that are served over a subdomain, eg. http://[site-slug].qa.pmc.com, http://qa.[site-slug].com
 * The main subdomain default wp content is located /srv/www/wp-content/
 * The QA branch admin url will be http://[qa-branch].[site-slug].qa.pmc.com/wp-admin
 * the QA branch site url will be http://[qa-branch].qa.[site-slug].com
 * The QA branch wp content is located at /srv/www/wp-content-sites/
 *
 */

// Only load and activate plugin only if code has been referenced by local-config.php
if ( defined('PMC_BRANCH_SWITCH') && PMC_BRANCH_SWITCH ) {

	if ( ! class_exists( 'PMC_Theme_Switch' ) ) {

		final class PMC_Theme_Switch {
			private static $_instance = false;
			private $_host_prefix     = false;
			private $_request_host    = false;
			private $_blog_id         = false;

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

				if ( empty( $_SERVER['REQUEST_URI'] ) ) {
					return;
				}

				add_action( 'init', array( $this, 'action_init' ) );

				// by this time, wp already know found the blog, we can restore the original request host name here
				if ( !empty( $_SERVER['REQUEST_HOST'] ) ) {
					$this->_request_host = $_SERVER['REQUEST_HOST'];
					$_SERVER['SERVER_NAME']  = $_SERVER['HTTP_HOST'] = $_SERVER['REQUEST_HOST'];
				}

				$this->_blog_id = get_current_blog_id();

			}

			public function action_init() {
				remove_all_filters( 'intermediate_image_sizes' );
				remove_all_filters( 'send_headers' );

				// quickstart mu plugins not playing nice in function wpcom_vip_quickstart_fix_domain
				remove_all_filters( 'site_url' );
				remove_all_filters( 'home_url' );

				if ( !empty( $_SERVER['HTTP_HOST_PREFIX'] ) ) {

					$this->_host_prefix = $_SERVER['HTTP_HOST_PREFIX'];
					add_filter( 'home_url', array( $this, 'filter_url' ) );
					add_filter( 'site_url', array( $this, 'filter_url' ) );
					add_filter( 'theme_root_uri', array( $this, 'filter_url' ) );
					add_filter( 'plugins_url', array( $this, 'filter_url' ) );
					add_filter( 'admin_url', array( $this, 'filter_url' ) );
					add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 2 );
				}

				add_filter('site_url', array( $this, 'filter_wp_login' ) );
				add_filter('login_redirect', array( $this,'filter_login_redirect' ), 10, 3 );
				add_filter('theme_root_uri', array( $this,'filter_theme_root_uri' ) );
			}

			public function filter_theme_root_uri( $url ) {
				$host = parse_url( $url, PHP_URL_HOST );
				if ( $host != $_SERVER['HTTP_HOST'] ) {
					$url = str_replace( $host, $_SERVER['HTTP_HOST'], $url );
				}
				return $url;
			}

			public function filter_login_redirect($redirect_to, $requested_redirect_to, $user) {
				if ( empty( $requested_redirect_to ) ) {
					$redirect_to = '/';
				}
				return $redirect_to;
			}

			public function filter_wp_login( $url ) {

				$path = parse_url( $url, PHP_URL_PATH );
				if ( '/wp-login.php' == $path ) {
					$host = parse_url( $url, PHP_URL_HOST );
					if ( $host != $_SERVER['HTTP_HOST'] ) {
						$url = str_replace( $host, $_SERVER['HTTP_HOST'], $url );
					}
				}

				return $url;
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
				if ( empty( $this->_host_prefix ) || $this->_blog_id != get_current_blog_id() ) {
					return $url;
				}
				$host = !empty( $this->_request_host ) ? $this->_request_host : ( $this->_host_prefix . '.' . $_SERVER['HTTP_HOST'] ) ;
				$url_parts = wp_parse_args( parse_url( $url ), array('path'=>'', 'scheme' => 'http', 'host' => $_SERVER['HTTP_HOST'] ) );
				$url = $url_parts['scheme'] . '://' . $host . $url_parts['path'];
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