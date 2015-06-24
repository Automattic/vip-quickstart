<?php
/**
 * PHP implementation of the Eventbrite API
 *
 * @package eventbrite-api
 * @author  Voce Communications
 */

if ( !class_exists( 'Voce_Eventbrite_API' ) ) {
class Voce_Eventbrite_API {

	const ENDPOINT    = 'https://www.eventbriteapi.com/v3/';

	/**
	 * Sets up the actions used in the admin
	 */
	static function admin_init() {
		add_action( 'keyring_connection_deleted', array( __CLASS__, 'keyring_connection_deleted') );
		add_filter( 'keyring_eventbrite_request_token_params', array( __CLASS__, 'add_connection_referrer' ) );
	}

	/**
	* Append a referrer to the OAuth request made to Eventbrite, giving them an idea of WordPress adoption.
	*/
	public static function add_connection_referrer( $params ) {
		if ( !isset( $params['ref'] ) ) {
			$params['ref'] = 'wpoauth';
		}
		return $params;
	}

	/**
	 * Flush caches and remove the Eventbrite token if the Keyring connection is deleted.
	 */
	public static function keyring_connection_deleted( $service, $request ) {
		if ( 'eventbrite' != $service ) {
			return;
		}

		// Flush caches.
		wp_clear_scheduled_hook( 'sync_eb_data' );
		$flush_method_caches = array(
			'users/me/owned_events',
			'users/me/venues'
		);
		foreach ( $flush_method_caches as $flush_method_cache ) {
			$key = md5( Voce_Eventbrite_API::get_request_unique_string( $flush_method_cache ) );
			call_user_func( array( 'Voce_Eventbrite_API', 'flush_cache' ), $key );
		}

		// Remove token reference.
		delete_blog_option( absint( $request['blog'] ), 'eventbrite_token' );
	}

	/**
	 * Retrieve the Eventbrite keyring service
	 * @return object/null returns the Eventbrite keyring service or null on failure
	 */
	public static function get_service() {
		return Keyring::get_service_by_name( 'eventbrite' );
	}

	/**
	 * Get the Eventbrite tokens
	 * @return array array of tokens
	 */
	public static function get_token() {
		// Check to see if we have a token reference from the original Eventbrite themes or widget.
		$token_ref = get_option( 'eventbrite_token' );

		// If so, grab the ID out of the array.
		if ( is_array( $token_ref ) && ! empty( $token_ref[0] ) ) {
			$token_ref = array_shift( $token_ref );
		}

		// If not, check for the newer v3 API option.
		else {
			$token_ref = get_option( 'eventbrite_api_token' );
		}

		// Bail if we have nothing from either option.
		if ( ! $token_ref ) {
			return false;
		}

		$token = Keyring::init()->get_token_store()->get_token( array( 'type' => 'access', 'id' => absint( $token_ref ) ) );

		if ( ! $token || ! is_a( $token, 'Keyring_Access_Token' ) ) {
			return false;
		}

		// If it's an old Eventbrite v1 external ID, convert it to a v3 external ID.
		if ( $token->meta['external_id'] <= 139973917 ) {
			$token->meta['external_id'] = $token->meta['external_id'] * 1007; // Seriously.
		}

		return $token;
	}

	/**
	 * Retrieve the Eventbrite authentication service
	 * @return object/boolean returns the Eventbrite keyring service or false on failure
	 */
	public static function get_auth_service() {
		$service = self::get_service();
		$token = self::get_token();

		if ( $token ) {
			$service->set_token( $token );
			return $service;
		}
		return false;
	}

	/**
	 * Submits the request to the API
	 * @param string $method api method
	 * @param array $params request parameters
	 * @param boolean $force force a renewal of the cache
	 * @return object/boolean response object is an object when successful or an object/boolean on failure
	 */
	private static function get_auth_request( $method, $params = array(), $force = false ) {
		if ( ! self::get_auth_service() ) {
			return false;
		}

		$request_key = self::get_request_unique_string( $method, $params );

		$transient = tlc_transient( $request_key )
				->updates_with( array( 'Voce_Eventbrite_API', 'make_request' ) , array( $method, $params ) )
				->expires_in( '1200' ) // 20 minutes
				->extend_on_fail( '300' ); // 5 minutes

		if ( $force )
			$transient->fetch_and_cache();

		$response = $transient->get();

		return $response;
	}

	/**
	 * Delete the cache of the specified unique string
	 * @param string $key request unique string
	 */
	public static function flush_cache( $key ) {
		delete_transient( 'tlc__' . $key );
	}

	/**
	 * Makes the request to the API
	 * @param string $method api method
	 * @param array $params request parameters
	 * @return object response object
	 * @throws Exception exception when service is not available or an error occurs when submitting the request
	 */
	public static function make_request( $method, $params ) {
		$url = trailingslashit( self::ENDPOINT . $method );
		$eb  = self::get_auth_service();

		if ( !$eb )
			throw new Exception( __( 'Eventbrite API: Failed to get auth service.', 'eventbrite-parent' ) );

		// Add required expansions.
		$params['expand'] = 'logo,organizer,venue,ticket_classes';
		$url = add_query_arg( $params, $url );

		$response = $eb->request( $url );

		if ( is_a($response, 'Keyring_Error') || isset($response->error) )
			throw new Exception( sprintf( __( 'Eventbrite API: %s', 'eventbrite-parent' ), 'An error occurred.' ) );

		return $response;
	}

	/**
	 * Creates a string to uniquely identify the provided method and parameters
	 * @param string $method api method
	 * @param array $params request parameters
	 * @return string
	 */
	public static function get_request_unique_string( $method, $params = array() ) {
		$unique = 'eventbrite-request-' . $method;
		if ( count($params) ) {
			$unique .= '-' . substr(md5(implode('-', $params)), 0, 5);
		}
		return $unique;
	}

	/**
	 * Get the authenticated user
	 * @param boolean $force force a renewal of the cache
	 * @return boolean
	 */
	public static function get_user( $force = false ) {
		$response = self::get_auth_request( 'users/me/', array(), $force );
		if ( $response && isset($response->user) ) {
			$user = $response->user;
			return $user;
		}
		return false;
	}

	/**
	 * Get a list of the authenticated user's venues
	 * @param boolean $force force a renewal of the cache
	 * @return array array of the user venue objects
	 */
	public static function get_user_venues( $force = false ) {
		$response = self::get_auth_request( 'users/me/venues', array(), $force );
		if ( $response && isset( $response->venues ) ) {
			return $response->venues;
		}
		return array();
	}

	/**
	 * Get a list of the user events
	 *
	 * Parameters
	 * count              - int - number of items to return
	 * per_page           - int - number of items to have on a page
	 * page               - int - current page number
	 * order_by           - string - ordering of the results ( default: start_asc; other values: start_desc, created_asc, created_desc )
	 * include            - array - only return the specified event ids
	 * exclude            - array - do not return the specifed event ids
	 * organizer          - string - only return results from the specified organizer id
	 * venue              - string - only return results from the specified venue id
	 * search             - string - term to search for in event titles
	 * only_public        - boolean - not set by default - overrides global setting set in admin - flag to only show public events in the results
	 *
	 * @param array $params function and api method parameters
	 * @param boolean $force force a renewal of the cache
	 * @return array array of the user event objects
	 */
	public static function get_user_events( $params = array(), $force = false ) {
		$events = array();

		$defaults = array(
			'count'        => -1,
			'per_page'     => 10,
			'page'         => -1,
			'order_by'     => '',
			'include'      => array(),    // include events by id
			'exclude'      => array(),
			'organizer'    => '',
			'venue'        => '',
			'search'       => '',
			'hide_private' => false,
		);
		$params = wp_parse_args( $params , $defaults );
		extract( $params );

		$request_args = array( 'status'  => 'live' );

		$response = self::get_auth_request( 'users/me/owned_events', $request_args, $force );

		if ( $response && isset( $response->events ) ) {

			$events = $response->events;

			/**
			 * The v3 API returns a fixed number of 50 objects, but with pagination information.
			 * Now that we know how many pages of 50 we have, we can append the rest of the
			 * pages (capping it at four pages to avoid madness).
			 */
			$pages = $response->pagination->page_count;
			if ( $pages >= 2 && $pages <= 4 ) {
				$i = 2;
				while ( $i <= $pages ) {
					$request_args['page'] = $i;
					$current_page = self::get_auth_request( 'users/me/owned_events', $request_args, $force );
					if ( is_array( $current_page->events ) ) {
						$events = array_merge( $events, $current_page->events );
					}
					$i++;
				}
			}

			// remove private events if "Hide private events" is checked
			if ( $hide_private ) {
				$events = array_filter( $events, array( new Voce_Eventbrite_Widget_User_Events_Filter($params), 'filter_private' ) );
			}

			// include the following ids
			if ( $include ) {
				$events = array_filter( $events, array( new Voce_Eventbrite_Widget_User_Events_Filter($params), 'filter_included' ) );
			}

            // exclude the following ids
			if ( $exclude ) {
				$events = array_filter( $events, array( new Voce_Eventbrite_Widget_User_Events_Filter($params), 'filter_excluded' ) );
			}

			if ( $venue && $venue !== 'all' ) {
				$events = array_filter( $events, array( new Voce_Eventbrite_Widget_User_Events_Filter($params), 'filter_venue' ) );
			}

			if ( $organizer && $organizer !== 'all' ) {
				$events = array_filter( $events, array( new Voce_Eventbrite_Widget_User_Events_Filter($params), 'filter_organizer' ) );
			}

			// allow the event titles to be searched
			if ( ! empty( $search ) ) {
				$search  = stripslashes( $search );
				$matched = array();
				foreach ( $events as $event ) {
					if ( isset( $event->name->text ) && false !== strpos( strtolower( $event->name->text ), strtolower( $search ) ) ) {
						$matched[] = $event;
					}
				}
				$events = $matched;
			}

			// pagination
			if ( $page > 0 ) {
				$events = array_slice( $events, ( $page - 1 ) * $per_page, $per_page );
			} else {
				// return the specified number
				if ( $count > 0 ) {
					$events = array_slice( $events, 0, $count );
				}
			}
		}

		return $events;
	}

	/**
	 * Callback to sort events by start date
	 * @param object $event_a
	 * @param object $event_b
	 * @return boolean
	 */
	static function event_start_date_sort_cb( $event_a, $event_b ) {
		return $event_a->start->local > $event_b->start->local;
	}

	/**
	 * Get the authenticated user's events for the specified venue
	 *
	 * See get_user_events for parameter declarations
	 *
	 * @param int/string $venue_id id of the venue
	 * @param array $params function and api method parameters
	 * @param boolean $force force a renewal of the cache
	 * @return array array of the venue's event objects
	 */
	public static function get_venue_events( $venue_id, $params = array(), $force = false ) {
		$defaults = array(
			'count'    => -1,
			'per_page' => 10,
			'page'     => -1,
			'order_by'  => '',
			'include'  => array(),
			'exclude'  => array(),
		);
		$params = wp_parse_args( $params , $defaults );
		extract( $params );
		$events = self::get_user_events( $params, $force );
		$events = array_filter($events, array( new Eventbrite_Services_User_Events_Filter(array('venue_id' => $venue_id)), 'filter_venue_ID' ) );
		return $events;
	}

	/**
	 * Get the venue for the authenticated user's specified venue id
	 * @param int/string $venue_id id of the venue
	 * @return object/boolean Eventbrite venue or false when does not exists
	 */
	public static function get_venue( $venue_id ) {
		$user_venues = self::get_user_venues();
		foreach ( $user_venues as $venue ) {
			if ( $venue->venue->id == $venue_id )
				return $venue->venue;
		}
		return false;
	}

	/**
	 * Retrieve the authorized user's organizers
	 * @param boolean $force force the retrieval of the organizers
	 * @return array array of organizers
	 */
	public static function get_user_organizers( $force = false ) {
		$response = self::get_auth_request( 'users/me/organizers', array(), $force );
		if ( $response && isset( $response->organizers ) ) {
			return $response->organizers;
		}
		return array();
	}

	/**
	 * Get the featured event ids
	 * @return array array of event ids
	 */
    public static function get_featured_event_ids() {
        $ids = maybe_unserialize(
            Voce_Settings_API::GetInstance()->get_setting(
                'featured-event-ids',
                Eventbrite_Settings::eventbrite_group_key(),
                array()
            )
        );

        // If we have no featured IDs, return an empty array
        if ( empty( $ids ) ) {
			return array();
        }

        // Return a simple array of IDs
        foreach ( $ids as $id ) {
			$output[] = $id[ 'id' ];
        }

        return $output;
    }
}
}
add_action( 'admin_init', array( 'Voce_Eventbrite_API', 'admin_init' ) );

