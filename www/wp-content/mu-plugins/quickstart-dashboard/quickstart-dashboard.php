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

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ) );

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
		// Check if this user just submitted the dashboard credentials form
		if ( isset( $_POST['dashboard-credentials-save-and-connect'] ) ) {
			check_admin_referer( 'dashboard-credentials' );
			if ( !current_user_can( 'manage_options' ) ) {
				wp_die( 'You do not have sufficient permissions to access this page.' );
			}
			
			// Alphanumeric characters and digits allowed in client secret.
			$secret_valid = preg_match( '/\A[a-z0-9]+\z/i', $_POST['oauth-client-secret'] );
			$id_valid = preg_match( '/\A[0-9]+\z/', $_POST['oauth-client-id'] );
			
			if ( $secret_valid && $id_valid ) {
				// Save the enterred credentials
				$this->set_wpcom_client_id( intval( $_POST['oauth-client-id'] ) );
				$this->set_wpcom_client_secret( $_POST['oauth-client-secret'] );
				
				// Redirect the user to the auth page
				wp_redirect( $this->get_wpcom_authorization_url() );
			} elseif ( !$secret_valid ) {
				?>
				<div class="error"><p><?php _e( 'The Client Secret you entered is invalid. Client IDs may contain only alphanumerical digits.' ); ?></p></div>
				<?php
			} else {
				?>
				<div class="error"><p><?php _e( 'The Client ID you entered is invalid. Client IDs may contain only numerical digits.' ); ?></p></div>
				<?php
			}
		}
		
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

	function admin_footer_text( $text ) {
		return '<span id="footer-thankyou">' . __( 'Thank you for creating with <a href="http://wordpress.org/">WordPress</a> and <a href="https://vip.wordpress.com/">WordPress.com VIP</a>.' ) . '</span>';
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
			'client_id' => urlencode( $this->get_wpcom_client_id() ),
			'redirect_uri' => $this->get_wpcom_redirect_uri(),
			'client_secret' => urlencode( $this->get_wpcom_client_secret() ),
			'code' => urlencode( $_GET['code'] ),
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
		
		if ( !isset( $secret->access_token ) ) {
			?>
			<div class="error">
				<p><?php _e( 'An error occured retrieving the access token from WordPress.com. The data received was: ', 'quickstart-dashboard' ); ?></p>
				<pre><?php echo esc_html( $auth ); ?></pre>
			</div>
			<?php

			return;
		}
		
		$access_key = $secret->access_token;

		?>
		<div class="updated"><p><?php printf( __( 'Successfully connected to WordPress.com.', 'quickstart-dashboard' ), $access_key ); ?></p></div>
		<?php

		$this->set_wpcom_access_token( $access_key );
	}

	function admin_enqueue_scripts() {
		wp_enqueue_script( 'dashboard' );

		add_thickbox();
		if ( wp_is_mobile() )
			wp_enqueue_script( 'jquery-touch-punch' );

		wp_enqueue_style( 'quickstart-dashboard', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/css/dashboard.css' );
		wp_enqueue_style( 'noticons', '//s0.wordpress.com/i/noticons/noticons.css' );
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
					<tr>
						<th scope="row"><label for="oauth-client-id"><?php _e( 'Client ID', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="text" id="oauth-client-id" name="oauth-client-id" value="<?php echo esc_attr( $this->get_wpcom_client_id() ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="oauth-client-secret"><?php _e( 'Client Secret', 'quickstart-dashboard' ) ?></label></th>
						<td><input type="text" id="oauth-client-secret" name="oauth-client-secret" value="<?php echo esc_attr( $this->get_wpcom_client_secret() ); ?>" /></td>
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

	function show_admin_notices() {
		if ( $this->show_wpcom_access_notice && ( !$this->has_oauth_credentials() || empty( $this->wpcom_access_token ) ) ) {
			echo '<div class="error"><p>' . $this->get_connect_wpcom_message() . '</p></div>';
		}
	}
	
	function get_connect_wpcom_message() {
		// Check whether we need credentials or just need to be connected
		if ( !$this->has_oauth_credentials() ) {
			$connect_url = menu_page_url( 'dashboard-credentials', false );
		} else {
			$connect_url = add_query_arg( array( 'dashboard_wpcom_connect' => true ), menu_page_url( 'vip-dashboard', false ) );
		}
		
		return sprintf( __( 'Please <a href="%s">connect Quickstart</a> with WordPress.com VIP to enable enhanced features.', 'quickstart-dashboard' ), $connect_url );
	}
	
	function has_oauth_credentials() {
		$client_id = $this->get_wpcom_client_id();
		$client_secret = $this->get_wpcom_client_secret();
		
		return !empty( $client_id ) && !empty( $client_secret );
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
			'client_id'		=> urlencode( $this->get_wpcom_client_id() ),
			'redirect_uri'  => urlencode( $this->get_wpcom_redirect_uri() ),
			'response_type' => 'code',
		), $this->wpcom_api_endpoints['authorize'] );
	}
	
	function get_wpcom_client_id() {
		$id = get_option( 'dashboard_wpcom_client_id', defined( 'DASHBOARD_WP_CLIENT_ID' ) ? DASHBOARD_WP_CLIENT_ID : '' );
		return (string) apply_filters( 'dashboard_wpcom_client_id', $id );
	}
	
	function set_wpcom_client_id( $client_id ) {
		update_option( 'dashboard_wpcom_client_id', $client_id );
	}
	
	function get_wpcom_client_secret() {
		$secret = get_option( 'dashboard_wpcom_client_secret', defined( 'DASHBOARD_WP_CLIENT_SECRET' ) ? DASHBOARD_WP_CLIENT_SECRET : '' );
		return (string) apply_filters( 'dashboard_wpcom_client_secret', $secret );
	}
	
	function set_wpcom_client_secret( $secret ) { 
		update_option( 'dashboard_wpcom_client_secret', $secret );
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