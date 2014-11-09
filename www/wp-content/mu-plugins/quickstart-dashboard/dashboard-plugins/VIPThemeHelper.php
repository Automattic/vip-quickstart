<?php

/**
 * VIP Theme Helper is designed to make it easier for VIPs to get their resources
 * Automattically checked out from VIP SVN.
 */
class VIPThemeHelper extends Dashboard_Plugin {

	private $needs_credentials = false;
	private $access_token = null;
	private $wpcom_endpoints = array(
		'vip-themes' => 'https://public-api.wordpress.com/rest/v1/vip/themes',
	);

	private $svn_uri = 'https://vip-svn.wordpress.com/';

	public function init() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'print_admin_notice' ) );
		add_action( 'switch_theme', array( $this, 'theme_switched' ), 10, 2 );
		add_action( 'quickstart_dashboard_setup', array( $this, 'dashboard_setup' ) );
	}
	
	public function name() {
		return __( 'VIP Theme Helper', 'quickstart-dashboard' );
	}

	function dashboard_setup() {
		$update_link = ' <a class="widget_update" title="' . __( 'Check for updates', 'quickstart-dashboard' ) . '"><span class="dashicons dashicons-update"></span></a>';
		wp_add_dashboard_widget( 'quickstart_dashboard_vipthemehelper', __( 'VIP Themes', 'quickstart-dashboard' ) . $update_link, array( $this, 'show' ) );
	}

	function show() {
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div id="themehelper-update-box" class="widget-update-box"></div>';
		if ( ! empty( $this->access_token ) ) {
			$table = new ThemeHelperWidgetTable( $this );
			$table->prepare_items();
			$table->display(); 
		} else {
			// No access token, print a message
			echo '<p>' . Quickstart_Dashboard::get_connect_wpcom_message() . '</p>';
		}
	}

	/**
	 * Called when the current WordPress theme is switched ('switch_theme' action)
	 * 
	 * @param string $new_name The name of the new theme
	 * @param WP_Theme $new_theme The theme object for the new theme
	 */
	function theme_switched( $new_name, $new_theme ) {
		$new_stylesheet = $new_theme->get_stylesheet();

		// Check if a VIP theme was switched on/off
		$themes = $this->get_vip_scanned_themes();
		foreach ( $themes as $slug => $theme ) {
			// Mark whether this VIP theme is the currently activate VIP theme
			$themes[$slug]['activated'] = $slug == $new_stylesheet || "vip/$slug" == $new_stylesheet;
		}

		$this->set_vip_scanned_themes( $themes );
	}

	function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_vipthemehelpher_update_themes', array( $this, 'ajax_update_themes' ) );

		// If we have a wpcom access token and we've never scanned the users' VIP themes before, do so
		$this->access_token = Quickstart_Dashboard::get_wpcom_access_token();
		if (true|| !empty( $this->access_token ) && ! $this->has_scanned_vip_themes() ) {
			$result = $this->scan_vip_themes();

			if ( is_wp_error( $result ) ) {
				?>
				<div class="error"><p><?php echo esc_html( $result->get_error_message() ); ?></p></div>
				<?php
			}
		}

		// Check if we're supposed to be downloading and activating a theme right now
		if ( isset( $_REQUEST['page'] ) && 'vip-dashboard' === $_REQUEST['page'] && isset( $_REQUEST['vipthemehelper-action'] ) && ! isset( $_REQUEST['vipthemehelper-svn_credential_form_cancel'] ) ) {
			if ( !wp_verify_nonce( $_REQUEST['_thnonce'], 'vipthemehelper-install_and_activate' ) ) {
				wp_nonce_ays( 'vipthemehelper-install_and_activate' );
				exit;
			}

			if ( isset( $_REQUEST['vipthemehelper-install'] ) ) {
				$this->install_theme( $_REQUEST['vipthemehelper-action'], isset( $_REQUEST['vipthemehelper-activate'] ) );

			} elseif ( isset( $_REQUEST['vipthemehelper-activate'] ) ) {
				if ( $this->activate_vip_theme( $_REQUEST['vipthemehelper-action'] ) ) {
					?>
					<div class="updated"><p><?php printf( __( 'New VIP theme activated. <a href="%s">Visit site</a>', 'quickstart-dashboard' ), esc_attr( get_bloginfo( 'wpurl' ) ) ); ?></p></div>
					<?php
				}
			}
		}
	}

	function admin_enqueue_scripts() {
		if ( isset( $_REQUEST['page'] ) && 'vip-dashboard' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'vipthemehelper_js', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/js/vipthemehelper.js', array( 'jquery' ) );
			wp_localize_script( 'vipthemehelper_js', 'vipthemehelper_settings', array(
				'translations'	=> array(
					'action_done'	 => __( 'Done.', 'quickstart-dashboard' ),
					'action_fail'	 => __( 'Failed.', 'quickstart-dashboard' ),
					'get_vip_themes' => __( 'Updating VIP Themes from WordPress.com', 'quickstart-dashboard' ),
					'needs_refresh'  => __( 'New VIP themes were detected. Please reload the page to see them.', 'quickstart-dashboard' ),
				),
			) );
		}
	}

	function install_theme( $theme, $activate = true ) {
		$themes = $this->get_vip_scanned_themes();
		if ( !isset( $themes[$theme] ) ) {
			?>
			<div class="error"><p><?php printf( __( 'Error: Cannot install unknown theme: %s', 'quickstart-dashboard' ), esc_html( $theme ) ); ?></p></div>
			<?php

			return;
		}

		// Clone the theme to the default directory
		$destination = get_theme_root() . '/vip/' . $theme;
		$auth_args = '';
		if ( isset( $_REQUEST['vipthemehelper-svn_username'] ) ) {
			$auth_args .= ' --username=' . escapeshellarg( $_REQUEST['vipthemehelper-svn_username'] );
		}
		if ( isset( $_REQUEST['vipthemehelper-svn_password'] ) ) {
			$auth_args .= ' --password=' . escapeshellarg( $_REQUEST['vipthemehelper-svn_password'] );
		}

		$out = exec( sprintf( 'svn checkout --non-interactive %s %s %s 2>&1', escapeshellarg( $this->svn_uri . $theme ), escapeshellarg( $destination ), $auth_args ), $output, $return_var );

		if ( $return_var !== 0 ) {
			// The command failed, try and figure out if this was due to missing credentials
			if ( empty( $_REQUEST['username'] ) || empty( $_REQUEST['password'] ) ) {
				// Prompt the user for credentials
				$this->needs_credentials = $theme;
				return;
			} else {
				?>
				<div class="error"><p><?php printf( __( 'SVN Theme checkout failed with code <strong>%s</strong>. The output was: ', 'quickstart-dashboard'  ), number_format( $return_var ) ) ?></p><pre><?php echo esc_html( implode( "\n", $output ) ) ?></pre></div>
				<?php

				return;
			}
		}

		// Mark that this theme is now installed
		$themes[$theme]['installed'] = true;
		$themes[$theme]['stylesheet'] = 'vip/' . $theme;
		$this->set_vip_scanned_themes( $themes );
		
		// Add the theme directory to the RepoMonitor
		$quickstart_dashboard = new Quickstart_Dashboard;
		$plugins = $quickstart_dashboard->get_plugins();
		if ( isset( $plugins['RepoMonitor'] ) ) {
			$wp_theme = wp_get_theme( $themes[$theme]['stylesheet'] );

			$credentials = array();
			if ( isset( $_REQUEST['vipthemehelper-svn_username'] ) ) {
				$credentials['username'] = sanitize_text_field( $_REQUEST['vipthemehelper-svn_username'] );
			}
			if ( isset( $_REQUEST['vipthemehelper-svn_password'] ) ) {
				$credentials['password'] = sanitize_text_field( $_REQUEST['vipthemehelper-svn_password'] );
			}
			
			$result = $plugins['RepoMonitor']->add_repo( array(
				'repo_type'			 => 'svn',
				'repo_path'			 => $wp_theme->get_stylesheet_directory(),
				'repo_friendly_name' => $wp_theme->display( 'Name', false ),
			), true, false, $credentials );
			
			if ( is_wp_error( $result ) ) {
				?>
				<div class="error"><p><?php printf( __( 'An error occured adding the theme to the RepoMonitor: %s', 'quickstart-dashboard' ), $result->get_error_message() ) ?></p></div>
				<?php
				
				return;
			}
		}

		$message = __( 'The theme was installed.', 'quickstart-dashboard' );

		// Activate it if we're supposed to activate it
		if ( $activate ) {
			if ( $this->activate_vip_theme( $theme ) ) {
				$message = __( 'The theme was installed and activated.', 'quickstart-dashboard' );
			} else {
				$message = __( 'The theme was installed but activation failed.', 'quickstart-dashboard' );
			}
		}

		?>
		<div class="updated"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	function activate_vip_theme( $theme ) {
		$themes = $this->get_vip_scanned_themes();
		if ( !isset( $themes[$theme] ) ) {
			?>
			<div class="error"><p><?php printf( __( 'Error: Could not activate unknown theme: %s', 'quickstart-dashboard' ), $theme ); ?></p></div>
			<?php
			return false;
		}

		// Force a rescan of theme directories
		search_theme_directories( true );

		// First locate the theme stylesheet name
		$installed_themes = wp_get_themes( array( 'allowed' => null ) );
		
		// Get the stylesheet to activate the theme
		$stylesheet = '';
		if ( isset( $installed_themes[$theme] ) ) {
			$stylesheet = $theme;
		} elseif ( isset( $installed_themes['vip/' . $theme] ) ) {
			$stylesheet = 'vip/' . $theme;
		} else {
			?>
			<div class="error"><p><?php printf( __( 'Error: Could not activate because the stylesheet directory could not be found for theme: %s', 'quickstart-dashboard' ), $theme ); ?></p></div>
			<?php
			return false;
		}

		// Make sure that the theme is multisite enabled
		$allowed_themes = get_site_option( 'allowedthemes' );
		$allowed_themes[$theme] = true;
		update_site_option( 'allowedthemes', $allowed_themes );

		// Switch the theme
		switch_theme( $stylesheet );

		// Get the themes again. If the switch was successful the VIP theme will now be marked as activated.
		$themes = $this->get_vip_scanned_themes();
		return $themes[$theme]['activated'];
	}

	function print_svn_credential_form( $theme ) {
		?>
		<div class="error">
			<h3><?php _e( 'SVN Credentials', 'quickstart-dashboard' ); ?></h3>
			<form action="<?php menu_page_url( 'vip-dashboard' ) ?>" method="post">
				<?php wp_nonce_field( 'vipthemehelper-install_and_activate', '_thnonce' ) ?>
				<input type="hidden" name="vipthemehelper-action" value="<?php echo esc_attr( $theme ); ?>" />
				<p><?php printf( __( 'Please enter the SVN Credentials for <code>%s</code>.', 'quickstart-dashboard' ), esc_attr( $this->svn_uri . $theme ) ) ?></p>
				<table class="form-table">
					<tr>
						<td><label for="vipthemehelper-svn_username"><?php _e( 'SVN Username:', 'quickstart-dashboard' ); ?></label></td>
						<td><input type="text" id="vipthemehelper-svn_username" name="vipthemehelper-svn_username" value="" /></td>
					</tr>
					<tr>
						<td><label for="vipthemehelper-svn_password"><?php _e( 'SVN Password:', 'quickstart-dashboard' ); ?></label></td>
						<td><input type="password" id="vipthemehelper-svn_password" name="vipthemehelper-svn_password" value="" /></td>
					</tr>
					<tr>
						<td><label for="vipthemehelper-install"><?php _e( 'Install Theme:', 'quickstart-dashboard' ); ?></label></td>
						<td><input type="checkbox" id="vipthemehelper-install" name="vipthemehelper-install"<?php echo isset( $_REQUEST['vipthemehelper-install'] ) ? 'checked="checked"' : '' ?> /></td>
					</tr>
					<tr>
						<td><label for="vipthemehelper-activate"><?php _e( 'Activate Theme:', 'quickstart-dashboard' ); ?></label></td>
						<td><input type="checkbox" id="vipthemehelper-install" name="vipthemehelper-activate"<?php echo isset( $_REQUEST['vipthemehelper-activate'] ) ? 'checked="checked"' : '' ?> /></td>
					</tr>
				</table>

				<p>
					<input class="button-primary" type="submit" name="vipthemehelper-svn_credential_form" value="<?php _e( 'Install', 'quickstart-dashboard' ); ?>" />
					<input class="button-secondary" type="submit" name="vipthemehelper-svn_credential_form_cancel" value="<?php _e( 'Cancel', 'quickstart-dashboard' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	function print_admin_notice() {
		if ( ! empty( $this->needs_credentials ) ) {
			$this->print_svn_credential_form( $this->needs_credentials );
			return;
		}

		$this->print_vip_themes_notice();
	}

	function print_vip_themes_notice() {
		// Check if we should print a notice about VIP themes
		$installed_themes = array();
		$available_themes = array();
		foreach ( $this->get_vip_scanned_themes() as $slug => $theme ) {
			if ( $theme['activated'] ) {
				// If a VIP theme is activated, don't show any notices
				return;
			} elseif ( $theme['installed'] && $theme['notify'] && !$theme['activated'] ) {
				$installed_themes[] = $slug;
			} elseif ( !$theme['installed'] && $theme['notify'] ) {
				$available_themes[] = $slug;
			}
		}

		$available_theme_count = count( $available_themes );
		$installed_theme_count = count( $installed_themes );

		$message = '';
		$install_nonce = wp_create_nonce( 'vipthemehelper-install_and_activate' );
		if ( $available_theme_count && $installed_theme_count ) {
			$message = sprintf(
				__( 'Quickstart Dashboard detected that you have access to <strong>"%1$s"</strong> remote VIP Themes and <strong>%2$s</strong> local VIP themes but have not activated any. Go to the <a href="%3$s">Quickstart VIP Dashboard</a> to manage your VIP Themes.', 'quickstart-dashboard' ),
				$available_theme_count,
				$installed_theme_count,
				menu_page_url( 'vip-dashboard', false )
			);
		} elseif ( $available_theme_count ) {
			// There are no local themes, only remote ones that havn't been pulled
			if ( 1 === $available_theme_count ) {
				$message = sprintf(
					__( 'Quickstart Dashboard detected that you have access to the VIP theme <strong>%1$s</strong> but have not installed it. <a href="%2$s">Click here to install and activate it.</a>', 'quickstart-dashboard' ),
					$available_themes[0],
					$this->get_theme_action_link( $available_themes[0], true, true, $install_nonce )
				);
			} else {
				$message = sprintf(
					__( 'Quickstart Dashboard detected that you have access to <strong>%1$s</strong> VIP themes but have not installed them. <a href="%2$s">Click here to install and activate them.</a>', 'quickstart-dashboard' ),
					$available_theme_count,
					$this->get_theme_action_link( 'all', true, false, $install_nonce )
				);
			}
		} elseif ( $installed_theme_count ) {
			// There are only local themes that havn't been activated
			if ( 1 === $installed_theme_count ) {
				$message = sprintf(
					__( 'Quickstart Dashboard detected that you have installed the VIP theme <strong>%1$s</strong> but have not activated it. <a href="%2$s">Click here to activate it.</a>', 'quickstart-dashboard' ),
					$installed_themes[0],
					$this->get_theme_action_link( $installed_themes[0], false, true, $install_nonce )
				);
			} else {
				$message = sprintf(
					__( 'Quickstart Dashboard detected that you have access to <strong>%0$s</strong> VIP themes but have not installed them. <a href="%1$s">Click here to install and activate them.</a>', 'quickstart-dashboard' ),
					$installed_theme_count,
					add_query_arg( array( 'vipthemehelper-activate' => 'all', '_thnonce' => $install_nonce ), menu_page_url( 'vip-dashboard', false ) )
				);
			}
		} else {
			// No installed or available themes
			return;
		}

		// Show the message
		?>
		<div class="updated"><p><?php echo wp_kses( $message, wp_kses_allowed_html( 'post' ) ); ?></p></div>
		<?php
	}

	function has_scanned_vip_themes() {
		return (bool) get_option( 'qs_vipthemehelper_has_scanned_themes', false );
	}

	function theme_admin_notices() {
		return get_option( 'qs_vipthemehelper_show_admin_notice', array() );
	}

	function should_show_theme_notice( $theme ) {
		
	}

	function scan_vip_themes() {
		if ( empty( $this->access_token ) )
			return;

		$request_args = array(
			'headers' => array( 'Authorization' => 'Bearer ' . $this->access_token, ),
		);

		$result = wp_remote_get( $this->wpcom_endpoints['vip-themes'], $request_args );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'could_not_query_dotcom', sprintf( __( 'Error: Could not query VIP Themes from WordPress.com: %s', 'quickstart-dashboard' ), $result->get_error_message() ) );
		}

		$themes = json_decode( $result['body'], true );
		
		if ( ! isset( $themes['themes'] ) ) {
			return new WP_Error( 'unexpected_result', sprintf( __( 'Error: Could not query VIP Themes from WordPress.com: %s', 'quickstart-dashboard' ), $result['body'] ) );
		}
		
		if ( isset( $themes['error'] ) ) {
			return new WP_Error( 'query_error_occured', sprintf( __( 'An error occured querying VIP Themes from WordPress.com: %s', 'quickstart-dashboard' ), $themes['message'] ) );
		}

		$current_theme = wp_get_theme();
		$installed_themes = wp_get_themes( array( 'allowed' => null ) );
		$network_allowed_themes = get_site_option( 'allowedthemes' );

		// Check if the user has activated a VIP theme
		$vip_theme_active = false;

		// Whether to notify users about that themes' status by default
		$notify_by_default = apply_filters( 'qs_vipthemehelper_notify_default', true );

		// Now look and see if we should show notices about any of these themes
		$scanned_themes = $this->get_vip_scanned_themes();
		foreach ( $themes['themes'] as $theme ) {
			if ( $theme['slug'] == 'plugins' ) {
				continue;
			}

			// Check if this theme is available in the known places
			if ( array_key_exists( $theme['slug'], $installed_themes ) || array_key_exists( "vip/{$theme['slug']}", $installed_themes ) ) {
				// Remark that this theme is installed
				$scanned_themes[$theme['slug']] = array(
					'installed'		  => true,
					'activated'		  => $theme['slug'] == $current_theme->get_stylesheet() || "vip/{$theme['slug']}" == $current_theme->get_stylesheet(),
					'network_enabled' => isset( $network_allowed_themes[$theme['slug']] ) && $network_allowed_themes[$theme['slug']],
					'notify'		  => isset( $scanned_themes[$theme['slug']] ) ? $scanned_themes[$theme['slug']]['notify'] : $notify_by_default,
					'stylesheet'	  => array_key_exists( "vip/{$theme['slug']}", $installed_themes ) ? "vip/{$theme['slug']}" : $theme['slug'],
				);

				if ( $scanned_themes[$theme['slug']]['activated'] ) {
					$vip_theme_active = true;
				}
			} else {
				//  The theme cannot be found, we should prompt the user to pull it!
				$scanned_themes[$theme['slug']] = array(
					'installed'		  => false,
					'activated'		  => false,
					'network_enabled' => false,
					'notify'		  => isset( $scanned_themes[$theme['slug']] ) ? $scanned_themes[$theme['slug']]['notify'] : $notify_by_default,
					'stylesheet'	  => '',
				);
			}
		}

		update_option( 'qs_vipthemehelper_has_scanned_themes', true );
		$this->set_vip_scanned_themes( $scanned_themes );
		return true;
	}

	function ajax_update_themes() {
		if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-themes' ) ) {
			wp_send_json_error( array( 'error' => __( 'You do not have sufficient permissions to access this page.', 'quickstart-dashboard' ) ) );
		}

		// Check that we're connected to .com
		if ( empty( $this->access_token ) ) {
			$this->access_token = Quickstart_Dashboard::get_wpcom_access_token();
		}

		if ( empty( $this->access_token ) ) {
			// We aren't connected to .com
			wp_send_json_error( new WP_Error( 'not_connected_to_wpcom', __( 'Could not fetch VIP Themes because quickstart dashboard is not connected to WordPress.com' ) ) );
		}

		// Query the server for a list of vip themes for the current user
		$result = $this->scan_vip_themes();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}

		// Put the data into a more consumable format
		$themes = $this->get_vip_scanned_themes();
		foreach ( $themes as $slug => $theme ) {
			$theme['actions'] = array();

			if ( ! $theme['installed'] ) {
				$theme['actions']['install'] = array( $this->get_theme_action_link( $slug, true, false ), __( 'Install', 'quickstart-dashboard' ) );
				$theme['actions']['install_activate'] = array( $this->get_theme_action_link( $slug, true, true ), __( 'Install and Activate', 'quickstart-dashboard' ) );
			} elseif ( ! $theme['activated'] ) {
				$theme['actions']['activate'] = array( $this->get_theme_action_link( $slug, false, true ), __( 'Activate', 'quickstart-dashboard' ) );
			}

			$wp_theme = wp_get_theme( $theme['stylesheet'] );

			if ( ! empty( $theme['stylesheet'] ) && $wp_theme->exists() ) {
				$themes[$slug] = array_merge( $theme, array(
					'slug'		  => $slug,
					'theme_name'  => $wp_theme->display( 'Name' ),
					'description' => $wp_theme->display( 'Description' ),
				) );
			} else {
				$themes[$slug] = array_merge( $theme, array(
					'slug'		  => $slug,
					'theme_name'  => $slug,
					'description' => '',
				) );
			}
		}

		wp_send_json_success( $themes );
	}

	function get_vip_scanned_themes() {
		return get_option( 'qs_vipthemehelper_vip_themes', array() );
	}

	function set_vip_scanned_themes( $scanned_themes ) {
		return update_option( 'qs_vipthemehelper_vip_themes', $scanned_themes );
	}

	function get_theme_action_link( $theme_slug, $install = true, $activate = true, $nonce = null ) {
		if ( is_null( $nonce ) ) {
			$nonce = wp_create_nonce( 'vipthemehelper-install_and_activate' );
		}

		$args = array( 'vipthemehelper-action' => $theme_slug, '_thnonce' => $nonce );

		if ( $install ) {
			$args['vipthemehelper-install'] = true;
		}

		if ( $activate ) {
			$args['vipthemehelper-activate'] = true;
		}

		return add_query_arg( $args, menu_page_url( 'vip-dashboard', false ) );
	}
}