/**
 * Gets the venue information from the Venue setting in the admin
 * @return object/boolean venue info or false when does not exist
 */
function eventbrite_services_get_venue_info() {
	$venue = false;
	$venue_id = eventbrite_services_get_setting( 'venue-id' );
	if ( $venue_id )
		$venue = Voce_Eventbrite_API::get_venue( $venue_id );

	return $venue;
}

/**
 * Gets the events that have been featured in the admin
 * @param array $args function and api method parameters
 * @return array array of events
 */
function eventbrite_services_get_featured_events( $args = array() ) {
	$events = array();

	$featured_event_ids = Voce_Eventbrite_API::get_featured_event_ids();

	if ( !empty($featured_event_ids) ) {
		$args[ 'hide_private' ] = eventbrite_services_get_setting( 'only-public-events' );
		$args[ 'include' ] = $featured_event_ids;
		$events = Voce_Eventbrite_API::get_user_events( $args );
		// re-index array
		$events = array_values( $events );
	}

	return $events;
}

/**
 * Gets events that aren't featured
 *
 * @param array $args function and api method parameters
 * @return array array of events
 */
function eventbrite_services_get_non_featured_events( $args = array() ) {
    $events = array();

	$featured_event_ids = Voce_Eventbrite_API::get_featured_event_ids();
	if ( !empty($featured_event_ids) ) {
		$args['exclude'] = $featured_event_ids;
	}

	$events = Voce_Eventbrite_API::get_user_events( $args );

	return $events;
}

