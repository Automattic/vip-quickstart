<?php

/**
 * Implement wp cli command: pmc-site
 */

if ( defined('WP_CLI') && WP_CLI ) {

	class PMC_WP_CLI_Site extends WP_CLI_Command {

		/**
		 * Import users from csv file and remove any existing users that is not.
		 * @subcommand import-users
		 * @synopsis <csv-file>
		*/
		public function import_users( $args, $assoc_args ) {
			global $wpdb;
			$file = $args[0];
			if ( ! $fh = fopen( $file, 'r' ) ) {
				return;
			}

			$users = array();
			if ( $headers = fgetcsv($fh) ) {
				while( $row = fgetcsv($fh) ) {
					if ( count( $headers ) != count( $row ) ) {
						continue;
					}
					$users[] = array_combine( $headers, $row );
				}
			}

			fclose($fh);

			if ( empty( $users ) ) {
				return;
			}

			$users = wp_list_pluck( $users, 'user_login' );
			$blogs = $wpdb->get_results( "SELECT * FROM $wpdb->blogs");

			$existing_users = get_users( array( 'blog_id' => 1 ) );

			foreach ( $existing_users as $user ) {

				// don't remove super admin
				if ( $user->ID == 1 || is_super_admin( $user->ID ) ) {
					continue;
				}

				if ( !in_array( $user->user_login, $users ) ) {
					foreach ( $blogs as $blog ) {
						remove_user_from_blog( $user->ID, $blog->blog_id, 1);
						printf("Remove user %s from %s\n", $user->user_login, $blog->domain );
					}
					printf("Delete user %s\n", $user->user_login );
					wpmu_delete_user( $user->ID );
				}
			}

			switch_to_blog(1);
			add_filter( 'send_password_change_email', '__return_false' );
			\WP_CLI\Utils\load_command('user');
			$user = new User_Command();
			$user->import_csv( $args, $assoc_args );
			$this->add_network_users();
		}

		/**
		 * @subcommand add-network-users
		*/
		public function add_network_users() {
			global $wpdb;
			$users = get_users( array( 'blog_id' => 1 ) );
			$blogs = $wpdb->get_results( "SELECT * FROM $wpdb->blogs where blog_id <> 1");
			foreach ( $users as $user ) {
				if ( empty( $user ) ) {
					continue;
				}
				foreach($blogs as $blog) {
					if ( empty( $blog ) ) {
						continue;
					}
					foreach ( $user->roles as $role ) {
						remove_user_from_blog( $user->ID, $blog->blog_id, 1);
						add_user_to_blog( $blog->blog_id, $user->ID, $role );
					}
					printf("add user %s to %s\n",$user->user_login,$blog->domain);
				}
			}
		}

		/**
		 * @subcommand setup
		 * @synopsis --domain=<domain> --theme=<theme> --title=<title> [--home=<home>]
		*/
		public function setup( $args, $assoc_args ) {

			$domain = strtolower( $assoc_args['domain'] );
			$title  = $assoc_args['title'];
			$theme  = $assoc_args['theme'];
			$home   = strtolower( $assoc_args['home'] );

			$details = get_blog_details( array( 'domain' => $domain, 'path' => '/' ) );

			if ( !empty( $details ) ) {
				$blog_id = $details->blog_id;
			} else {
				if ( ! ( $user_id = email_exists( 'wordpress@vip.dev' ) ) ) {
					$user_id = username_exists('wordpress');
				}
				if ( !$user_id ) {
					WP_CLI::error( 'Error locating wordpress user' );
				}
				$blog_id = wpmu_create_blog( $domain, '/', $title, $user_id , array( 'public' => 1 ) );
			}

			if ( empty( $blog_id ) ) {
				WP_CLI::error('Invalid blog_id');
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
			if ( empty( $home ) ) {
				$home = $blog_address;
			} elseif ( 'http' != substr($home,0,4) ) {
				$home = "http://" . $home;
			}

			if ( get_option( 'siteurl' ) != $blog_address ) {
				update_option( 'siteurl', $blog_address );
			}

			if ( get_option( 'home' ) != $home ) {
				update_option( 'home', $home );
			}

			if ( get_option( 'blogname' ) != $title )  {
				update_option( 'blogname', $title );
			}

			$users = get_users( array( 'blog_id' => 1 ) );
			foreach ( $users as $user ) {
				foreach ( $user->roles as $role ) {
					add_user_to_blog( $blog_id, $user->ID, $role );
				}
				printf("add user %s to %s\n",$user->user_login,$domain);
			}

			restore_current_blog();

			WP_CLI::success( "{$blog_address}" );
		}

		/**
		 * @subcommand fix
		 * @synopsis <domain> [--title=<title>]  [--home=<home>]
		*/
		public function fix( $args, $assoc_args ) {
			list( $domain ) = $args;
			$domain = strtolower( $domain );
			$title = !empty( $assoc_args['title'] ) ? ucfirst( $assoc_args['title'] ) : '';
			$home   = strtolower( $assoc_args['home'] );
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
			if ( empty( $home ) ) {
				$home = $blog_address;
			} elseif ( 'http' != substr($home,0,4) ) {
				$home = "http://" . $home;
			}

			if ( get_option( 'siteurl' ) != $blog_address ) {
				update_option( 'siteurl', $blog_address );
			}

			if ( get_option( 'home' ) != $home ) {
				update_option( 'home', $home );
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

		}

	} // class

	// class must be exist before we add since we're using if to define the class
	WP_CLI::add_command( 'pmc-site', 'PMC_WP_CLI_Site' );

} // if

// EOF