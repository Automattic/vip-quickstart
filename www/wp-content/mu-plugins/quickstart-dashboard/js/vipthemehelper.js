jQuery( function ( $ ) {
	var ui = new DashboardUIHelper( '#themehelper-update-box' );
	ui.row_actions_selector = '.column-theme_name';
	ui.translations.action_done = vipthemehelper_settings.translations.action_done;
	ui.translations.action_fail = vipthemehelper_settings.translations.action_fail;

	$( '#quickstart_dashboard_vipthemehelper a.widget_update' ).click( start_vipthemehelper_update );

	function start_vipthemehelper_update() {
		ajax_update_vip_themes();

		ui.show_update_box();
		ui.add_action( 'vipthemehelper_update', vipthemehelper_settings.translations.get_vip_themes );
	}

	function ajax_update_vip_themes() {
		return $.ajax( ajaxurl, {
			data: {
				action: 'vipthemehelpher_update_themes',
				_wpnonce: $( '#quickstart_dashboard_vipthemehelper #_wpnonce' ).val(),
			},
		} ).done( parse_theme_update_ajax_response );
	}

	function parse_theme_update_ajax_response( response ) {
		// Mark the current action complete
		ui.complete_action( 'vipthemehelper_update', response.success );

		// Update the given row
		if ( response.success ) {
			for ( var t in response.data ) { 
				if( ! update_theme_status_row( response.data[t] ) ) {
					ui.add_action( 'vipthemehelper_needs_refresh', vipthemehelper_settings.translations.needs_refresh );
					return;
				}
			}
		}

		ui.hide_update_box();
	}

	function update_theme_status_row( theme ) {
		var row_selector = '#viptheme-' + theme['slug'] + '-status';

		// Figure out what the new status will be
		var row_actions = '';
		var new_status = ui.row_status.inactive;

		if ( theme['activated'] && theme['installed'] ) {
			new_status = ui.row_status.warn;
		} else if ( theme['activated'] ) {
			new_status = ui.row_status.update;
		} else if ( theme['installed'] ) {
			new_status = ui.row_status.active;
		}

		// Add the row actions
		console.log( 'theme actions', theme['actions'] );
		for ( var i in theme['actions'] ) {
			console.log( i );
			if ( row_actions.length ) {
				row_actions += ' | ';
			}

			row_actions += 
				'<span class="{action_slug}"><a href="{update_link}">{update_action}</a></span>'
				.replace( '{update_link}', theme['actions'][i][0] )
				.replace( '{update_action}', theme['actions'][i][1] )
				.replace( '{action_slug}', i )
			;
		}

		// Update the row
		if ( $( row_selector ).length ) {
			ui.update_status_row( row_selector, new_status, theme.description, row_actions );
		} else {
			// The row is not found. We need to refresh the dashboard page.
			return false;
		}

		return true;
	}
} );