/**
 * Creates the ticket widget iframe
 * @param id/string $event_id event id
 * @param string $height css format of height
 * @param string $width css format of width
 */
function eventbrite_services_print_ticket_widget( $event_id, $height='350px', $width='100%' ) {
	$src = add_query_arg( array(
			'eid' => $event_id,
			'ref' => 'etckt',
	), '//eventbrite.com/tickets-external' );
	?>
        <div class="iframe-wrap eventbrite-widget" style="width:100%; text-align:left;" >
			<iframe src="<?php echo esc_url($src); ?>" height="<?php echo esc_attr($height); ?>" width="<?php echo esc_attr($width); ?>" frameborder="0" vspace="0" hspace="0" marginheight="5" marginwidth="5" scrolling="auto" allowtransparency="true"></iframe>
		</div>
	<?php
}

/**
 * Get an event object from the given event id and optional occurrence number
 *
 * @param int $event_id
 * @return object event
 */
function eventbrite_services_get_event_by_id( $event_id ) {

    $args['include'] = array( $event_id );

	if ( ! $events = Voce_Eventbrite_API::get_user_events( $args ) ) {
        return false;
    }

	return array_shift( $events );
}

/**
 * Get the call-to-action button text ("Ticket Purchase Link/Button Text" on the Eventbrite settings page)
 *
 * @return string Button text or null
 */
