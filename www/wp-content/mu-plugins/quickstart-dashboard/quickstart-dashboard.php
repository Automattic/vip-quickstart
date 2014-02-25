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
	
	private $wpcom_access_token = '';
	private $show_wpcom_access_notice = true;

	private $wpcom_api_endpoints = array(
		'authorize' => 'https://public-api.wordpress.com/oauth2/authorize',
		'token'	    => 'https://public-api.wordpress.com/oauth2/token',
	);
	
	/**
	 *
	 * @var array All loaded plugins
	 */
	private $plugins = array();

	function __construct() {
		// Load the WP.com access token
		$this->wpcom_access_token = get_option( 'qs_dashboard_wpcom_access_token', '' );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
        
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
		// Check if we're supposed to be connecting
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'vip-dashboard'  ) {
			// Check for the args to connect to WP.com
			if ( isset( $_REQUEST['dashboard_wpcom_connect'] ) && $_REQUEST['dashboard_wpcom_connect'] ) {
				wp_redirect( $this->get_wpcom_authorization_url() );
				exit;
			}

			// Check for the authorization info from wp.com
			if ( isset( $_REQUEST['dashboard_auth'] ) && $_REQUEST['dashboard_auth'] ) {
				$this->do_wpcom_auth_flow();
			}
		}
	}

	function do_wpcom_auth_flow() {
		if ( !wp_verify_nonce( $_REQUEST['_qsnonce'], 'dashboard_wpcom_connect' ) ) {
			wp_nonce_ays( 'dashboard_wpcom_connect' );
		}

		if ( isset( $_REQUEST['error'] ) ) {
			?>
			<div class="error"><p><?php _e( 'Error: You did not authorize Quickstart with WordPress.com VIP.', 'quickstart-dashboard' ); ?></p></div>
			<?php

			return;
		}

		if ( !isset( $_REQUEST['code'] ) ) {
			?>
			<div class="error"><p><?php _e( 'Error: Code missing from request.', 'quickstart-dashboard' ); ?></p></div>
			<?php

			return;
		}

		// Go ahead and get the access token
		$curl = curl_init( $this->wpcom_api_endpoints['token'] );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
			'client_id' => apply_filters( 'dashboard_wpcom_client_id', '' ),
			'redirect_uri' => $this->get_wpcom_redirect_uri(),
			'client_secret' => apply_filters( 'dashboard_wpcom_client_secret', '' ),
			'code' => $_GET['code'],
			'grant_type' => 'authorization_code',
		) );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
		$auth = curl_exec( $curl );

		if ( false === $auth ) {
			?>
			<div class="error"><p><?php _e( 'An error occured retrieving the access token from WordPress.com. Please try again.', 'quickstart-dashboard' ); ?></p></div>
			<?php

			return;
		}

		$secret = json_decode($auth);
		$access_key = $secret->access_token;

		?>
		<div class="updated"><p><?php printf( __( 'Successfully connected to WordPress.com.', 'quickstart-dashboard' ), $access_key ); ?></p></div>
		<?php

		$this->set_wpcom_access_token( $access_key );
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

	function show_admin_notices() {
		if ( empty( $this->wpcom_access_token ) && $this->show_wpcom_access_notice ) {
			$connect_url = add_query_arg( array( 'dashboard_wpcom_connect' => true ), menu_page_url( 'vip-dashboard', false ) );
			?>
			<div class="error"><p><?php printf( __( 'Please <a href="%s">connect Quickstart</a> with WordPress.com VIP to enable enhanced features.', 'quickstart-dashboard' ), $connect_url ); ?></p></div>
			<?php
		}
	}

	function set_wpcom_access_token( $new_token ) {
		$this->wpcom_access_token = $new_token;
		update_option( 'qs_dashboard_wpcom_access_token', $this->wpcom_access_token );
	}

	function invalidate_wpcom_access_token() {
		$this->set_wpcom_access_token( '' );
	}

	function get_wpcom_access_token() {
		return $this->wpcom_access_token;
	}

	function get_wpcom_redirect_uri() {
		$query_args = array(
			'dashboard_auth' => 1,
			'_qsnonce'		 => wp_create_nonce( 'dashboard_wpcom_connect' ),
		);

		update_option( 'qs_dashboard_wpcom_connect_nonce', $query_args['_qsnonce'] );

		return add_query_arg( $query_args, menu_page_url( 'vip-dashboard', false ) );
	}

	function get_wpcom_authorization_url() {
		return add_query_arg( array(
			'client_id'		=> urlencode( apply_filters( 'dashboard_wpcom_client_id', '' ) ),
			'redirect_uri'  => urlencode( $this->get_wpcom_redirect_uri() ),
			'response_type' => 'code',
		), $this->wpcom_api_endpoints['authorize'] );
	}

	/**
	 * Gets the current Quickstart_Dashboard instance.
	 * @return Quickstart_Dashboard
	 */
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