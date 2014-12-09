<?php
/**
 * This class is reponsible for setting up theme for qa site where there are multiple versions
 * of the theme that are served over a subdomain, eg. [subdomain].xxx.yyy.zzz
 * The vip quick start deffault themes are located at /srv/www/themes
 * The subdomain themes are located at /srv/www/wp-content-sites/[subdomain]
 * If subdomain themes doesn't exist, it will fallback to default
 *
 */

/*
Add following code to local-config.php

if ( preg_match( '/(?:(.+)\.)?([^.]+\.[^.]+\.[^.]+)$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
	if ( !empty( $matches[1] ) && file_exists( __DIR__ . '/wp-content-sites/' . $matches[1] ) ) {
		$_SERVER['SERVER_NAME']      = $_SERVER['HTTP_HOST'] = $matches[2];
		$_SERVER['HTTP_HOST_PREFIX'] = $matches[1];
		define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] );
		define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
		define( 'WP_CONTENT_URL', WP_HOME . '/wp-content-sites/' . $_SERVER['HTTP_HOST_PREFIX'] );
		define( 'WP_PLUGIN_DIR', __DIR__ .'/wp-content/plugins' );
		define( 'WP_PLUGIN_URL', WP_HOME . '/wp-content/plugins' );
		define( 'WPMU_PLUGIN_DIR', __DIR__ . '/wp-content/mu-plugins' );
		define( 'WPMU_PLUGIN_URL', WP_HOME . '/wp-content/mu-plugins' );
	}
}

*/

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
		}

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

PMC_Theme_Switch::get_instance();

// EOF