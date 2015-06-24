<?php
/**
 * Eventbrite theme settings
 *
 * @package eventbrite-parent
 * @author  Voce Communications
 */

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'Eventbrite_Settings' ) ) {
class Eventbrite_Settings {

	/**
	 * Setup the Eventbrite Settings
	 */
	public static function init() {

		// must have classes
		if ( ! class_exists( 'Voce_Eventbrite_API' ) )
			return;

		if ( ! class_exists( 'Voce_Settings_API' ) )
			return;

		// initial settings
		$theme_option = Voce_Settings_API::GetInstance()->add_page( __( 'Theme Options', 'eventbrite-parent' ), __( 'Theme Options', 'eventbrite-parent' ), 'theme-options', 'edit_theme_options', '' )
			->add_group( '', self::eventbrite_group_key() )
			->add_setting( '<h3 id="eb-settings">' . __( 'Eventbrite Authentication', 'eventbrite-parent' ) . '</h3>', 'eventbrite-theme-settings', array(
				'display_callback' => array( __CLASS__, 'section_cb' )
			))->group
			->add_setting( __( 'Authentication Status', 'eventbrite-parent' ), 'authentication-status', array(
				'display_callback' => array( __CLASS__, 'authentication_status_cb' )
			));

		// if authenticated, display the rest of the Eventbrite settings
		if ( Voce_Eventbrite_API::get_auth_service() ) {
			$theme_option->group
				->add_setting( '<h3>' . __( 'Eventbrite Display Settings', 'eventbrite-parent' ) . '</h3>', 'eventbrite-account-settings', array(
				'display_callback' => array( __CLASS__, 'section_cb' )
				))->group
				->add_setting( __( 'Show Events', 'eventbrite-parent' ), 'show-events-by', array(
					'default_value'      => '',
					'display_callback'   => array( __CLASS__, 'show_events_by_cb' ),
					'sanitize_callbacks' => array( array( 'Eventbrite_Settings', 'sanitize_show_events_by' ) )
				))->group
				->add_setting( __( 'Featured Events', 'eventbrite-parent' ), 'featured-event-ids', array(
					'default_value'      => array(),
					'display_callback'   => array( __CLASS__, 'featured_event_selection_cb' ),
					'sanitize_callbacks' => array( array( 'Eventbrite_Settings', 'sanitize_featured_events' ) )
				))->group
				->add_setting( __( 'Ticket Purchase Link/Button Text', 'eventbrite-parent' ), 'call-to-action', array(
					'description'        => __( 'Language to use for the call to action to buy a ticket/register for an event.', 'eventbrite-parent' ),
					'default_value'      => __( 'Register', 'eventbrite-parent' ),
					'display_callback'   => array( __CLASS__, 'cta_text_selection_display_cb' ),
					'sanitize_callbacks' => array( array( __CLASS__, 'cta_text_selection_sanitize_cb' ) ),
					'options'            => array( __( 'Register', 'eventbrite-parent' ), __( 'Buy Tickets', 'eventbrite-parent' ) )
				))->group
				->add_setting( __( 'Only Display Public Events', 'eventbrite-parent' ), 'only-public-events', array(
					'description'        => __( 'Only allow public events to be displayed throughout the site.', 'eventbrite-parent' ),
					'default_value'      => false,
					'display_callback'   => array( __CLASS__, 'only_public_events_display_cb'),
					'sanitize_callbacks' => array( array( __CLASS__, 'only_public_events_sanitize_cb' ) )
				));
		}

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Voce Settings API callback used to create a section
	 * @return type
	 */
	static function section_cb() {
		return;
	}

	/**
	 * Used to get the key that is used by the eventbrite settings page ( Theme Options )
	 * @return string
	 */
	static function eventbrite_group_key( $theme_slug = false ) {
		$theme_slug = ( $theme_slug ) ? $theme_slug : get_option( 'stylesheet' );
		return sprintf( 'theme-options_%s', $theme_slug );
	}

	/**
	 * Setup the admin side of the Eventbrite Settings
	 */
	static function admin_init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_setting_scripts' ) );
		add_action( 'wp_ajax_organizer_selected', array( __CLASS__, 'ajax_organizer_selected' ) );
		add_action( 'wp_ajax_venue_selected', array( __CLASS__, 'ajax_venue_selected' ) );
		// clear options when switching themes
		add_action( 'switch_theme', array( __CLASS__, 'clear_settings' ) );
		// remove user settings when removing authentication
		add_action( 'keyring_connection_deleted', array( __CLASS__, 'clear_user_settings' ) );
	}

	/*
	* Clears stored user information
	*/
	static function clear_user_settings( $service ) {
		if ( 'eventbrite' == $service ) {

			$remove_settings = array(
				'organizer-id',
				'featured-event-ids'
			);

			$group_key = Eventbrite_Settings::eventbrite_group_key();
			$eb_settings = get_option( $group_key );
			if ( $eb_settings ) {
				foreach ( $remove_settings as $remove_setting ) {
					unset( $eb_settings[$remove_setting] );
				}
				update_option( $group_key, $eb_settings );
			}
		}
	}

	/*
	* Remove stored settings
	*/
	static function clear_settings() {
		$theme_slug  = get_option( 'theme_switched' );
		if ( $theme_slug ) {
			$eb_settings = Eventbrite_Settings::eventbrite_group_key( $theme_slug );
			if ( $eb_settings )
				delete_option( $eb_settings );
		}
	}

	/*
	* Enqueue scripts for settings
	*/
	static function enqueue_setting_scripts() {
		if ( ! isset( $_GET['page'] ) || 'theme-options-page' != $_GET['page'] )
			return;

		wp_enqueue_script( 'eventbrite-settings', content_url( '/lib/eventbrite-services/eventbrite-settings/js/eventbrite-settings.js' ), array( 'jquery' ), '20140408' );
		wp_enqueue_style(  'eventbrite-settings', content_url( '/lib/eventbrite-services/eventbrite-settings/css/eventbrite-settings.css' ), array(), '20140408' );
	}

	/**
	 * Callback to display the Eventbrite authentication status
	 */
	static function authentication_status_cb() {
		$auth_service = Voce_Eventbrite_API::get_auth_service();

		if ( $auth_service ) {
			$kr_nonce = wp_create_nonce( 'keyring-delete' );
			$delete_nonce = wp_create_nonce( 'keyring-delete-' . $auth_service->get_name() . '-' . $auth_service->get_token()->get_uniq_id() );

			$name = '';
			if ( $auth_service->get_token()->get_meta( 'external_display' ) ) {
				$name = $auth_service->get_token()->get_meta( 'external_display' );
			}

			if ( ! empty( $name ) )
				printf( __( 'Connected as %1$s | ', 'eventbrite-parent' ), esc_html( $name ) );

			echo '<a href="' . esc_url( Keyring_Util::admin_url( false, array( 'action' => 'delete', 'service' => $auth_service->get_name(), 'token' => $auth_service->get_token()->get_uniq_id(), 'kr_nonce' => $kr_nonce, 'nonce' => $delete_nonce ) ) ) . '" title="' . esc_attr( _x( 'Delete', 'keyring', 'eventbrite-parent' ) ) . '" class="delete">'. __( 'Remove', 'eventbrite-parent' ) . '</a>';
		} else {
			$service = Voce_Eventbrite_API::get_service();
			$request_kr_nonce = wp_create_nonce( 'keyring-request' );
			$request_nonce = wp_create_nonce( 'keyring-request-' . $service->get_name() );
			echo '<a id="eb-authenticate" href="' . esc_url( Keyring_Util::admin_url( $service->get_name(), array( 'blog' => get_current_blog_id(), 'for' => 'eventbrite', 'action' => 'request', 'kr_nonce' => $request_kr_nonce, 'nonce' => $request_nonce ) ) ) . '">' . __( 'Connect with ', 'eventbrite-parent' ) . esc_html( $service->get_label() ) . '</a>';
		}
	}

	/**
	 * Callback to display the options to filter the events
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 */
	static function show_events_by_cb( $value, $setting, $args ) {

		$organizer_value = isset( $value['organizer-id'] ) ? $value['organizer-id'] : 'all';
		$venue_value     = isset( $value['venue-id'] ) ? $value['venue-id'] : 'all';
		?>
		<div id="show-events-by" class="show-events-by">
			<?php _e( 'Organized by', 'eventbrite-parent' ); ?>
			<?php self::organizer_selection_cb( $organizer_value, $setting, $args );  ?>
			<?php _e( 'at', 'eventbrite-parent' ); ?>
			<?php self::venue_selection_cb( $venue_value, $setting, $args ); ?>
		</div>
		<?php
	}

	/**
	 * Callback to sanitize, when saving, the options to filter the events
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 * @return type
	 */
	static function sanitize_show_events_by( $value, $setting, $args ) {
		$value['organizer-id'] = self::sanitize_organizer_id( $value['organizer-id'], $setting, $args );
		$value['venue-id']     = self::sanitize_venue_id( $value['venue-id'], $setting, $args );

		return $value;
	}

	/**
	 * Callback to display the organizer options
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 */
	static function organizer_selection_cb( $value, $setting, $args ) {
		$organizers = Voce_Eventbrite_API::get_user_organizers( true );
		?>
		<div id="organizer-selection">
			<?php wp_nonce_field( 'organizer-selection', 'organizer-selection_nonce' ); ?>
			<input type="hidden" name="original-organizer-id" id="original-organizer-id" value="<?php echo esc_attr( $value ); ?>" />
			<legend class="screen-reader-text"><span><?php _e( 'Organizer', 'eventbrite-parent' ); ?></span></legend>
			<select name="<?php echo esc_attr( $setting->get_field_name() . '[organizer-id]' ); ?>">
				<?php self::organizer_selection_item( (object) array( 'name' => _x( 'All Organizers', 'eventbrite settings', 'eventbrite-parent' ), 'id' => 'all' ), $setting, $value ); ?>
				<?php foreach ( $organizers as $organizer ) : ?>
					<?php self::organizer_selection_item( $organizer, $setting, $value ); ?>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Helper function to display individual organizers
	 * @param type $organizer
	 * @param type $setting
	 * @param type $selected
	 */
	static function organizer_selection_item( $organizer, $setting, $selected ) {
		?>
			<option id="<?php echo esc_attr( $setting->get_field_id() . '-' . $organizer->id . '-organizer' ); ?>" value="<?php echo esc_attr( $organizer->id ); ?>" <?php selected( $selected, $organizer->id ); ?>><?php echo esc_attr( $organizer->name );?></option>
		<?php
	}

	/**
	 * AJAX callback for updating featured events when changing the organizer
	 */
	static function ajax_organizer_selected() {
		$organizer_id = isset( $_REQUEST['organizer_id'] ) ? intval( $_REQUEST['organizer_id'] ) : false;
		$field_name   = isset( $_REQUEST['field_name'] ) ? sanitize_text_field( $_REQUEST['field_name'] ) : '';
		$field_id     = isset( $_REQUEST['field_id'] ) ? sanitize_text_field( $_REQUEST['field_id'] ) : '';
		if ( $organizer_id && check_admin_referer( 'organizer-selection', 'organizer_selection_nonce' ) )
			self::featured_event_items( $organizer_id, '', $field_name, $field_id );

		die();
	}

	/**
	 * Helper function sanitize the organizer id when saving
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 * @return type
	 */
	static function sanitize_organizer_id( $value, $setting, $args ) {
		$original_organizer_id = ( $_POST['original-organizer-id'] ) ? $_POST['original-organizer-id'] : 0;

		if ( ( $original_organizer_id && $value ) && ( $original_organizer_id !== $value ) )
			add_filter( 'eventbrite-settings_update_organizer', '__return_true' );

		return $value;
	}

	/**
	 * Callback to display the venue options
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 */
	static function venue_selection_cb( $value, $setting, $args ) {
		$venues = Voce_Eventbrite_API::get_user_venues( true );
		?>
		<div id="venue-selection">
			<?php wp_nonce_field( 'venue-selection', 'venue-selection_nonce' ); ?>
			<input type="hidden" name="original-venue-id" id="original-venue-id" value="<?php echo esc_attr( $value ); ?>" />
			<legend class="screen-reader-text"><span><?php _e( 'Venue', 'eventbrite-parent' ); ?></span></legend>
			<select name="<?php echo esc_attr( $setting->get_field_name() . '[venue-id]' ); ?>">
				<?php self::venue_selection_item( (object) array( 'name' => _x( 'All Locations', 'eventbrite setting', 'eventbrite-parent' ), 'id' => 'all' ), $setting, $value ); ?>
				<?php self::venue_selection_item( (object) array( 'name' => _x( 'Online', 'eventbrite setting', 'eventbrite-parent' ), 'id' => 'online' ), $setting, $value ); ?>
				<?php foreach ( $venues as $venue ) : ?>
					<?php self::venue_selection_item( $venue, $setting, $value ); ?>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Helper function to display an individual helper option
	 * @param type $venue
	 * @param type $setting
	 * @param type $selected
	 */
	static function venue_selection_item( $venue, $setting, $selected ) {
		?>
			<option id="<?php echo esc_attr( $setting->get_field_id() . '-' . $venue->id . '-venue' ); ?>" value="<?php echo esc_attr( $venue->id ); ?>" <?php selected( $selected, $venue->id ); ?>><?php echo esc_attr( $venue->name );?></option>
		<?php
	}

	/**
	 * AJAX callback to update the events when selecting a venue
	 */
	static function ajax_venue_selected() {
		$organizer_id = isset( $_REQUEST['organizer_id'] ) ? intval( $_REQUEST['organizer_id'] ) : false;
		$venue_id     = isset( $_REQUEST['venue_id'] ) ? intval( $_REQUEST['venue_id'] ) : false;
		$field_name   = isset( $_REQUEST['field_name'] ) ? sanitize_text_field( $_REQUEST['field_name'] ) : '';
		$field_id     = isset( $_REQUEST['field_id'] ) ? sanitize_text_field( $_REQUEST['field_id'] ) : '';
		if ( $venue_id && check_admin_referer( 'venue-selection', 'venue_selection_nonce' ) )
			self::featured_event_items( $organizer_id, $venue_id, $field_name, $field_id );

		die();
	}

	/**
	 * Helper function to sanitize the venue id when saving
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 * @return type
	 */
	static function sanitize_venue_id( $value, $setting, $args ) {
		$original_venue_id = ( $_POST['original-venue-id'] ) ? $_POST['original-venue-id'] : 0;

		if ( ( $original_venue_id && $value ) && ( $original_venue_id !== $value ) )
			add_filter( 'eventbrite-settings_update_venue', '__return_true' );

		return $value;
	}

	/**
	 * Callback to display the events to select to be featured
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 */
	static function featured_event_selection_cb( $value, $setting, $args ) {
		$organizer_id = eventbrite_services_get_setting( 'organizer-id', 'all' );
		$venue_id     = eventbrite_services_get_setting( 'venue-id', 'all' );
		$field_name   = $setting->get_field_name();
		$field_id     = $setting->get_field_id();
		?>
		<div id="featured-event-checklist-wrap">
			<div id="featured-event-checklist" data-field-name="<?php echo esc_attr( $field_name ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>">
				<?php self::featured_event_items( $organizer_id, $venue_id, $field_name, $field_id, $value ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Helper function to display the event items, used with the ajax update as well
	 * @param type $organizer_id
	 * @param type $venue_id
	 * @param type $field_name
	 * @param type $field_id
	 * @param type $selected
	 * @return type
	 */
	static function featured_event_items( $organizer_id, $venue_id, $field_name, $field_id, $selected = array() ) {
		if ( ! $organizer_id )
			return;

		if ( ! is_array( $selected ) )
			$selected = array();

		// allows the specification of selecting only a single featured event
		$single_event = apply_filters( 'eventbrite-settings_single-featured-event', false );

		$featured_event_lt = new Featured_Event_List_Table( $organizer_id, $venue_id, $field_name, $field_id, $selected, $single_event, array( 'ajax' => true, 'screen' => 'theme-options' ) );
		$featured_event_lt->prepare_items();
		$featured_event_lt->display();
	}

	/**
	 * Callback to sanitize the selected featured events when saving
	 * @param type $value
	 * @param type $setting
	 * @param type $args
	 * @return type
	 */
	static function sanitize_featured_events( $value, $setting, $args ) {
		if ( ! is_array( $value ) )
			$value = array();

		$updated_organizer = apply_filters( 'eventbrite-settings_update_organizer', false );
		$updated_venue     = apply_filters( 'eventbrite-settings_update_venue', false );

		// only merge featured when the venue hasn't been updated
		if ( ! $updated_organizer || ! $updated_venue ) {
			// get the events on the page
			$event_ids = ( isset( $_POST['page-event-ids'] ) ) ? $_POST['page-event-ids'] : array();

			// get previously featured events
			$previous_featured = Voce_Settings_API::GetInstance()->get_setting( 'featured-event-ids', self::eventbrite_group_key(), array() );

			// separate out not-featured events on the page
			$not_featured = array_diff( array_keys( $event_ids ), $value );

			// filter out the not featured event
			$still_featured = array_diff( array_keys( $previous_featured ), $not_featured );

			// merge in the new featured
			$value = array_unique( array_merge( $still_featured, $value ) );

			// get full featured event objects
			$value = array_intersect_key( $event_ids, array_flip( $value ) );
		}

		return $value;
	}

	/**
	 * Print call to action setting
	 */
	static function cta_text_selection_display_cb( $value, $setting, $args ) {
		if ( ! empty( $args['options'] ) && is_array( $args['options'] ) ) {
			if ( ! ( $value && in_array( $value, $args['options'] ) ) && ! empty( $args['default_value'] ) )
				$value = $args['default_value'];

			foreach ( $args['options'] as $label ) {
				$key = str_replace( ' ', '-', strtolower( $label ) );
				?>
					<input <?php checked( $value, $label, true ); ?> id="<?php echo esc_attr( $setting->get_field_id() . '-' . $key ); ?>" name="<?php echo esc_attr( $setting->get_field_name() ); ?>" type="radio" value="<?php echo esc_attr( $label ); ?>" />
					<label for="<?php echo esc_attr( $setting->get_field_id() . '-' . $key ); ?>"><?php echo esc_html( $label ); ?></label><br/>
				<?php
			}
		}
	}

	/**
	 * Sanitize and validate call to action setting on save
	 */
	static function cta_text_selection_sanitize_cb( $value, $setting, $args ) {
		$value = sanitize_text_field( $value );
		if ( isset( $args['options'] ) && in_array( $value, $args['options'] ) )
			return $value;

		return false;
	}

	/**
	 * Output "Only Public Events" checkbox
	 */
	static function only_public_events_display_cb( $value, $setting, $args ) {
		$value = in_array($value, array('on', true), true);
		?>
		<input type="checkbox" id="<?php echo esc_attr( $setting->get_field_id() ); ?>" name="<?php echo esc_attr( $setting->get_field_name() ) ?>" <?php checked( $value ) ?> />
		<?php if(!empty($args['description'])) : ?>
			<br/><span class="description"><?php echo wp_kses_post( $args['description'] ); ?></span>
		<?php endif;
	}

	/**
	 * Sanitize and validate public events setting on save
	 */
	static function only_public_events_sanitize_cb( $value, $setting, $args ) {
		return !is_null( $value );
	}
}
}
add_action( 'init', Eventbrite_Settings::init() );

/**
 * Extended WP_List_Table to implement a list of events that can be featured
 */
if ( ! class_exists( 'Featured_Event_List_Table' ) ) {
class Featured_Event_List_Table extends WP_List_Table {

	var $organizer_id = 0;
	var $venue_id     = 0;
	var $field_name   = '';
	var $field_id     = '';
	var $selected     = array();
	var $single_event = false;

	/**
	 * Create the list table
	 * @param type $organizer_id
	 * @param type $venue_id
	 * @param type $field_name
	 * @param type $field_id
	 * @param type $selected
	 * @param type $single_event
	 * @param type $list_table_args
	 */
	function __construct( $organizer_id, $venue_id, $field_name, $field_id, $selected, $single_event, $list_table_args = array() ) {
		$this->organizer_id = $organizer_id;
		$this->venue_id     = $venue_id;
		$this->field_name   = $field_name;
		$this->field_id     = $field_id;
		$this->selected     = $selected;
		$this->single_event = $single_event;
		parent::__construct( $list_table_args );
	}

	/**
	 * Specify the columns
	 * @return string
	 */
	function get_columns() {
		$columns = array(
			'cb'         => '',
			'event_name' => _x( 'Event Name', 'eventbrite setting', 'eventbrite-parent' ),
			'event_date' => _x( 'Event Date', 'eventbrite setting', 'eventbrite-parent' ),
			'event_id'   => _x( 'Event ID', 'eventbrite setting', 'eventbrite-parent' )
		);
		return $columns;
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @return array
	 */
	function get_column_info() {
		list( $columns, $hidden, $sortable ) = parent::get_column_info();
		if ( ! empty( $columns['event_id'] ) ) {
			$hidden[] = 'event_id';
		}
		return array( $columns, $hidden, $sortable );
	}

	/**
	 * Obtain items to be used in the table
	 */
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable);

		$args = array();

		if ( $this->organizer_id ) $args['organizer'] = $this->organizer_id;
		if ( $this->venue_id ) $args['venue'] = $this->venue_id;

		$venue_events = Voce_Eventbrite_API::get_user_events( $args, true );

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count( $venue_events );

		$this->items = array_slice( $venue_events, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
	}

	/**
	 * Specify each columns default value
	 * @param type $item
	 * @param type $column_name
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'event_id':
				return $item->id . '-0';
			case 'event_date':
				return mysql2date( get_option( 'date_format' ), $item->start->local );
			case 'event_name':
				return $item->name->text;
			default:
				return '';
		}
	}

	/**
	 * Create a custom column_cb so that a radio box may be utilized
	 * @param type $item
	 * @return type
	 */
	function column_cb( $item ) {
		$id         = (string) $item->id;
		$key        = $item->id . '-0';

		if ( $this->single_event )
			$type = 'radio';
		else
			$type = 'checkbox';

		return $output = sprintf( '<input type="%5$s" name="%1$s" id="%2$s" %3$s value="%4$s" />', esc_attr( $this->field_name . '[]' ), esc_attr( $this->field_id . '-' . $key ), checked( in_array( $key, array_keys( $this->selected ) ), true, false ), esc_attr( $key ), $type );
	}

	/**
	 * Callback for displaying the event id to be used when saving
	 * @param type $item
	 * @return type
	 */
	function column_event_id( $item ) {
		$id         = (string) $item->id;
		$key        = $item->id . '-0';
		$output     = sprintf( '<input type="hidden" name="page-event-ids[%1$s][id]" value="%2$s" />', esc_attr( $key ), esc_attr( $id ) );
		return $output;
	}

	/**
	 * Overriding parent to remove previously declared nonce
	 * @param type $which
	 */
	function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions">
				<?php $this->bulk_actions(); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}
}
}

/**
 * Wrapper around the Voce Settings API get_setting function so that Eventbrite specific logic can
 * be implemented, for the Eventbrite themes, this template tag should be utilized when obtaining
 * Eventbrite data vs direct access to the Voce Settings API.
 * @param type $key
 * @param type $default
 * @return type
 */
function eventbrite_services_get_setting( $key, $default = false ) {
	if ( ! class_exists( 'Voce_Settings_API' ) ) {
		return false;
	}

	$show_events_by = array(
		'organizer-id',
		'venue-id'
	);

	if ( in_array( $key, $show_events_by ) ) {
		$show_events_by_settings = Voce_Settings_API::GetInstance()->get_setting( 'show-events-by', Eventbrite_Settings::eventbrite_group_key(), array() );
		return ( ( isset( $show_events_by_settings[ $key ] ) ) ? $show_events_by_settings[ $key ] : $default );
	} else {
		return Voce_Settings_API::GetInstance()->get_setting( $key, Eventbrite_Settings::eventbrite_group_key(), $default );
	}
}
