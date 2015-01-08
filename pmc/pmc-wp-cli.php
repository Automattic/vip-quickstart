<?php

class PMC_WP_CLI_Site extends WP_CLI_Command {

	/**
	 * @subcommand setup
	 * @synopsis --domain=<domain> --theme=<theme> --title=<title>
	*/
	public function setup( $args, $assoc_args ) {

		$domain = $assoc_args['domain'];
		$title  = $assoc_args['title'];
		$theme  = $assoc_args['theme'];

		$details = get_blog_details( array( 'domain' => $domain, 'path' => '/' ) );

		if ( !empty( $details ) ) {
			$blog_id = $details->blog_id;
		} else {
			$user_id = email_exists( 'wordpress@vip.dev' );
			if ( !$user_id ) {
				WP_CLI::error( 'Error locating user with email: wordpress@vip.dev' );
			}
			$blog_id = wpmu_create_blog( $domain, '/', $title, $user_id , array( 'public' => 1 ) );
		}

		if ( empty( $blog_id ) ) {
			WP_CLI::error();
		}

		switch_to_blog( $blog_id );

		if ( $theme != wp_get_theme()->template ) {
			switch_theme( $theme );
		}

		$blog_data = array(
				'domain'  => $domain,
				'siteurl' => 'http://' . $domain,
				'path'    => '/',
			);

		update_blog_details( $blog_id, $blog_data );

		$blog_address = esc_url_raw( $blog_data['siteurl'] );

		if ( get_option( 'siteurl' ) != $blog_address ) {
			update_option( 'siteurl', $blog_address );
		}

		if ( get_option( 'home' ) != $blog_address ) {
			update_option( 'home', $blog_address );
		}

		if ( get_option( 'blogname' ) != $title )  {
			update_option( 'blogname', $title );
		}

		restore_current_blog();

		WP_CLI::success( "{$blog_address}" );
	}

	/**
	 * @subcommand fix
	 * @synopsis <domain> [--title=<title>]
	*/
	public function fix( $args, $assoc_args ) {
		list( $domain ) = $args;
		$domain = strtolower( $domain );
		$title = !empty( $assoc_args['title'] ) ? ucfirst( $assoc_args['title'] ) : '';
		$details = get_blog_details( array( 'domain' => $domain, 'path' => '/' ) );

		if ( empty( $details ) ) {
			WP_CLI::error("Cannot located site {$domain}");
		}

		$blog_id = $details->blog_id;
		switch_to_blog( $blog_id );

		$blog_data = array(
				'domain'  => $domain,
				'siteurl' => 'http://' . $domain,
				'path'    => '/',
			);

		update_blog_details( $blog_id, $blog_data );

		$blog_address = esc_url_raw( $blog_data['siteurl'] );

		if ( get_option( 'siteurl' ) != $blog_address ) {
			update_option( 'siteurl', $blog_address );
		}

		if ( get_option( 'home' ) != $blog_address ) {
			update_option( 'home', $blog_address );
		}

		if ( !empty( $title ) && get_option( 'blogname' ) != $title )  {
			update_option( 'blogname', $title );
		}

		global $wpdb;
		$sql = "delete from `$wpdb->options` where option_name like '_transien%'";
		$wpdb->query( $sql );

		restore_current_blog();

		WP_CLI::success( "{$blog_address}" );
	} // function

	/**
	 * @subcommand set-domain
	 * @synopsis <domain>
	*/
	public function set_domain($args, $assoc_args) {
		$sites = wp_get_sites();

		list( $domain ) = $args;

		$details = get_blog_details(1);

		if ( empty( $details) ) {
			return;
		}

		$old_domain = $details->domain;

		if ( $old_domain == $domain ) {
			return;
		}

		foreach ( wp_get_sites() as $site ) {
			$blog_id = $site['blog_id'];
			switch_to_blog( $blog_id );
			$blog_data = array(
					'domain'  => str_replace( $old_domain, $domain, $site['domain'] ),
					'siteurl' => 'http://' . $domain,
					'path'    => '/',
				);
			$blog_address = esc_url_raw( $blog_data['siteurl'] );

			if ( get_option( 'siteurl' ) != $blog_address ) {
				update_option( 'siteurl', $blog_address );
			}

			if ( get_option( 'home' ) != $blog_address ) {
				update_option( 'home', $blog_address );
			}

			update_blog_details( $blog_id, $blog_data );
			restore_current_blog();

		}

		WP_CLI::success( "{$old_domain} -> {$domain}" );

	} // function

}

WP_CLI::add_command( 'pmc-site', 'PMC_WP_CLI_Site' );