function eventbrite_services_get_call_to_action() {
	return Voce_Settings_API::GetInstance()->get_setting( 'call-to-action', Eventbrite_Settings::eventbrite_group_key() );
}

/**
 * Class to filter events, used as a workaround to make array_filter calls with
 * additional arguments while avoiding using closures to allow PHP < 5.3 compatibility
 */
class Eventbrite_Services_User_Events_Filter {

	private $args;

	function __construct( $args ) {
        $this->args = $args;
    }

    function filter_included( $event ) {
		return in_array( $event->id, $this->args['include'] );
	}

	function filter_excluded( $event ) {
		return !in_array( $event->id, $this->args['exclude'] );
	}

	function filter_venue( $event ) {
		// handles case when no venue is specified for an event ( online events )
		if ( isset( $event->venue ) ) {
			return $event->venue->id == $this->args['venue'];
		} elseif ( $this->args['venue'] === 'online' && !isset( $event->venue ) ) {
			return true;
		} else {
			return false;
		}
	}

	function filter_organizer( $event ) {
		if ( isset( $event->organizer->id ) ) {
			return $event->organizer->id == $this->args['organizer'];
		} else {
			return false;
		}
	}

	function order_events( $a, $b ) {
		if ( $this->args['order'] == 'asc' )
			return ( strtotime( $a->created ) > strtotime( $b->created ) );
		else
			return ( strtotime( $a->created ) < strtotime( $b->created ) );
	}

	function filter_venue_ID( $event ) {
		// handles case when no venue is specified for an event ( online events )
		if ( isset( $event->venue ) )
			return $event->venue->id == $this->args['venue_id'];
		elseif ( $this->args['venue_id'] === 'online' && !isset( $event->venue ) )
			return true;
		else
			return false;
	}

	function filter_events_after_now( $event ) {
		return current_time( 'timestamp' ) <= strtotime( $event->end->local );
	}

	function filter_private( $event ) {
		if ( isset( $event->listed ) ) {
			return $event->listed;
		} else {
			return false;
		}
	}
}
