function DashboardUIHelper( container ) {
	this.container = container;
}

// Public properties
DashboardUIHelper.prototype.container = '';
DashboardUIHelper.prototype.current_action_index = 0;
DashboardUIHelper.prototype.update_box_visible = false;
DashboardUIHelper.prototype.animation_speed = 'slow';
DashboardUIHelper.prototype.animation_easing = 'linear';
DashboardUIHelper.prototype.row_status_column_selector = '.column-status';
DashboardUIHelper.prototype.row_actions_selector = '.row-actions';
DashboardUIHelper.prototype.translations = {
	action_done: '',
	action_fail: '',
};
DashboardUIHelper.prototype.row_status = {
	inactive: 'inactive',
	active: 'active',
	update: 'update',
	warn: 'update active',
};

// Public members
DashboardUIHelper.prototype.add_action = function ( action_slug, action_text ) {
	jQuery( this.container ).append( '<p id="update-status-' + this.current_action_index + '" class="update-status ' + action_slug + '">' + action_text + '...<span class="spinner"></span></p>' );
	jQuery( this.container + ' .' + action_slug + ' .spinner' ).show();
	jQuery( this.container + ' .'  + action_slug ).slideDown( {
		duration: this.animation_speed,
		easing: this.animation_easing,
	} );

	this.current_action_index += 1;
};

DashboardUIHelper.prototype.complete_action = function ( action_slug, success ) {
	var action = jQuery( this.container ).children( '.' + action_slug );
	action.children( '.spinner' ).remove();

	if ( typeof success === 'undefined' || success ) {
		action.html( action.html() + ' ' + this.translations.action_done );
	} else {
		action.html( action.html() + ' ' + this.translations.action_fail );
	}
};

DashboardUIHelper.prototype.show_update_box = function () {
	if ( this.update_box_visible ) {
		return;
	}

	// hide the refresh button and show the spinner
	var $refresh = jQuery( 'a.widget_update' );
	$refresh.hide();
	$refresh.siblings( '.widget_update_status' ).show();

	// Reset the action index
	this.current_action_index = 0;

	var $update_box = jQuery( this.container );
	$update_box.html( '' );
	$update_box.parent().slideUp( this.animation_speed, function() {
		$update_box.show();
		$update_box.siblings().hide();
		$update_box.parent().addClass( 'doing-update' ).slideDown( this.animation_speed );
	} );

	this.update_box_visible = true;

	return $update_box;
};

DashboardUIHelper.prototype.hide_update_box = function () {
	if ( ! this.update_box_visible ) {
		return;
	}

	// Re-enable the refresh button
	var $refresh = jQuery( 'a.widget_update' );
	$refresh.show();
	$refresh.siblings( '.widget_update_status' ).hide();

	// Hide the update box
	var $update_box = jQuery( this.container );
	$update_box.parent().slideUp( this.animation_speed, function() {
		$update_box.hide();
		$update_box.siblings().show();
		$update_box.parent().removeClass( 'doing-update' ).slideDown( this.animation_speed );
	} );

	this.update_box_visible = false;

	return $update_box;
};

DashboardUIHelper.prototype.update_status_row = function ( row_selector, new_status, status_text, row_actions ) {
	var $row = jQuery( row_selector );

	// Get the current status of the row
	var current_status = this.row_status.inactive;
	if ( $row.hasClass( this.row_status.active ) && $row.hasClass( this.row_status.update ) ) {
		current_status = this.row_status.warn;
	} else if ( $row.hasClass( this.row_status.active ) ) {
		current_status = this.row_status.active;
	} else if ( $row.hasClass( this.row_status.update ) ) {
		current_status = this.row_status.update;
	}

	// If we don't have to update the row status, just update the text
	$row.children( this.row_status_column_selector ).html( status_text );
	
	if ( row_actions.length ) {
		// Add the row actions div if its missing
		if ( ! jQuery( this.row_actions_selector + ' .row-actions', $row ).length ) {
			$row.children( this.row_actions_selector ).append( '<div class="row-actions visible"></div>' );
		}

		// Update the row action if its missing -- .update
		if ( ! jQuery( this.row_actions_selector + ' .row-actions', $row ).length ) {
			jQuery( '.row-actions', $row ).empty().append( row_actions );
		}
	} else {
		// Remove the actions div
		jQuery( this.row_actions_selector + ' .row-actions', $row ).remove();
	}
	
	if ( current_status === new_status ) {
		return;
	} else {
		if ( this.row_status.warn === current_status && $row.next().hasClass( 'plugin-update-tr' ) ) {
			// The current status is warn, but the new status is not. We need to remove the next row which is a status message
			$row.next().remove();
		}

		// Remove the current status
		$row.removeClass( current_status );

		// Add the new status
		$row.addClass( new_status );
	}
};