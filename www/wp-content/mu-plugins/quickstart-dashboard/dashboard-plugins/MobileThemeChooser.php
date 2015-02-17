<?php

/**
 * Mobile Theme Chooser
 *
 * Allow site admins to set mobile themes
 */
class MobileThemeChooser extends Dashboard_Plugin {
	
	public function init() {
		add_action( 'quickstart_dashboard_setup', array( $this, 'dashboard_setup' ) );
	}

	function dashboard_setup() {
        wp_add_dashboard_widget( 'quickstart_dashboard_mobile_theme_chooser', $this->name(), array( $this, 'show' ) );
	}

	public function name() {
		return __( 'Mobile Theme', 'quickstart-dashboard' );
	}

	function show() {

		$enable_edit = (bool) current_user_can( 'manage_options' );

		$show_enabled_status = true;

		$screen = get_current_screen();

		// Prevent editing from on the mobile page until we convert it to use Settings API
		if ( 'appearance_page_mobile-options' === $screen->id ) {
			$enable_edit 			= false;
			$show_enabled_status 	= false;
		}

		// TODO: We should probably use the settings API for this...
		if ( ( true === $enable_edit ) && ! empty( $_POST['vipmobilethemes'] ) ) {
			check_admin_referer( 'vip-mobile-themes' );

			if ( isset( $_POST['wp_mobile_template'] ) )
				update_option( 'wp_mobile_template',   wp_filter_nohtml_kses( trim( $_POST['wp_mobile_template']       ) ) );

			update_option( 'wp_mobile_smart_template', wp_filter_nohtml_kses( trim( $_POST['wp_mobile_smart_template'] ) ) );
			update_option( 'wp_mobile_dumb_template',  wp_filter_nohtml_kses( trim( $_POST['wp_mobile_dumb_template']  ) ) );
			update_option( 'wp_ipad_template',         wp_filter_nohtml_kses( trim( $_POST['wp_ipad_template']         ) ) );
			update_option( 'wp_tablet_template',       wp_filter_nohtml_kses( trim( $_POST['wp_tablet_template']       ) ) ); ?>

			<div id="message" class="updated fade"><p><strong><?php _e( 'Mobile theme settings have been saved.' ); ?></strong></p></div>

			<?php
		}

		$wp_mobile_smart_template = get_option( 'wp_mobile_smart_template' );
		$wp_mobile_dumb_template  = get_option( 'wp_mobile_dumb_template' );
		$wp_mobile_template       = get_option( 'wp_mobile_template' );
		$wp_ipad_template         = get_option( 'wp_ipad_template' );
		$wp_tablet_template       = get_option( 'wp_tablet_template' );

		$mobile_enabled           = (bool) ! get_option( 'wp_mobile_disable' );
		$ipad_enabled             = ! empty( $wp_ipad_template );
		$tablet_enabled           = ! empty( $wp_tablet_template );

		$default_dumb_theme       = __( 'Smart Phone Theme' );
		$default_smart_theme      = '<a href="http://en.support.wordpress.com/themes/mobile-themes/">WP Touch</a>';
		$default_tablet_theme     = wp_get_theme()->Name;
		$default_ipad_theme       = wp_get_theme()->Name; ?>

		<style>
		.vip-mobile-status { border: 1px solid transparent; border-radius: 3px; }
		.vip-mobile-enabled { background-color: #10943E; color: #fff; }
		.vip-mobile-disabled { background-color: #ff3443; color: #fff; }

		/* MP6 specific styles */
		.mp6 .form-table.vip-mobile-table-tablet th,
		.mp6 .form-table.vip-mobile-table-tablet td {
			border-bottom: 0;
		}
		.mp6 .form-table {
			background: #fff;
		}
		.mp6 .form-table th {
			padding-left: 16px;
		}
		.mp6 .form-table th,
		.mp6 .form-table td {
			border-bottom: 1px solid #F4F4F4;
		}
		</style>

		<?php if ( true === $enable_edit ) : ?>
		<form id="vipmobilethemesform" action="" method="post">
		<?php endif; ?>

		<table class="form-table vip-mobile-table-mobile">
			<?php if ( $show_enabled_status ) : ?>
				<tr valign="top">
					<th scope="row"><h4><?php _e( 'Mobile' ); ?></h4></th>
					<td>
						<?php $mobile_enabled_text = $mobile_enabled ? 'Enabled' : 'Disabled'; ?>
						<span class="vip-mobile-status vip-mobile-<?php echo strtolower( $mobile_enabled_text ); ?>"><?php echo $mobile_enabled_text; ?></span>
						<small><a href="<?php menu_page_url( 'mobile-options' ); ?>" target="_blank" title="Opens in new window">(Change)</a></small>
					</td>
				</tr>
			<?php endif; ?>
			<?php if( $enable_edit || $mobile_enabled ) : ?>
				<?php if( $enable_edit && $wp_mobile_template ) : ?>
				<tr valign="top">
					<th scope="row">
						<label for="wp_mobile_template"><?php _e( 'Smart+Dumb Phones' ); ?></label>
					</th>
					<td>
						<input name="wp_mobile_template" type="text" id="wp_mobile_template" value="<?php echo esc_attr( $wp_mobile_template ); ?>" class="regular-text" />
						<br />
						<small><?php _e( 'Note: This option overrides the two options below.' ); ?></small>
					</td>
				</tr>
				<?php endif; ?>

				<tr valign="top">
					<th scope="row">
						<label for="wp_mobile_smart_template"><?php _e( 'Smart Phones' ); ?><br /><small><?php _e( 'iPhone, Android, BlackBerry v5+ etc.' ); ?></small></label>
					</th>
					<td>
						<?php if( $enable_edit ) : ?>
							<input name="wp_mobile_smart_template" type="text" id="wp_mobile_smart_template" value="<?php echo esc_attr( $wp_mobile_smart_template ); ?>" class="regular-text" />
						<?php else : ?>

							<?php if( ! empty( $wp_mobile_smart_template ) ) : ?>
								<?php echo esc_html( $wp_mobile_smart_template ); ?>
							<?php else : ?>
								<?php
								if( empty( $wp_mobile_smart_template ) && ! empty( $wp_mobile_template ) )
									$wp_mobile_smart_template = $wp_mobile_template;
								echo ! empty( $wp_mobile_smart_template ) ? esc_html( $wp_mobile_smart_template ) : $default_smart_theme;
								?>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="wp_mobile_dumb_template">Dumb Phones <br /><small>BlackBerry (v4.7 and below), Nokia, etc.</small></label>
					</th>
					<td>
						<?php if( $enable_edit ) : ?>
							<input name="wp_mobile_dumb_template" type="text" id="wp_mobile_dumb_template" value="<?php echo esc_attr( $wp_mobile_dumb_template ); ?>" class="regular-text" />
							<br />
							<small><?php _e( 'Note: This option defaults to the Smart Phone Theme when left empty.' ); ?></small>
						<?php else : ?>
							<?php if( ! empty( $wp_mobile_dumb_template ) ) : ?>
								<?php echo esc_html( $wp_mobile_dumb_template ); ?>
							<?php else : ?>
								<?php
								if( empty( $wp_mobile_dumb_template ) && ! empty( $wp_mobile_template ) )
									$wp_mobile_dumb_template = $wp_mobile_template;
								echo ! empty( $wp_mobile_dumb_template ) ? esc_html( $wp_mobile_dumb_template ) : $default_dumb_theme;
								?>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>

			<?php endif; ?>
		</table>

		<table class="form-table vip-mobile-table-ipad">
			<tr valign="top">
				<th scope="row"><h4><?php _e( 'iPad' ); ?></h4></th>
				<td>
					<?php $ipad_enabled_text = $ipad_enabled ? __( 'Custom Theme' ) : __( 'Desktop Theme' ); ?>
					<span class="vip-mobile-status vip-mobile-ipad"><?php echo $ipad_enabled_text; ?></span>
				</td>
			</tr>
			<?php if( $enable_edit || $ipad_enabled ) : ?>
				<tr valign="top">
					<th scope="row">
						<label for="wp_ipad_template"><?php _e( 'Theme' ); ?></label>
					</th>
					<td>
						<?php if( $enable_edit ) : ?>
							<input name="wp_ipad_template" type="text" id="wp_ipad_template" value="<?php echo esc_attr( $wp_ipad_template ); ?>" class="regular-text" />
						<?php else : ?>
							<?php echo ! empty( $wp_ipad_template ) ? esc_html( $wp_ipad_template ) : $default_ipad_theme; ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<table class="form-table vip-mobile-table-tablet">
			<tr valign="top">
				<th scope="row"><h4><?php _e( 'All Tablets' ); ?></h4></th>
				<td>
					<?php $tablet_enabled_text = $tablet_enabled ? __( 'Custom Theme' ) : __( 'Desktop Theme' ); ?>
					<span class="vip-mobile-status vip-mobile-tablet"><?php echo $tablet_enabled_text; ?></span>
				</td>
			</tr>
			<?php if( $enable_edit || $tablet_enabled ) : ?>
				<tr valign="top">
					<th scope="row">
						<label for="wp_tablet_template"><?php _e( 'Theme' ); ?></label>
					</th>
					<td>
						<?php if ( $enable_edit ) : ?>
							<input name="wp_tablet_template" type="text" id="wp_tablet_template" value="<?php echo esc_attr( $wp_tablet_template ); ?>" class="regular-text" />
						<?php else : ?>
							<?php echo ! empty( $wp_tablet_template ) ? esc_html( $wp_tablet_template ) : $default_tablet_theme; ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<?php if( true === $enable_edit ) :
			wp_nonce_field( 'vip-mobile-themes' ); ?>
			<p class="submit">
				<?php submit_button( 'Save Changes', 'primary', 'vipmobilethemes', false ); ?>
			</p>
		</form>
		<?php endif;
	}
}
