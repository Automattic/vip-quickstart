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
		global $wpdb;

		$sites = wp_get_sites();

		list( $domain ) = $args;

		$network = wp_get_network( $wpdb->siteid );

		if ( empty( $network ) ) {
			return;
		}

		$old_domain = $network->domain;

		if ( $old_domain == $domain ) {
			return;
		}

		$wpdb->update( $wpdb->site, array( 'domain' => $domain ), array( 'id' => $wpdb->siteid ) );

		update_site_option( 'siteurl', "http://{$domain}/" );
		update_site_option( 'site_name', "{$domain} Sites" );
		update_site_option( 'admin_email', "wordpress@{$domain}" );

		foreach ( wp_get_sites() as $site ) {
			$blog_id = $site['blog_id'];
			switch_to_blog( $blog_id );
			$blog_domain = str_replace( $old_domain, $domain, $site['domain'] );
			$blog_data = array(
					'domain'  => $blog_domain,
					'siteurl' => 'http://' . $blog_domain,
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
			WP_CLI::line( "{$blog_data['domain']} -> {$blog_domain}" );
		}


		if ( file_exists( __DIR__ . '/hosts' ) ) {
			$hosts = file_get_contents( __DIR__ . '/hosts' );
			$hosts = preg_replace('/\s'. preg_quote( $old_domain ) .'\s/', " {$domain} ", $hosts);
			$hosts = preg_replace('/\.'. preg_quote( $old_domain ) .'\s/', ".{$domain} ", $hosts);
			file_put_contents( __DIR__ . '/hosts' , $hosts );
		}

		if ( file_exists( __DIR__ . '/server_hosts' ) ) {
			$hosts = file_get_contents( __DIR__ . '/server_hosts' );
			$hosts = preg_replace('/\s'. preg_quote( $old_domain ) .'\s/', " {$domain} ", $hosts);
			$hosts = preg_replace('/\.'. preg_quote( $old_domain ) .'\s/', ".{$domain} ", $hosts);
			file_put_contents( __DIR__ . '/server_hosts' , $hosts );
		}

		if ( file_exists( '/etc/hosts' ) ) {
			$hosts = file_get_contents( '/etc/hosts' );
			$hosts = preg_replace('/\s'. preg_quote( $old_domain ) .'\s/', " {$domain} ", $hosts);
			$hosts = preg_replace('/\.'. preg_quote( $old_domain ) .'\s/', ".{$domain} ", $hosts);
			file_put_contents( '/etc/hosts' , $hosts );
		}

		WP_CLI::success( "{$old_domain} -> {$domain}" );

	} // function

	/**
	 * @subcommand clean-db
	*/
	public function clean_db($args, $assoc_args) {
		global $wpdb;

		$sql = "select post_type
					from {$wpdb->posts}
					where post_status = 'publish'
					group by post_type
					having count(*) > 500
				";
		$results = $wpdb->get_results( $sql );

		foreach ( $results as $row ) {
			$post_type = $row->post_type;
			$sql = "delete
				from {$wpdb->posts}
				where post_type = '{$post_type}'
				and ID not in (
					select ID
					from (
						select ID
						from {$wpdb->posts}
						where post_status = 'publish'
						and post_type = '{$post_type}'
						order by post_date desc
						limit 500
					) x
				)
				";
			$r = $wpdb->query( $sql );
			printf("removed %d records from post type %s\n", $r, $post_type );
		}

		$wpdb->query( "delete from {$wpdb->posts} where post_status <> 'publish' ");
		$wpdb->query( "delete from {$wpdb->postmeta} where post_id not in ( select ID from {$wpdb->posts} )");
		$wpdb->query( "delete from {$wpdb->comments} where comment_post_ID not in ( select ID from {$wpdb->posts} )");
		$wpdb->query( "delete from {$wpdb->commentmeta} where comment_id not in ( select comment_post_ID from {$wpdb->comments} )");
		$wpdb->query( "delete from {$wpdb->term_relationships} where object_id not in ( select ID from {$wpdb->posts} )");
		$wpdb->query( "delete from {$wpdb->term_taxonomy} where term_taxonomy_id not in ( select term_taxonomy_id from {$wpdb->term_relationships} )");

	}

}

WP_CLI::add_command( 'pmc-site', 'PMC_WP_CLI_Site' );