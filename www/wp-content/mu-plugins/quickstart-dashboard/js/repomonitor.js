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
			if ( !response.success ) {
				return;
			}
			
			// Mark the current action coplete
			complete_action( 'updating-repo-' + repos[current_update_repo]['repo_id'] );
			
			// Update the next repo
			scan_next_repo();
			
			// Lastly, save this repo's info
			for ( var r in repos ) {
				if ( repos[r]['repo_id'] === response.data['repo_id'] ) {
					repos[r] = response.data;
					break;
				}
			}
		}
		
		function scan_next_repo() {
			current_update_repo += 1;
			
			// Check if we're done updating
			if ( current_update_repo === repos.length - 1 ) {
				hide_update_box();
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
		
		function complete_action( action_slug ) {
			var action = $( '#repomonitor-update-box' ).children( '.' + action_slug );
			action.children( '.spinner' ).remove();
			action.html( action.html() + ' ' + repomonitor_settings.translations.action_done );
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