class ThemeHelperWidgetTable extends DashboardWidgetTable {
	/**
	 * @var VIPThemeHelper
	 */
	private $theme_helper = null;

	function __construct( $theme_helper ) {
		$this->theme_helper = $theme_helper;

		parent::__construct( array(
			'singular'  => 'theme',
			'plural'    => 'themes',
			'ajax'      => false
		) );
	}

	function get_table_classes() {
		$classes = parent::get_table_classes();
		$classes[] = 'vip-dashboard-vipthemehelper-table';
		return $classes;
	}

	function single_row( $item ) {
		$row_classes = parent::get_row_classes();
		
		if ( $item['activated'] ) {
			$row_classes[] = 'update';
		}

		if ( $item['installed'] ) {
			$row_classes[] = 'active';
		}

		echo '<tr id="viptheme-' . $item['slug'] . '-status" class="' . implode( ' ', $row_classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	function column_default( $item, $column_name ){
		$retval = '';
		switch( $column_name ){
			default:
				$retval = $item[$column_name];
		}

		return $retval;
	}

	function column_theme_name( $item ){
		$page = esc_attr( $_REQUEST['page'] );

		$link_format = '<a href="%s">%s</a>';

		//Build row actions
		$actions = array();

		if ( ! $item['installed'] ) {
			$actions['install'] = sprintf( $link_format, $this->theme_helper->get_theme_action_link( $item['slug'], true, false ), __( 'Install', 'quickstart-dashboard' ) );
			$actions['install_activate'] = sprintf( $link_format, $this->theme_helper->get_theme_action_link( $item['slug'], true, true ), __( 'Install and Activate', 'quickstart-dashboard' ) );
		} elseif ( ! $item['activated'] ) {
			$actions['activate'] = sprintf( $link_format, $this->theme_helper->get_theme_action_link( $item['slug'], false, true ), __( 'Activate', 'quickstart-dashboard' ) );
		}

		//Return the title contents
		return "<strong>{$item['theme_name']}</strong>" . $this->row_actions( $actions, true );
	}

	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item['slug']
		);
	}

