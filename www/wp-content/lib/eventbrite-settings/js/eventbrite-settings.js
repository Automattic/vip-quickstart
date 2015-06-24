/**
 *
 * Helper function to make ajax spinner appear when making ajax request
 *
 */
var eb_event_checklist_spinner = function(){
	jQuery( '#featured-event-checklist' ).html( '<div class="eb-spinner"></div>' );
}

jQuery(document).ready(function($){
	$( '#organizer-selection select' ).on( 'change', function(e) {
		eb_event_checklist_spinner();
		$( '#venue-selection select' ).val( 'all' );
		$( '.additional-venue-info' ).val('');
		var organizer_id   = $( this ).val();
		var nonce      = $( '#organizer-selection_nonce' ).val();
		var events     = $( '#featured-event-checklist' );
		var field_name = events.data( 'field-name' );
		var field_id   = events.data( 'field-id' );
		var data = {
			action: 'organizer_selected',
			organizer_selection_nonce: nonce,
			organizer_id: organizer_id,
			field_name: field_name,
			field_id: field_id
		};
		$.post( ajaxurl, data, function( response ){
			if ( response ) {
				$( '#featured-event-checklist' ).html( response );
			}
		});
	});
	$( '#venue-selection select' ).on( 'change', function(e) {
		eb_event_checklist_spinner();
		$( '.additional-venue-info' ).val('');
		var organizer_id = $( '#organizer-selection select' ).val();
		var venue_id     = $( this ).val();
		var nonce        = $( '#venue-selection_nonce' ).val();
		var events       = $( '#featured-event-checklist' );
		var field_name   = events.data( 'field-name' );
		var field_id     = events.data( 'field-id' );
		var data = {
			action: 'venue_selected',
			venue_selection_nonce: nonce,
			organizer_id: organizer_id,
			venue_id: venue_id,
			field_name: field_name,
			field_id: field_id
		};
		$.post( ajaxurl, data, function( response ){
			if ( response ) {
				$( '#featured-event-checklist' ).html( response );
			}
		});
	});

	var dropdowns  = $('.page-select select');
	dropdowns.on('change', function(e){
		var $this          = $(this);
		var dropdown_id    = $this.attr('id');
		var dropdown_value = $this.val();
		dropdowns.each( function( index, element ) {
			if ( dropdown_value != 0 && element.value == dropdown_value && element.id != dropdown_id ) {
				element.value = 0;
			}
		} );
	});

});