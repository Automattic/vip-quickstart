<?php

/**
 * Class Eventbrite_Suggested_Pages_Setup
 *
 * Creates suggested pages for the eventbrite themes
 *
 * @package eventbrite-parent
 */
if ( !class_exists( 'Eventbrite_Suggested_Pages_Setup' ) ) {
class Eventbrite_Suggested_Pages_Setup {

	/**
	 * Attach internal functions to the appropriate actions
	 */
	public static function init() {
		add_action( 'after_switch_theme', array( __CLASS__, 'create_pages' ) );
	}

	/**
	 * Retrieve an array of the default pages for this theme
	 *
	 * Result is a multi-dimensional array
	 * - 'title' maps to 'post_title'
	 *
	 * Result is filterable using 'eventbrite_default_pages'
	 *
	 * @return array array of default pages and their attributes
	 */
	public static function get_default_pages() {
		$default_pages = array();

		return apply_filters( 'eventbrite_default_pages', $default_pages );
	}

	/**
	 * Ensure the default page for the $item_data exists
	 *
	 * @param array $item_data
	 * @return int id of created/existing page
	 */
	public static function create_default_page( $page_data ) {
		/*
		 * Retrieve or create each page based on it's title.
		 */
		$existing_page = get_page_by_title( $page_data['title'] );

		if ( is_null( $existing_page ) ) {
			$new_page_id = wp_insert_post( array(
				'post_type'     => 'page',
				'post_title'    => $page_data['title'],
				'post_status'   => 'publish'
			) );

			return $new_page_id;

		} else {
			return $existing_page->ID;
		}

	}

	/**
	 * Create default pages
	 */
	public static function create_pages() {
		$pages = self::get_default_pages();

		foreach ( $pages as $page_data ) {
			self::create_default_page( $page_data );
		}
	}
}
}
