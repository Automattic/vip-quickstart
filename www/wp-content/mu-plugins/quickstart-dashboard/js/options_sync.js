(function( $ ) {
	$( document ).ready( function() {
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
})(jQuery);