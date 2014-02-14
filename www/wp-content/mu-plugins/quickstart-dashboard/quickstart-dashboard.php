<?php
/*
Plugin Name: Quickstart Dashboard
Description: A dashboard for VIP Quickstart.
Author: Automattic, Michael Blouin
Version: 0.1
Stable tag: 0.1
License: GPLv2
*/

if ( defined( 'WP_CLI' ) && true === WP_CLI )
	require dirname( __FILE__ ) . '/includes/wp-cli.php';

define ( 'QUICKSTART_DASHBOARD_PLUGINS_DIR', dirname( __FILE__ ) . '/dashboard-plugins/' );

require_once( dirname( __FILE__ ) . '/includes/class-dashboard-plugin.php' );

class Quickstart_Dashboard {

	private static $instance;
	
	/**
	 *
	 * @var array All loaded plugins
	 */
	private $plugins = array();

	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		do_action( 'quickstart_dashboard_loaded' );
	}

	function init() {
	}

	function admin_init() {
		$this->load_plugins();
	}

	static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		
		return self::$instance;
	}

	/**
	 * Loads all of the plugins in the plugins directory.
	 *
	 * @return array The loaded plugin objects
	 */
	function load_plugins() {
		// Get the available plugins
		$plugins = $this->scan_plugins_dir();

		// Load each one
		foreach ( $plugins as $plugin ) {
			$this->plugins[$plugin] = new $plugin;
		}

		return $this->plugins;
	}

	/**
	 * Scans the plugin directory for quickstart dashboard plugins.
	 *
	 * @param string $dir The directory to scann for plugins
	 * @return array Returns an array of plugin files
	 */
	private function scan_plugins_dir( $dir = QUICKSTART_DASHBOARD_PLUGINS_DIR ) {
		$plugin_files = array();
		$dir = new DirectoryIterator( $dir );

		foreach ($dir as $fileinfo) {
			// Needs to be a php file
			if ( $fileinfo->getExtension() !== 'php' ) {
				continue;
			}

			$filename = $fileinfo->getBasename( '.php' );

			// Check if this is a valid, loadable plugin
			if ( $this->load_dashboard_plugin( $filename, $fileinfo->getPathname() ) ) {
				$plugin_files[] = $filename;
			}
		}

		return $plugin_files;
	}

	/**
	 *
	 * @param string $plugin The plugin class name
	 * @param string $file The path of the plugin file
	 * @return boolean Whether or not the file contains a proper plugin
	 */
	private function load_dashboard_plugin( $plugin, $file = '' ) {

		if( ! class_exists( $plugin ) ) {
			$path =  ! empty( $file ) ? $file : sprintf( '%1$s/%2$s.php', QUICKSTART_DASHBOARD_PLUGINS_DIR, $plugin );
			if ( file_exists( $path ) )
				include( $path );
		}

		return class_exists( $plugin ) && is_subclass_of( $plugin, 'Dashboard_Plugin' ) ;
	}
}