<?php
/**
 * This class is reponsible for setting up theme for qa site where there are multiple versions
 * of the theme that are served over a subdomain, eg. [subdomain].xxx.yyy.zzz
 * The vip quick start deffault themes are located at WP_CONTENT_DIR/themes
 * The subdomain themes are located at WP_CONTENT_DIR/pmc-theme-switch/[subdomain]
 * If subdomain themes doesn't exist, it will fallback to default
 *
 */

/*
Add following code to local-config.php

if ( preg_match( '/(?:(.+)\.)?([^.]+\.[^.]+\.[^.]+)$/si',$_SERVER['HTTP_HOST'], $matches ) ) {
	if ( !empty( $matches[1] ) ) {
		$_SERVER['SERVER_NAME']      = $_SERVER['HTTP_HOST'] = $matches[2];
		$_SERVER['HTTP_HOST_PREFIX'] = $matches[1];
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

		if ( !empty( $_SERVER['HTTP_HOST_PREFIX'] ) ) {
			$this->_host_prefix = $_SERVER['HTTP_HOST_PREFIX'];
			$this->_theme_directory = 'pmc-theme-switch/'. $this->_host_prefix;
			$theme_directory_registered = register_theme_directory( $this->_theme_directory );

			if ( !$theme_directory_registered ) {
				header( 'Location: http://' . $_SERVER['HTTP_HOST'], true, 302);
				die();
			}

			add_filter( 'home_url', array( $this, 'filter_url' ) );
			add_filter( 'site_url', array( $this, 'filter_url' ) );
			add_filter( 'theme_root', array( $this, 'filter_theme_root' ) );
		}

	}

	public function filter_url( $url ) {
		if ( empty( $this->_host_prefix ) ) {
			return $url;
		}
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		return $scheme .'://' . $this->_host_prefix . '.'. $_SERVER['HTTP_HOST'];
	}

	public function filter_theme_root( $theme_root ) {
		if ( !empty( $this->_theme_directory ) ) {
			if ( file_exists( $this->_theme_directory ) ) {
				return $this->_theme_directory;
			}
			if ( file_exists( WP_CONTENT_DIR . '/' . $this->_theme_directory ) ) {
				return WP_CONTENT_DIR . '/' . $this->_theme_directory;
			}
		}
		return $theme_root;
	}

}

PMC_Theme_Switch::get_instance();

// EOF