	function get_columns(){
		$cols = array(
			'cb'		  => '<input type="checkbox" />', //Render a checkbox instead of text
			'theme_name'  => __( 'Theme', 'quickstart-dashboard' ),
			'description' => __( 'Description', 'quickstart-dashboard' ),
		);

		return apply_filters( 'vipthemehelper_table_get_columns', $cols );
	}

	function get_sortable_columns() {
		return apply_filters( 'vipthemehelper_table_get_sortable_columns', array() );
	}

	function get_bulk_actions() {
		return apply_filters( 'vipthemehelper_table_bulk_actions', array(
			'install'    => 'Install'
		) );
	}

	function process_bulk_action() {
		do_action( 'vipthemehelper_table_do_bulk_actions' );
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$total_items = 0;
		$this->items = array();
		foreach ( $this->theme_helper->get_vip_scanned_themes() as $slug => $theme ) {
			$wp_theme = wp_get_theme( $theme['stylesheet'] );

			if ( ! empty( $theme['stylesheet'] ) && $wp_theme->exists() ) {
				$this->items[] = array_merge( $theme, array(
					'slug'		  => $slug,
					'theme_name'  => $wp_theme->display( 'Name' ),
					'description' => $wp_theme->display( 'Description' ),
				) );
			} else {
				$this->items[] = array_merge( $theme, array(
					'slug'		  => $slug,
					'theme_name'  => $slug,
					'description' => '',
				) );
			}

			$total_items += 1;
		}

	}
}
