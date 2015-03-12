jQuery( function ( $ ) {
	var repos = [];
	var current_update_repo = 0;
	var repo_updates = [];
	var ui = new DashboardUIHelper( '#repomonitor-update-box' );
	ui.row_actions_selector = '.column-repo_friendly_name';
	ui.translations.action_done = repomonitor_settings.translations.action_done;
	ui.translations.action_fail = repomonitor_settings.translations.action_fail;

	$( '#quickstart_dashboard_repomonitor a.widget_update' ).click( start_repomonitor_update );
	$( 'a.repo-update' ).click( function () {
		// Figure out which repo we're updating..
		var repo_id = $( 'input.repo-id', $( this ).parents( 'tr' ) ).val();
		start_repo_updates( [ repo_id ] );
		return false;
	} );

	$( '#quickstart_dashboard_repomonitor input#doaction' ).click( function() {
		// Handle the bulk actions
		if ( $( '#quickstart_dashboard_repomonitor .bulkactions select' ).val() === 'update' ) {
			// Get a list of selected checkbox
			var repo_ids = [];
			$( '#quickstart_dashboard_repomonitor .check-column input' ).filter( function() {
				return $( this ).attr( 'checked' );
			} ).each( 
				function () {
					repo_ids.push( $( this ).val() );
					$( this ).removeAttr( 'checked' );
				}
			);

			start_repo_updates( repo_ids );

			return false;
		}
	} );

	function start_repo_updates( update_repos ) {
		repo_updates.push.apply( repo_updates, update_repos );

		ui.show_update_box();

		update_next_repo();
	}

	function update_next_repo() {
		if ( ! repo_updates.length ) {
			finish_repomonitor_update();
			return;
		}

		current_update_repo = repo_updates.pop();
		var $repo = $( '#repo-' + current_update_repo + '-status' );

		ajax_update_repo( current_update_repo );

		ui.add_action( 'update-repo-' + current_update_repo, "Updating " + $( '.column-repo_friendly_name strong', $repo ).html() );
	}

	function ajax_update_repo( repo_id ) {
		return $.ajax( ajaxurl, {
			data: {
				action: 'repomonitor_update_repo',
				repo_id: repo_id,
				_wpnonce: $( '#quickstart_dashboard_repomonitor #_wpnonce' ).val(),
			},
		} ).done( parse_repo_update_ajax_response );
	}

	function parse_repo_update_ajax_response( response ) {
		ui.complete_action( 'update-repo-' + current_update_repo, response.success );

		// Save this repo's info if the scan succeeded
		if ( response.success ) {
			var found = false;
			for ( var r in repos ) {
				if ( repos[r]['repo_id'] === response.data['repo_id'] ) {
					repos[r] = response.data;
					found = true;
					break;
				}
			}

			// If this item isn't already in the list, add it
			if ( !found ) {
				repos.push( response.data );
			}
		}

		update_next_repo();
	}

	function start_repomonitor_update() {
		current_update_repo = -1;

		// Start the ajax query
		ajax_get_repo_list();

		ui.show_update_box();
		ui.add_action( 'fetching-repos', repomonitor_settings.translations.fetching_repos );
	}

	function finish_repomonitor_update() {
		ui.add_action( 'updating-status-table', repomonitor_settings.translations.updating_table );

		update_repo_status_table();

		ui.complete_action( 'updating-status-table' );

		ui.hide_update_box();
	}

	function update_repo_status_table() {
		for ( var r in repos ) {
			update_status_row_for_repo( repos[r] );
		}
	}

	function update_status_row_for_repo( repo ) {
		var row_selector = '#repo-' + repo['repo_id'] + '-status';

		// Figure out what the new status will be
		var row_actions = '';
		var new_status = ui.row_status.inactive;

		if ( repo.out_of_date ) {
			if ( repo.can_update ) {
				new_status = ui.row_status.active;
				row_actions = 
					'<span class="update"><a href="{update_link}" title="{update_descr}" class="repo-update">{update_action}</a></span>'
					.replace( '{update_link}', repo['update_link'] )
					.replace( '{update_descr}', repomonitor_settings.translations.update_descr )
					.replace( '{update_action}', repomonitor_settings.translations.update_action )
				;
			} else {
				new_status = ui.row_status.update;
			}
		}

		ui.update_status_row( row_selector, new_status, repo.status_text, row_actions );
	}

	function parse_repo_list_ajax_response( response ) {
		if ( !response.success ) {
			// We should alert the user that something happened
			return;
		}

		// Mark the list action complete
		ui.complete_action( 'fetching-repos' );

		// Loop through repos, updating the table.
		repos = response.data;
		scan_next_repo();
	}

	function parse_repo_scan_ajax_response( response ) {
		// Mark the current action coplete
		ui.complete_action( 'updating-repo-' + repos[current_update_repo]['repo_id'], response.success );

		// Save this repo's info if the scan succeeded
		if ( response.success ) {
			var found = false;
			for ( var r in repos ) {
				if ( repos[r]['repo_id'] === response.data['repo_id'] ) {
					found = true;
					repos[r] = response.data;
					break;
				}
			}

			// If this item isn't already in the list, add it
			if ( !found ) {
				repos.push( response.data );
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
		ui.add_action( 
			'updating-repo-' + repo['repo_id'], 
			'(' + ( current_update_repo + 1 ) + '/' + repos.length + ') ' + 
					repomonitor_settings.translations.scanning_repo.replace( '{repo_name}', repo['repo_friendly_name'] ) 
		);
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
} );