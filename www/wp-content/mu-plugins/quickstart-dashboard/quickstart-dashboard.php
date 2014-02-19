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
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        
        if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            // Need to load plugins here instead of admin_init so they have a chance to register submenu pages
            $this->load_plugins();
            $this->init_plugins();
        }
        
		do_action( 'quickstart_dashboard_loaded' );
	}

	function init() {
	}

	function admin_init() {
	}

	function admin_enqueue_scripts() {
		wp_enqueue_script( 'dashboard' );

		if ( wp_is_mobile() )
			wp_enqueue_script( 'jquery-touch-punch' );
	}
    
    function admin_menu() {
        add_menu_page( __( 'VIP Dashboard', 'quickstart-dashboard' ), __( 'VIP', 'quickstart-dashboard' ), 'manage_options', 'vip-dashboard', null, 'dashicons-cloud', 3 );
		add_submenu_page( 'vip-dashboard', __( 'VIP Dashboard', 'quickstart-dashboard' ), __( 'Dashboard', 'quickstart-dashboard' ), 'manage_options', 'vip-dashboard', array( $this, 'vip_admin_page' ), 'dashicons-cloud', 3 );
        
        do_action( 'quickstart_dashboard_admin_menu' );
    }
    
    function vip_admin_page() {
        // Include the WP Dashboard API
        require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );
        
        do_action( 'quickstart_dashboard_setup' );
        
        settings_errors();
        
        ?>
        <div class="wrap">
            <div id="icon-vip" class="icon32"><br /></div>
            <h2><?php _e( 'VIP Quickstart Dashboard', 'quickstart-dashboard' ); ?></h2>
            <div id="dashboard-widgets-wrap">
                <?php wp_dashboard(); // Main call that displays the widgets ?>
                <div class="clear"></div>
            </div>
        </div>
        <?php
        
        do_action( 'quickstart_admin_page' );
    }

	static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		
		return self::$instance;
	}

	function get_plugins() {
		return $this->plugins;
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
    
    function init_plugins() {
        foreach ( $this->plugins as $plugin ) {
			$plugin->init();
		}
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

// Bootsrap the dashboard
Quickstart_Dashboard::get_instance();