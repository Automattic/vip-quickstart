( function ( $ ) {
	$(document).ready( function () {
		var updating = false;
		var repos = [];
		var current_action_index = 0;
		var current_update_repo = 0;
		var animation_speed = 'slow';
		
		$( 'a.widget_update' ).click( start_repomonitor_update );
		
		function start_repomonitor_update() {
			current_update_repo = -1;
			
			// Start the ajax query
			ajax_get_repo_list();
				
			show_update_box();
			add_action( 'fetching-repos', repomonitor_settings.translations.fetching_repos );
		}
		
		function finish_repomonitor_update() {
			add_action( 'updating-status-table', repomonitor_settings.translations.updating_table );
			
			update_repo_status_table();
			
			complete_action( 'updating-status-table' );
			
			hide_update_box();
		}
		
		function update_repo_status_table() {
			for ( var r in repos ) {
				update_status_row_for_repo( repos[r] );
			}
		}
		
		function update_status_row_for_repo( repo ) {
			var $row = $( '#repo-' + repo['repo_id'] + '-status' );

			// Get the current status of the row
			var current_status = 'inactive';
			if ( $row.hasClass( 'active' ) ) {
				current_status = 'active';
			}
			if ( $row.hasClass( 'update' ) ) {
				current_status = 'update';
			}

			// Figure out what the new status will be
			var new_status = 'inactive';
			if ( repo.out_of_date ) {
				if ( repo.can_update ) {
					new_status = 'active';
				} else {
					new_status = 'update';
				}
			}

			// If we don't have to update the row status, just update the text
			$row.children( '.column-status' ).html( repo.status_text );
			if ( current_status === new_status ) {
				return;
			} else {
				if ( 'update' === current_status && $row.next().hasClass( 'plugin-update-tr' ) ) {
					// The current status is warn, but the new status is not. We need to remove the next row which is a status message
					$row.next().remove();
				}

				// Remove the current status
				$row.removeClass( current_status );
				
				// Add the new status
				$row.addClass( new_status );
				
				if ( 'active' === new_status ) {
					// Add the row actions div if its missing
					if ( ! $( '.column-repo_friendly_name .row-actions', $row ).length ) {
						$row.children( '.column-repo_friendly_name' ).append( '<div class="row-actions visible"></div>' );
					}

					// Update the row action if its missing
					if ( ! $( '.column-repo_friendly_name .row-actions .update', $row ).length ) {
						$( '.row-actions', $row ).append(
							'<span class="update"><a href="{update_link}" title="{update_descr}" class="thickbox">{update_action}</a></span>'
							.replace( '{update_link}', repo['update_link'] )
							.replace( '{update_descr}', repomonitor_settings.translations.update_descr )
							.replace( '{update_action}', repomonitor_settings.translations.update_action )
						);
					}
				} else {
					// Remove the actions div
					$( '.column-repo_friendly_name .row-actions', $row ).remove();
				}
			}
		}
		
		function parse_repo_list_ajax_response( response ) {
			if ( !response.success ) {
				// We should alert the user that something happened
				return;
			}
			
			// Mark the list action complete
			complete_action( 'fetching-repos' );
			
			// Loop through repos, updating the table.
			repos = response.data;
			scan_next_repo();
		}
		
		function parse_repo_scan_ajax_response( response ) {
			// Mark the current action coplete
			complete_action( 'updating-repo-' + repos[current_update_repo]['repo_id'], response.success );
			
			// Save this repo's info if the scan succeeded
			if ( response.sucess ) {
				for ( var r in repos ) {
					if ( repos[r]['repo_id'] === response.data['repo_id'] ) {
						repos[r] = response.data;
						break;
					}
				}
			}
			
			// Update the next repo
			scan_next_repo();
		}
		
		function scan_next_repo() {
			current_update_repo += 1;
			
			// Check if we're done updating
			if ( current_update_repo === repos.length ) {
				finish_repomonitor_update();
				return;
			}
			
			var repo = repos[current_update_repo];
			
			// Send the ajax request first
			ajax_scan_repo( repo['repo_id'] );
			
			// Now show the user what we're doing
			add_action( 
				'updating-repo-' + repo['repo_id'], 
				'(' + ( current_update_repo + 1 ) + '/' + repos.length + ') ' + 
						repomonitor_settings.translations.scanning_repo.replace( '{repo_name}', repo['repo_friendly_name'] ) 
			);
		}
		
		function add_action( action_slug, action_text ) {
			$( '#repomonitor-update-box' ).append( '<p id="repomonitor-update-status-' + current_action_index + '" class="repomonitor-update-status ' + action_slug + '">' + action_text + '...<span class="spinner"></span></p>' );
			$( '#repomonitor-update-box .' + action_slug + ' .spinner' ).show();
			$( '#repomonitor-update-box .' + action_slug ).slideDown( {
				duration: animation_speed,
				easing: 'linear',
			} );
			
			current_action_index += 1;
		}
		
		function complete_action( action_slug, success ) {
			var action = $( '#repomonitor-update-box' ).children( '.' + action_slug );
			action.children( '.spinner' ).remove();

			if ( typeof sucess === 'undefined' || success ) {
				action.html( action.html() + ' ' + repomonitor_settings.translations.action_done );
			} else {
				action.html( action.html() + ' ' + repomonitor_settings.translations.action_fail );
			}
		}
		
		function ajax_get_repo_list() {
			return $.ajax( ajaxurl, {
				data: {
					action: 'repomonitor_list_repos',
					_wpnonce: $( '#quickstart_dashboard_repomonitor #_wpnonce' ).val(),
				},
			} ).done( parse_repo_list_ajax_response );
		}
		
		function ajax_scan_repo( repo_id ) {
			return $.ajax( ajaxurl, {
				data: {
					action: 'repomonitor_scan_repo',
					_wpnonce: $( '#quickstart_dashboard_repomonitor #_wpnonce' ).val(),
					repo_id: repo_id,
				},
			} ).done( parse_repo_scan_ajax_response );
		}
		
		function show_update_box() {
			// hide the refresh button and show the spinner
			var $refresh = $( 'a.widget_update' );
			$refresh.hide();
			$refresh.siblings( '.widget_update_status' ).show();
			
			// Reset the action index
			current_action_index = 0;
			
			var $update_box = $( '#repomonitor-update-box' );
			$update_box.html( '' );
			$update_box.parent().slideUp( animation_speed, function() {
				$update_box.show();
				$update_box.siblings().hide();
				$update_box.parent().addClass( 'doing-update' ).slideDown( animation_speed );
			} );
			
			return $update_box;
		}
		
		function hide_update_box() {
			// Re-enable the refresh button
			var $refresh = $( 'a.widget_update' );
			$refresh.show();
			$refresh.siblings( '.widget_update_status' ).hide();

			// Hide the update box
			var $update_box = $( '#repomonitor-update-box' );
			$update_box.parent().slideUp( animation_speed, function() {
				$update_box.hide();
				$update_box.siblings().show();
				$update_box.parent().removeClass( 'doing-update' ).slideDown( animation_speed );
			} );
			
			return $update_box;
		}
	} );
} )( jQuery );