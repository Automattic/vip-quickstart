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
require_once( dirname( __FILE__ ) . '/includes/class-dashboard-widget-table.php' );
require_once( dirname( __FILE__ ) . '/includes/class-dashboard-data-table.php' );

class Quickstart_Dashboard {
	const OPTION = 'quickstart-dashboard';

	private $show_wpcom_access_notice = true;

	private $wpcom_api_endpoint = 'https://public-api.wordpress.com/oauth2/token';
	
	/**
	 *
	 * @var array All loaded plugins
	 */
	private $plugins = array();

	function __construct() {
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ) );

		add_action( 'admin_init', array( $this, 'oauth_flow' ) );
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

	function admin_footer_text( $text ) {
		return '<span id="footer-thankyou">' . __( 'Thank you for creating with <a href="http://wordpress.org/">WordPress</a> and <a href="https://vip.wordpress.com/">WordPress.com VIP</a>.' ) . '</span>';
	}

	function admin_enqueue_scripts() {
		wp_enqueue_script( 'dashboard' );

		add_thickbox();
		if ( wp_is_mobile() )
			wp_enqueue_script( 'jquery-touch-punch' );

		wp_enqueue_script( 'quickstart-dashboard', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/js/quickstart_dashboard.js', array( 'jquery' ) );
		wp_enqueue_style( 'quickstart-dashboard', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/css/dashboard.css', array( 'dashicons' ) );
	}

	function admin_menu() {
		add_menu_page( __( 'VIP Dashboard', 'quickstart-dashboard' ), __( 'VIP', 'quickstart-dashboard' ), 'manage_options', 'vip-dashboard', null, 'dashicons-cloud', 3 );
		add_submenu_page( 'vip-dashboard', __( 'VIP Dashboard', 'quickstart-dashboard' ), __( 'Dashboard', 'quickstart-dashboard' ), 'manage_options', 'vip-dashboard', array( $this, 'vip_admin_page' ), 'dashicons-cloud', 3 );
		add_submenu_page( null, __( 'Dashboard Credentials', 'quickstart-dashboard' ), __( 'Dashboard Credentials', 'quickstart-dashboard' ), 'manage_options', 'dashboard-credentials', array( $this, 'dashboard_credentials_page' ) );

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
			<div id="dashboard-widgets-wrap" class="quickstart-dashboard-widgets-wrap">
				<?php wp_dashboard(); // Main call that displays the widgets ?>
				<div class="clear"></div>
			</div>
		</div>
		<?php

		do_action( 'quickstart_admin_page' );
	}
	
	function dashboard_credentials_page() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}
		
		?>
		<div class="wrap">
			<div id="icon-vip" class="icon32"><br /></div>
			<h2><?php _e( 'Dashboard Credentials', 'quickstart-dashboard' ); ?></h2>
			<h3><?php _e( 'WordPress.com OAuth Credentials', 'quickstart-dashboard' ); ?></h3>
			<p><?php _e( 'The WordPress.com OAuth credentials are used to query information about your VIP sites, get your VIP themes, and provide other connected goodness.' ); ?></p>
			<p><?php printf( __( 'If you do not already have WordPress.com OAuth credentials, you can get them by creating an application on the <a href="%s" target="_blank">WordPress.com Developer Site</a>.' ), 'https://developer.wordpress.com/apps/' ); ?></p>
			<form action="<?php menu_page_url( 'dashboard-credentials' ); ?>" method="POST">
				<?php wp_nonce_field( 'dashboard-credentials' ); ?>
				<table class="form-table">
					<?php if ( ! self::hardcoded_client_id() ): ?>
					<tr>
						<th scope="row"><label for="oauth-client-id"><?php _e( 'Client ID', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="text" id="oauth-client-id" name="oauth-client-id" /></td>
					</tr>
					<?php endif; ?>
					<?php if ( ! self::hardcoded_client_secret() ): ?>
					<tr>
						<th scope="row"><label for="oauth-client-secret"><?php _e( 'Client Secret', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="password" id="oauth-client-secret" name="oauth-client-secret" /></td>
					</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><label for="wpcom_username"><?php _e( 'WordPress.com Username', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="text" id="wpcom_username" name="wpcom_username" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpcom_password"><?php _e( 'WordPress.com Password', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="password" id="wpcom_password" name="wpcom_password" /></td>
					</tr>
				</table>
				<p>
					<input type="submit" class="button-primary" name="dashboard-credentials-save-and-connect" value="<?php _e( 'Save Credentials and Connect to WordPress.com', 'quickstart-dashboard' ); ?>" />
					<a class="button-secondary" href="<?php menu_page_url( 'vip-dashboard' ); ?>"><?php _e( 'Cancel', 'quickstart-dashboard' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	function oauth_flow() {
		$wpcom_access_token = self::get_wpcom_access_token();

		if ( ! empty( $wpcom_access_token ) && isset( $_GET['page'] ) && 'dashboard-credentials' == $_GET['page'] ) {
			wp_redirect( admin_url( 'admin.php?page=vip-dashboard' ) );
			exit;
		}

		if ( ! isset( $_POST['dashboard-credentials-save-and-connect'] ) )
			return;

		check_admin_referer( 'dashboard-credentials' );

		if ( ! current_user_can( 'manage_options' ) )
			return;

		if ( self::hardcoded_client_id() )
			$oauth_id = self::hardcoded_client_id();
		else if ( isset( $_POST['oauth-client-id'] ) )
			$oauth_id = $_POST['oauth-client-id'];
		else
			wp_die( 'No client ID set' );

		if ( self::hardcoded_client_secret() )
			$oauth_secret = self::hardcoded_client_secret();
		else if ( isset( $_POST['oauth-client-secret'] ) )
			$oauth_secret = $_POST['oauth-client-secret'];
		else
			wp_die( 'No client secret set' );

		$request_args = array(
			'body' => array(
				'client_id' => urlencode( $oauth_id ),
				'client_secret' => urlencode( $oauth_secret ),
				'grant_type' => 'password',
				'username' => $_POST['wpcom_username'],
				'password' => $_POST['wpcom_password'],
			)
		);

		$auth = wp_remote_post( $this->wpcom_api_endpoint, $request_args );

		if ( is_wp_error( $auth ) || $auth['response']['code'] != 200 ) {
			wp_die( "Couldn't fetch access token" );
			exit;
		}

		$auth = json_decode( $auth['body'] );

		if ( ! isset( $auth->access_token ) )
			wp_die( 'No token?' );

		self::set_wpcom_access_token( $auth->access_token );

		wp_redirect( admin_url( 'admin.php?page=vip-dashboard' ) );
		exit;
	}

	function show_admin_notices() {
		$wpcom_access_token = self::get_wpcom_access_token();

		if ( $this->show_wpcom_access_notice && ( empty( $wpcom_access_token ) ) ) {
			echo '<div class="error"><p>' . $this->get_connect_wpcom_message() . '</p></div>';
		}
	}
	
	static function get_connect_wpcom_message() {
		$connect_url = menu_page_url( 'dashboard-credentials', false );
		return sprintf( __( 'Please <a href="%s">connect Quickstart</a> with WordPress.com VIP to enable enhanced features.', 'quickstart-dashboard' ), $connect_url );
	}
	
	static function set_wpcom_access_token( $new_token ) {
		self::update_option( 'access_token', $new_token );
	}

	function invalidate_wpcom_access_token() {
		self::set_wpcom_access_token( '' );
	}

	static function get_wpcom_access_token() {
		return self::get_option( 'access_token' );
	}

	function get_wpcom_redirect_uri() {
		$query_args = array(
			'dashboard_auth' => 1,
			'_qsnonce'		 => wp_create_nonce( 'dashboard_wpcom_connect' ),
		);

		self::update_option( 'connect_nonce', $query_args['_qsnonce'] );

		return add_query_arg( $query_args, menu_page_url( 'vip-dashboard', false ) );
	}

	static function hardcoded_client_id() {
		if ( defined( 'QUICKSTART_DASHBOARD_WP_CLIENT_ID' ) )
			return QUICKSTART_DASHBOARD_WP_CLIENT_ID;

		return false;
	}

	static function hardcoded_client_secret() {
		if ( defined( 'QUICKSTART_DASHBOARD_WP_CLIENT_SECRET' ) )
			return QUICKSTART_DASHBOARD_WP_CLIENT_SECRET;

		return false;
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

	private static function get_option( $name, $default = false ) {
		$option = get_option( self::OPTION );

		if ( ! isset( $option[$name] ) )
			return $default;

		return $option[$name];
	}

	private static function update_option( $name, $value ) {
		$option = get_option( self::OPTION );
		$option[$name] = $value;
		update_option( self::OPTION, $option );
		return $value;
	}
}

// Bootsrap the dashboard
new Quickstart_Dashboard;
