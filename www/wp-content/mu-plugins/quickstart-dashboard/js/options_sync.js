function options_sync_preview() {
	jQuery( function( $ ) {
		/**
		 * Checks the dependencies for all of the files to be imported
		 */
		function check_action_dependencies() {
			var dependencies = options_sync_settings['table_dependencies'];
			
			for ( var d in dependencies ) {
				// Skip this item if it has no dependencies
				if ( 0 === dependencies[d].length ) {
					continue;
				}
				
				var freshly_warned = false;
				
				for ( var field in dependencies[d] ) {
					var other_table = dependencies[d][field];
					var other_table_action = get_table_action_setting( other_table );
					var this_settings = $( get_table_action_element_selector( d ) );
					var tr_parent = this_settings.parents( 'tr' );
					
					if ( 'skip' === other_table_action ) {
						tr_parent.addClass( 'warn' ).removeClass( 'inactive' );
						this_settings.val( 'skip' ).attr( 'disabled', 'disabled' );
						freshly_warned = true;
						
						// Show an explanatory message
						show_row_message( tr_parent, options_sync_settings.translations.table_dependency_skipped
							.replace( '{other_table}', '<code>' + other_table + '</code>' )
						);
						
					} else if ( get_action_value( other_table_action ) < get_action_value( this_settings.val() ) ) {
						tr_parent.addClass( 'warn' ).removeClass( 'inactive' );
						
						// Show an explanatory message
						show_row_message( tr_parent, options_sync_settings.translations.table_dependency_conflict
							.replace( '{other_table}', '<code>' + other_table + '</code>' )
							.replace( '{other_table_action}', '<code>' + other_table_action + '</code>' )
						);
				
						freshly_warned = true;

					} else if ( !freshly_warned ) {
						this_settings.parents( 'tr' ).addClass( 'inactive' ).removeClass( 'warn' );
						this_settings.siblings( '.actiong-warning-explanation' ).html();
						
						// If this element was disabled due to the setting on another, clone the others' setting
						if ( this_settings.attr( 'disabled' ) === 'disabled' ) {
							this_settings.removeAttr( 'disabled' );
							this_settings.val( other_table_action );
						}
						
						// Clear the warning message
						clear_row_message( tr_parent );
					}
					
					update_action_description( this_settings );
				}
			}
		}
		
		function show_row_message( tr_parent, message_text ) {
			if ( ! tr_parent.next().hasClass( 'warning-message' ) ) {
				$( '<tr class="warn warning-message"><td></td><td colspan="3"><div>' + message_text + '</div></td></tr>' ).insertAfter( tr_parent );
			}
		}
		
		function clear_row_message( tr_parent ) {
			if ( tr_parent.next().hasClass( 'warning-message' ) ) {
				 tr_parent.next().remove();
			}
		}
		
		function get_table_action_setting( table ) {
			return $( get_table_action_element_selector(table) ).val();
		}
		
		function get_table_action_element_selector( table ) {
			return '#options-action-select-' + table;
		}
		
		/**
		 * Assigns a numerical value to an action.
		 * @param string action The action
		 * @returns number
		 */
		function get_action_value( action ) {
			switch ( action ) {
				case 'skip':
					return 0;
				case 'merge-import': 
					return 1;
				case 'destructive-import':
					return 2;
				default:
					return 0;
			}
		}
		
		function update_action_description( element ) {
			$( element ).parents( 'tr' ).children( '.column-description' ).html( options_sync_settings['action_descriptions'][$( element ).val()] );
		}
		
		$( '.options-action-select' ).each( function() { 
			check_action_dependencies();
			
		} ).change( function() {
			// Update the action description for this select box
			update_action_description( this );
			
			// Check the dependencies for this box to make sure there's nothing invalid
			check_action_dependencies();
		} );
	} );
}

function options_sync_package_downloader() {
	jQuery( function( $ ) {
		var current_state = -1;
		var status_action_interval = false;
		var request_package_interval = 15000;
		var states = [ 'request-package', 'download-package', 'generate-preview' ];
		next_state();

		function update_status_row() {
			var status = states[current_state];

			// Mark the old status row as complete
			$( '#options-sync-status-table .active .status-column' ).html( 'Done.' );
			$( '#options-sync-status-table .active' ).removeClass( 'active' );

			// Mark the new active row
			$( '#options-sync-status-table .' + status ).show().addClass( 'active' );
			$( '#options-sync-status-table .' + status + ' .status-column span' ).addClass('spinner').show();
		}

		function do_status_actions() {
			var request = $.ajax( ajaxurl, {
				data: {
					action: 'qs_options_sync-' + states[current_state],
					_wpnonce: $( '#wpnonce' ).val(),
				},
				dataType: 'json',
			} );

			switch ( current_state ) {
				case 0:
					if ( !status_action_interval ) {
						status_action_interval = setInterval( do_status_actions, request_package_interval );
					}

					// We're requesting the package. Every 15 seconds check the package status
					request.complete( parse_package_generation_status_response );
					break;

				case 1:
					// Download the package
					request.complete( parse_package_download_response );
					break;

				case 2:
					// Get the next url
					request.complete( parse_package_generate_preview_response );
					break;
			}
		}

		function parse_package_generation_status_response( full_response ) {
			console.log( full_response );
			var response = full_response.responseJSON;

			if ( ! response.success ) {
				handle_failure();
				return;
			}

			if ( response.data.package_generation_complete ) {
				// Package generation is complete, move on to the next step
				next_state();
			} else {
				// Package generation is not yet complete.
			}
		}

		function parse_package_download_response( full_response ) {
			console.log( full_response );
			var response = full_response.responseJSON;

			if ( ! response.success ) {
				handle_failure();
				return;
			}

			next_state();
		}

		function parse_package_generate_preview_response( full_response ) {
			console.log( full_response );
			var response = full_response.responseJSON;

			if ( ! response.success || typeof response.data.preview_url === 'undefined' ) {
				handle_failure();
				return;
			}

			window.location.replace( response.data.preview_url );
		}

		function next_state() {
			++current_state;

			if ( status_action_interval ) {
				clearInterval( status_action_interval );
				status_action_interval = false;
			}

			update_status_row();

			do_status_actions();
		}

		function handle_failure() {
			current_state = -1;

			if ( status_action_interval ) {
				clearInterval( status_action_interval );
				status_action_interval = false;
			}

			var status = states[current_state];
			$( '#options-sync-status-table .active .status-column' ).html( 'Failed.' );
			$( '#options-sync-status-table .active' ).removeClass( 'active' ).addClass( 'failed' );
		}
	} );
}