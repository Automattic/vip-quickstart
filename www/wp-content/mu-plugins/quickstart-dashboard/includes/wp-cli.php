<?php

WP_CLI::add_command( 'dashboard', 'Quickstart_Dashboard_CLI' );

class Quickstart_Dashboard_CLI extends WP_CLI_Command {
	/**
	 * @var string Type of command triggered so we can keep track of killswitch cleanup.
	 */
	private $command = '';

	/**
	 * @var bool Flag whether or not execution should be stopped.
	 */
	private $halt = false;

	/**
	 * @subcommand load_plugins
	 */
	function load_plugins( $args, $assoc_args ) {
		WP_CLI::line( 'Loading plugins...' );
		$instance = new Quickstart_Dashboard;

		$plugins = $instance->load_plugins();

		WP_CLI::line( 'Plugins loaded: ' );
		foreach ( $plugins as $name => $plugin ) {
			WP_CLI::line( "	$name: {$plugin->name()}" );
		}
	}

	/**
	 * @subcommand scan_repos
	 */
	function scan_repos( $args, $assoc_args ) {
		WP_CLI::line( 'Scanning Known Repositories...' );
		
		$repo_monitor = $this->load_repo_monitor();

		if ( ! $repo_monitor ) {
			return;
		}

		foreach ( $repo_monitor->get_repos() as $repo ) {
			$this->scan_repo( array(), array( 'id' => $repo['repo_id'] ) );
		}

		WP_CLI::line( 'Scan complete' );
	}

	/**
	 * Scans a specific repo for changes
	 *
	 * ## OPTIONS
	 *
	 * <path>
	 * : The path to the repository to add
	 * <name>
	 * : The friendly name of the repository
	 * <id>
	 * : The id of the repo to be scanned
	 *
	 * ## EXAMPLES
	 *
	 *     wp dashboard scan_repo --name=quickstart
	 *     wp dashboard scan_repo /srv
	 *     wp dashboard scan_repo --id=1
	 *
	 * @synopsis [<path>] [--id=<id>] [--name=<name>]
	 */
	function scan_repo( $args, $assoc_args ) {
		$repo_monitor = $this->load_repo_monitor();

		if ( ! $repo_monitor ) {
			return;
		}

		$repos = $repo_monitor->get_repos();
		
		if ( isset( $assoc_args['id'] ) ) {
			$enterred_id = intval( $assoc_args['id'] );
			foreach ( $repos as $r ) {
				if ( $r['repo_id'] == $enterred_id ) {
					$repo = $r;
					break;
				}
			}

			if ( !isset( $repo ) ) {
				WP_CLI::error( "Could not find repo with id '$enterred_id'" );
				return;
			}
		} elseif ( isset( $assoc_args['name'] ) ) {
			foreach ( $repos as $r ) {
				if ( strcasecmp( $r['repo_friendly_name'], $assoc_args['name'] ) === 0 ) {
					$repo = $r;
					break;
				}
			}

			if ( !isset( $repo ) ) {
				WP_CLI::error( "Could not find repo with name '{$assoc_args['name']}'" );
				return;
			}
		} else {
			if ( !isset( $args[0] ) ) {
				$args[0] = '.';
			}
			
			$path = realpath( $args[0] );
			foreach ( $repos as $r ) {
				if ( strcasecmp( $r['repo_path'], $path ) === 0 ) {
					$repo = $r;
					break;
				}
			}

			if ( !isset( $repo ) ) {
				WP_CLI::error( "Could not find repo with path '{$path}'" );
				return;
			}
		}

		// We now have the repo, trigger the scan

		WP_CLI::line( "Scanning {$repo['repo_type']} repo {$repo['repo_friendly_name']}..." );

		$results = $repo_monitor->scan_repo( $repo );

		// Output the repo status if out of date or error occured
		$text = $repo_monitor->get_status_text( $results, $repo['repo_type'] );

		if ( is_wp_error( $results) ) {
			WP_CLI::error( $text );
		} elseif ( $repo_monitor->repo_out_of_date( $results, $repo['repo_type'] ) ) {
			WP_CLI::warning( $text );
		}
	}

	/**
	 * Adds a repository to the Repo Monitor.
	 *
	 * ## OPTIONS
	 *
	 * <path>
	 * : The path to the repository to add
	 * <name>
	 * : The friendly name of the repository
	 *
	 * ## EXAMPLES
	 *
	 *     wp dashboard add_repo Quickstart /srv
	 *     wp dashboard add_repo --svn WordPress /srv/www/wp
	 *
	 * @synopsis <name> <path> [--warn] [--svn] [--autodetect] [--username] [--password]
	 */
	function add_repo( $args, $assoc_args ) {
		$type = 'git';
		if ( $assoc_args['autodetect'] ) {
			$type = 'autodetect';
		} elseif ( $assoc_args['svn'] ) {
			$type = 'svn';
		}

		$path = realpath( $args[1] );
		WP_CLI::line( "Adding $type repository {$args[0]}..." );
		WP_CLI::line( "Repo path: $path" );

		$repo_monitor = $this->load_repo_monitor();
		
		if ( ! $repo_monitor ) {
			return;
		}

		$credentials = array();
		if ( isset( $assoc_args['username'] ) ) {
			$credentials['username'] = sanitize_text_field( $assoc_args['username'] );
		}

		if ( isset( $assoc_args['password'] ) ) {
			$credentials['password'] = sanitize_text_field( $assoc_args['password'] );
		}

		$result = $repo_monitor->add_repo( array(
			'repo_type'			 => $type,
			'repo_path'			 => $path,
			'repo_friendly_name' => $args[0],
			'warn_out_of_date'   => ! $assoc_args['warn'],
		), true, ! isset( $credentials['password'] ), $credentials ); // Only allow interactive mode if password not given

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
			return;
		}

		if ( $assoc_args['autodetect'] ) {
			foreach ( $repo_monitor->get_repos() as $r ) {
				if ( $r['repo_id'] == $result ) {
					WP_CLI::line( 'Repo type detected: ' . WP_CLI::colorize( $r['repo_type'] ) );
					break;
				}
			}
		}

		WP_CLI::success( "Repo added with id $result!" );
	}

	/**
	 * Adds a repository to the Repo Monitor.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp dashboard list_repos
	 *     wp dashboard list_repos --id
	 *
	 * @synopsis [--id] [--only-ids]
	 */
	function list_repos( $args, $assoc_args ) {
		$repo_monitor = $this->load_repo_monitor();

		if ( ! $repo_monitor ) {
			return;
		}

		$format_str = '%2$s: %3$s';
		if ( $assoc_args['id'] ) {
			$format_str = '(%1$s) ' . $format_str;
		} elseif ( $assoc_args['only-ids'] ) {
			$format_str = '%1$s';
		}

		foreach ( $repo_monitor->get_repos() as $repo ) {
			WP_CLI::line( sprintf( $format_str, $repo['repo_id'], $repo['repo_friendly_name'], $repo['repo_path'] ) );
		}
	}

	/**
	 *
	 * @return RepoMonitor|bool The RepoMonitor plugin or false on failure
	 */
	private function load_repo_monitor() {
		$instance = new Quickstart_Dashboard;
		$plugins = $instance->load_plugins();
		
		if ( ! isset( $plugins['RepoMonitor'] ) ) {
			WP_CLI::error( 'Could not find RepoMonitor plugin' );
			return false;
		}

		return $plugins['RepoMonitor'];
	}
}
