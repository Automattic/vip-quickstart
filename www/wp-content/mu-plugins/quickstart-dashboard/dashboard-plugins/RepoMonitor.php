<?php

class RepoMonitor extends Dashboard_Plugin {

	const REPO_CPT = 'qs_repo';

	private $repos = null;
	private $repo_update_cache = array();

	function __construct() {
		// Register the dashboard plugin

		// Setup cron
	}
	
	/**
	 * Register 15 minute cron interval for scanning plugins.
	 * @param array[] $schedules
	 * @return array[] modified schedules
	 */
	public static function repo_15_min_cron_interval( $schedules ) {
		$schedules[ 'qs-dashboard-15-min-cron-interval' ] = array(
			'interval' => 900,
			'display' => __( 'Every 15 minutes', 'quickstart-dashboard' ),
			);
		return $schedules;
	}

	function name() {
		return __( 'Repo Monitor', 'quickstart-dashboard' );
	}
    
    function init() {
		$this->create_post_type();
		
        add_action( 'quickstart_dashboard_setup', array( $this, 'dashboard_setup' ) );
		add_action( 'repomonitor_scan_repos', array( $this, 'scan_repositories' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'print_admin_notice' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		add_filter( 'cron_schedules', array( $this, 'repo_15_min_cron_interval' ) );
    }
	
	function admin_init() {
		add_action( 'wp_ajax_repomonitor_list_repos', array( $this, 'ajax_list_repos' ) );
		add_action( 'wp_ajax_repomonitor_scan_repo', array( $this, 'ajax_scan_repo' ) );
		add_action( 'wp_ajax_repomonitor_update_repo', array( $this, 'ajax_update_repo' ) );
		
		// Add the cron job to check for updates
		if ( ! wp_next_scheduled( 'repomonitor_scan_repos' ) ) {
			wp_schedule_event( time(), 'qs-dashboard-15-min-cron-interval', 'repomonitor_scan_repos' );
		}

		if ( isset( $_REQUEST['page'] ) && 'repomonitor-update' === $_REQUEST['page'] && isset( $_REQUEST['no_wp'] ) ) {
			$this->update_repo_page();
		}
	}

	function admin_menu() {
		add_submenu_page( null, __( 'RepoMonitor Update Repo', 'quickstart-dashboard' ), __( 'RepoMonitor Update Repo', 'quickstart-dashboard' ), 'manage_options', 'repomonitor-update', array( $this, 'update_repo_page' ) );
	}
	
	function admin_enqueue_scripts() {
		if ( isset( $_REQUEST['page'] ) && 'vip-dashboard' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'repomonitor_js', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/js/repomonitor.js', array( 'jquery' ) );
			wp_localize_script( 'repomonitor_js', 'repomonitor_settings', array(
				'translations'	=> array(
					'fetching_repos' => __( 'Retrieving list of repositories', 'quickstart-dashboard' ),
					'scanning_repo'	 => __( 'Scanning {repo_name}', 'quickstart-dashboard' ),
					'updating_repo'  => __( 'Updating {repo_name}', 'quickstart-dashboard' ),
					'action_done'	 => __( 'Done.', 'quickstart-dashboard' ),
					'action_fail'	 => __( 'Failed.', 'quickstart-dashboard' ),
					'updating_table' => __( 'Updating status table', 'quickstart-dashboard' ),
					'update_action'  => __( 'Update', 'quickstart-dashboard' ),
					'update_descr'   => __( 'Update this repo', 'quickstart-dashboard' ),
				),
			) );
		}
	}

	function print_admin_notice() {
		// Check if any repos are out of date
		$repos = $this->get_repos();
		$outofdate = array();
		foreach ( $repos as $repo ) {
			$status = $this->get_repo_status( $repo['repo_id'] );
			if ( $repo['warn_out_of_date'] && $this->repo_out_of_date( $status, $repo['repo_type'] ) ) {
				$outofdate[] = array( 'repo' => $repo, 'status' => $status );
			}
		}

		$outofdate_count = count( $outofdate );
		if ( $outofdate_count == 1 ) {
			$message = sprintf(
				__( 'The %s repo is out of date: %s Visit the <a href="%s">VIP Dashboard</a> for more info.', 'quickstart-dashboard' ),
				$outofdate[0]['repo']['repo_friendly_name'],
				$this->get_status_text( $outofdate[0]['status'], $outofdate[0]['repo']['repo_type'] ),
				menu_page_url( 'vip-dashboard', false )
			);

			?>
			<div class="update update-nag"><?php echo $message; ?></div>
			<?php
		} elseif ( $outofdate_count > 1 ) {
			$message = sprintf(
				__( 'There are %s repos that are out of date. Visit the <a href="%s">VIP Dashboard</a> for more info.', 'quickstart-dashboard' ),
				number_format( $outofdate_count ),
				menu_page_url( 'vip-dashboard', false )
			);
			?>
			<div class="update update-nag"><?php echo $message; ?></div>
			<?php
		}
	}
	
	/**
	 * Register our CPT
	 */
	public static function create_post_type() {
		register_post_type(
			self::REPO_CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Repositories' ),
					'singular_name' => __( 'Repository' ),
					),
				'public'       => false,
				'has_archive'  => false,
				'rewrite'      => false,
				'show_ui'      => false,
				'show_in_menu' => false,
				'supports'     => array(
					'title',
					),
				)
			);
	}

	function dashboard_setup() {
		$update_link = ' <a class="widget_update" title="' . __( 'Check for updates', 'quickstart-dashboard' ) . '"><span class="noticon noticon-refresh"></span></a>';
        wp_add_dashboard_widget( 'quickstart_dashboard_repomonitor', $this->name() . $update_link, array( $this, 'show' ) );
    }

	function show() {
		echo '<div id="repomonitor-update-box" class="widget-update-box"></div>';
		$table = new RepoMonitorWidgetTable( $this );
		$table->prepare_items();
		$table->display();
	}
	
	function ajax_list_repos() {
		if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-repos' ) ) {
			wp_send_json_error( array( 'error' => __( 'You do not have sufficient permissions to access this page.', 'quickstart-dashboard' ) ) );
		}

		// Get the list of repos and summarize
		$output_data = array();
		foreach ( $this->get_repos() as $repo ) {
			$status = $this->get_repo_status( $repo['repo_id'] );
			$out_of_date = $this->repo_out_of_date( $status, $repo['repo_type'] );
			
			$output_data[] = array_merge( $repo, array(
				'status_text' => $this->get_status_text( $status, $repo['repo_type'] ),
				'out_of_date' => $out_of_date,
				'can_update'  => !$out_of_date || $this->can_update_repo( $repo ),
			) );
		}
		
		wp_send_json_success( $output_data );
	}
	
	function ajax_scan_repo() {
		if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-repos' ) ) {
			wp_send_json_error( array( 'error' => __( 'You do not have sufficient permissions to access this page.', 'quickstart-dashboard' ) ) );
		}
		
		$repo_id = intval( $_REQUEST['repo_id'] );

		// Get the list of repos and summarize
		$scan_repo = null;
		foreach ( $this->get_repos() as $repo ) {
			if ( $repo['repo_id'] === $repo_id ) {
				$scan_repo = $repo;
				break;
			}
		}
		
		if ( is_null( $scan_repo ) ) {
			wp_send_json_error( array( 'error' => __( 'The repo id was not found.', 'quickstart-dashboard' ) ) );
		}
		
		// Scan the repo
		$result = $this->scan_repo( $repo );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'error' => $result->get_error_message() ) );
		}
		
		// Send back useful info
		wp_send_json_success( $this->get_ajax_repo_status( $repo ) );
	}

	function ajax_update_repo() {
		if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-repos' ) ) {
			wp_send_json_error( array( 'error' => __( 'You do not have sufficient permissions to access this page.', 'quickstart-dashboard' ) ) );
		}
		
		if ( ! isset( $_REQUEST['repo_id'] ) ) {
			wp_die( __( 'No repo specified.', 'quickstart-dashboard' ) );
		}

		$repo_id = intval( $_REQUEST['repo_id'] );

		// Get the list of repos and summarize
		$repo = null;
		foreach ( $this->get_repos() as $potential_repo ) {
			if ( $potential_repo['repo_id'] === $repo_id ) {
				$repo = $potential_repo;
				break;
			}
		}

		if ( is_null( $repo ) ) {
			wp_send_json_error( array( 'error' => __( 'The repo id was not found.', 'quickstart-dashboard' ) ) );
		}

		// Check that we can perform an update
		if ( ! $this->can_update_repo( $repo ) ) {
			wp_send_json_error( array( 'error' =>  __( 'Error: This repo is not currently in a state where it can be updated.', 'quickstart-dashboard' ) ) );
		}

		// Start the update
		$result = $this->update_repo( $repo, false );

		// Print the result
		if ( false === $result ) {
			wp_send_json_error( array( 'error' => __( 'The update failed for an unknown reason.', 'quickstart-dashboard' ) ) );
		} elseif ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'error' => $result->get_error_message() ) );
		} else {
			// Scan the repo
			$scan_result = $this->scan_repo( $repo, false );
			if ( is_wp_error( $scan_result ) ) {
				wp_send_json_error( array( 'error' => $scan_result->get_error_message() ) );
			}

			wp_send_json_success( $this->get_ajax_repo_status( $repo ) );
		}
	}

	/**
	 * Returns an object with most of a repos status info combined in one. This is
	 * very useful for JS where it is not convenient to quickly retrieve status information.
	 *
	 * @param array $repo The repo to fetch status info for.
	 * @return array
	 */
	function get_ajax_repo_status( $repo ) {
		$status = $this->get_repo_status( $repo['repo_id'] );
		$out_of_date = $this->repo_out_of_date( $status, $repo['repo_type'] );
		return array_merge( $repo, array(
			'status_text' => $this->get_status_text( $status, $repo['repo_type'] ),
			'out_of_date' => $out_of_date,
			'can_update'  => !$out_of_date || $this->can_update_repo( $repo ),
			'update_link' => add_query_arg( array( 'repo_id' => $repo['repo_id'], 'no_wp' => true, 'page' => 'repomonitor-update' ), '' ),
		) );
	}

	function update_repo_page() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You have insufficient permissions to perform that action.', 'quickstart-dashboard' ) );
		}

		echo '<h2>' . __( 'Updating Repo', 'quickstart-dashboard' ) . '</h2>';

		if ( ! isset( $_REQUEST['repo_id'] ) ) {
			wp_die( __( 'No repo specified.', 'quickstart-dashboard' ) );
		}

		// Get the repo we're updating
		$repos = $this->get_repos();
		$repo = null;
		foreach ( $repos as $r ) {
			if ( $r['repo_id'] == $_REQUEST['repo_id'] ) {
				$repo = $r;
				break;
			}
		}

		if ( is_null( $repo ) ) {
			wp_die( sprintf( __( 'Unknown repo id: ', 'quickstart-dashboard' ), intval( $_REQUEST['repo_id'] ) ) );
		}

		// Check that we can perform an update
		if ( ! $this->can_update_repo( $repo ) ) {
			wp_die( __( 'Error: This repo is not currently in a state where it can be updated.', 'quickstart-dashboard' ) );
		}

		// Start the update
		$result = $this->update_repo( $repo, true );

		// Print the result
		if ( false === $result ) {
			printf( '<p>%s</p>', __( 'Update failed.', 'quickstart-dashboard' ) );
		} elseif ( is_wp_error( $result ) ) {
			printf( '<p>%s %s</p>', __( 'Update failed:', 'quickstart-dashboard' ), $result->get_error_message() );
		} else {
			// Scan the repo
			$this->scan_repo( $repo );

			printf( '<p>%s</p>', __( 'Update successful!', 'quickstart-dashboard' ) );
		}

		if ( isset( $_REQUEST['no_wp'] ) ) {
			printf( '<p><a href="%s">&larr; %s</a></p>', 'javascript:window.parent.location.reload()', __( 'Return to dashboard', 'quickstart-dashboard' ) );
			die();
		} else {
			// Print the return link
			printf( '<p><a href="%s">&larr; %s</a></p>', menu_page_url( 'vip-dashboard', false ), __( 'Return to dashboard', 'quickstart-dashboard' ) );
		}
	}

	function scan_repositories() {
		foreach ( $this->get_repos() as $repo ) {
			$this->scan_repo( $repo );
		}
	}
	
	function scan_repo( $repo, $allow_interactive = true ) {
		// Run the command to determine if it needs an update
		if ( 'svn' == $repo['repo_type'] ) {
			$results = $this->scan_svn_repo( $repo['repo_path'], $allow_interactive );
		} elseif ( 'git' == $repo['repo_type'] ) {
			$results = $this->scan_git_repo( $repo['repo_path'] );
		}

		if ( is_wp_error( $results) ) {
			return $results;
		}

		// Save the new repo status
		$this->set_repo_status( $repo['repo_id'], $results );
		
		// Force a scan of the repo for updatability. This result gets cached.
		$this->can_update_repo( $repo, true );
		
		return $results;
	}

	function get_repos() {
		if ( is_null( $this->repos ) ) {
			$this->load_repos();
		}

		return $this->repos;
	}

	/**
	 * Gets the saved status for the given repo.
	 *
	 * @param int $repo_id The repo to get the status of
	 * @return array The repo status
	 */
	function get_repo_status( $repo_id ) {
		$status = get_post_meta( $repo_id, 'qs_dashboard_repo_status', true );

		if ( is_array( $status ) ) {
			return $status;
		}

		return array();
	}

	/**
	 * Saves the status for the given repo.
	 *
	 * @param int $repo_id The id of the repo
	 * @param array $status The status object from a scan
	 * @return bool True on success or false on failure
	 */
	function set_repo_status( $repo_id, $status ) {
		return update_post_meta( $repo_id, 'qs_dashboard_repo_status', (array) $status );
	}

	private function load_repos() {
		$args = array(
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => -1,
			'post_type' => self::REPO_CPT,
		);

		$repo_query = get_posts( $args );

		$this->repos = array();
		foreach ( $repo_query as $repo ) {
			$this->repos[] = array(
				'repo_id'	=> $repo->ID,
				'repo_path' => get_post_meta( $repo->ID, 'qs_repo_path', true ),
				'repo_type' => get_post_meta( $repo->ID, 'qs_repo_type', true ),
				'repo_friendly_name' => $repo->post_title,
				'warn_out_of_date' => get_post_meta( $repo->ID, 'qs_warn_out_of_date', true ),
			);
		}
	}

	function scan_svn_repo( $repo_path, $allow_interactive = false, $credentials = array() ) {
		$cwd = getcwd();
		
		// Variables to load output into
		$output = array();
		$return_value = -1;
		
		chdir( $repo_path );

        // Execute info command to get info about local repo
		$command_args = array();
		if ( ! $allow_interactive ) {
			$command_args[] = '--non-interactive';
		}

		if ( !empty( $credentials['username'] ) ) {
			$command_args[] = '--username=' . escapeshellarg( $credentials['username'] );
		}

		if ( !empty( $credentials['password'] ) ) {
			$command_args[] = '--password=' . escapeshellarg( $credentials['password'] );
		}
		
        exec( sprintf( 'svn info %s', implode( ' ', $command_args ) ), $output, $return_value );

        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn info. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }
        
        $info = $this->parse_svn_info( $output );
        
		$command_args[] = '-u';
		
		// Execute status command to get file into
		exec( sprintf( 'svn status %s', implode( ' ', $command_args ) ), $output, $return_value );
        
        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn status. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }
		
		// Go back to the previous working directory
		chdir( $cwd );

		$status = array_merge( $this->parse_svn_status( $output ), $info );
		$status['scan_time'] = time();

        return $status;
	}

    private function parse_svn_info( $output ) {
        $status = array(
            'local_revision' => -1,
            'repo_url'       => '',
        );

        foreach ( $output as $line ) {
            if ( strpos( $line, 'URL:' ) === 0 ) {
                $status['repo_url'] = trim( substr( $line, 4 ) );
            } else if ( strpos( $line, 'Revision:' ) === 0 ) {
                $status['local_revision'] = intval( trim( substr( $line, 9 ) ) );
            }
        }

        return $status;
    }

    private function parse_svn_status( $output ) {
        $status = array(
            'files_out_of_date' => array(),
            'locally_modified'  => array(),
            'remote_revision'   => -1,
        );

        foreach ( $output as $line ) {
            // Parse line of output from svn status command
            if ( !preg_match( '/(?<args>(\W|[ACDIMRX?!~CML+SX*]){9})\W*(?<filerev>\d*)\W*(?<filename>(\S)*)/', $line, $matches) ) {
                // Check if this line shows the remote revision
                if ( preg_match( '/revision:\W*(?<remote_revision>\d*)/', $line, $matches ) ) {
                    $status['remote_revision'] = intval( $matches['remote_revision'] );

                    // The remote revision line is the last line of meaningful output, exit the loop
                    break;
                }
                continue;
            }

            // Check for a locally modified file
            if ( ( !ctype_space($matches['args'][0]) && 'X' != $matches['args'][0] ) || !ctype_space( $matches['args'][1] ) ) {
                $status['locally_modified'][] = $matches['filename'];
            }

            // Check the ninth column to see if the file is out of date WRT the server
            if ( isset( $matches['args'][9] ) && !ctype_space( $matches['args'][9] ) ) {
                $status['files_out_of_date'][] = $matches['filename'];
            }
        }

        return $status;
    }

	/**
	 * Returns whether or not the repo is considered in need of updating from the
	 * result of a scan.
	 *
	 * @param array $status The status object from a scan.
	 * @return bool Whether the repo is out of date
	 */
	function repo_out_of_date( $status, $repo_type = 'git' ) {
		if ( empty( $status ) ) {
			return false;
		}
		
		if ( 'git' === $repo_type ) {
			return !empty( $status['behind'] ) || !empty( $status['diverged'] );
		} elseif ( 'svn' === $repo_type ) {
			return !empty( $status['files_out_of_date'] ) || 
				( isset( $status['remote_revision'] ) && isset( $status['local_revision'] ) && 
					$status['remote_revision'] != $status['local_revision'] );
		}

		return false;
	}

	function scan_git_repo( $repo_path ) {
		$cwd = getcwd();

		// Variables to load output into
		$output = array();
        $update_output = array();
		$return_value = -1;

		// Go to repository directory
		chdir( $repo_path );

		// Check for the .git dir
		if ( !file_exists( '.git' ) ) {
			return new WP_Error( 1, __( 'Could not find .git directory. Is this the root of a git repo?', 'quickstart-dashboard' ) );
		}

		// Start by updating remotes
		exec( 'git remote update origin', $update_output, $return_value );

        if ( 0 != $return_value ) {
            return new WP_Error(
                $return_value,
                sprintf( __( 'Error fetching remote "origin". git returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }

		// Now check the repo status
		exec( 'git status -u no', $output, $return_value );

        if ( 0 != $return_value ) {
            return new WP_Error(
                $return_value,
                sprintf( __( 'Error fetching git status. git returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }

		// Go back to the previous working directory
		chdir( $cwd );

		// Check return value
		if ( 0 != $return_value ) {
			print( 'Git command did not return 0' );
			return;
		}

		// The second line of output will contain the repo status
		$status = '';
		foreach ( $output as $line ) {
			$status .= trim( $line, '# ' ) . "\n";
		}

		$status = $this->parse_git_status_text( $status );
		$status['scan_time'] = time();

		return $status;
	}

	private function parse_git_status_text( $status ) {
		$status_var = array(
			'on_branch' => preg_match( "/on\Wbranch\W(?<branch>[a-zA-Z_-]+)/i", $status, $matches ) ? $matches['branch'] : '<unknown>',
			'behind' => false,
			'diverged' => false,
		);

		// Check if we're behind
		if ( preg_match( "/your\Wbranch\Wis\Wbehind\W'(?<branch>[a-zA-Z\/]+)'\Wby\W(?<numcommits>\d+)\Wcommit/i", $status, $matches ) ) {
			$status_var['behind'] = array(
				'branch'	  => $matches['branch'],
				'num_commits' => $matches['numcommits'],
			);
		}

        //'(?<branch>[a-zA-Z\/]+)'(\s|\S)*diverged(\s|\S)*(?<firstcount>[0-9]+)(\s|\S)*(?<secondcount>[0-9]+)

        // Check if branch diverged
        if ( preg_match( "/'(?<branch>[a-zA-Z\/]+)'(\s|\S)*diverged(\s|\S)*(?<firstcount>[0-9]+)(\s|\S)*(?<secondcount>[0-9]+)/i", $status, $matches ) ) {
			$status_var['diverged'] = array(
				'branch'              => $matches['branch'],
				'local_commit_count'  => $matches['firstcount'],
                'remote_commit_count' => $matches['secondcount'],
			);
		}

		return $status_var;
	}

    /**
     * Formats a status array into a human-readable string
     *
     * @param array $status A status array from parse_git_status_text().
     * @param string $repo_type The type of repo that generate the status text
     * @return string The textual representation of the repo status
     */
    function get_status_text( $status, $repo_type = 'git' ) {
        if ( is_wp_error( $status ) ) {
            return $status->get_error_message();
        }

		if ( empty( $status ) ) {
			return __( 'Repo not yet scanned.', 'quickstart-dashboard' );
		}

        switch ($repo_type) {
            case 'git':
                $text = __( sprintf( 'Branch %s ', esc_attr( $status['on_branch'] ) ), 'quickstart-dashboard' );

                if ( $status['behind'] !== false ) {
                    $text .= __(
                        sprintf( 'behind remote branch %s by %s commits.',
                            esc_attr( $status['behind']['branch'] ),
                            number_format( $status['behind']['num_commits'] )
                        ),
                        'quickstart-dashboard'
                    );
                } elseif ( $status['diverged'] !== false ) {
                    $text .= __(
                        sprintf( '%s commits ahead and %s commits behind remote branch %s.',
                            number_format( $status['diverged']['local_commit_count'] ),
                            number_format( $status['diverged']['remote_commit_count'] ),
                            esc_attr( $status['diverged']['branch'] )
                        ),
                        'quickstart-dashboard'
                    );
                } else {
					$text .= __( 'up to date.', 'quickstart-dashboard' );
				}

                return $text;

            case 'svn':
                $text = '';
				
				if ( !isset( $status['local_revision'] ) || !isset( $status['remote_revision'] ) ) {
					return sprintf( 
						__( 'Status information not available. Please try manually scanning the repo using %s', 'quickstart-dashboard' ),
						'<code>wp dashboard scan_repo</code>'
					);
				}

                // Base status text
                if ( $status['local_revision'] == $status['remote_revision'] ) {
                    // Revisions are the same
                    $text .= __( 'Working copy up to date', 'quickstart-dashboard' );
                } else {
                    $text .= __( 'Working copy needs update', 'quickstart-dashboard' );
                }

                // Local file modifications
                if ( empty( $status['locally_modified'] ) ) {
                    // No locally modified files
                    $text .= __( ' with no local changes', 'quickstart-dashboard' );
                } else {
                    $text .= sprintf(
                        __( ' with %s local changes', 'quickstart-dashboard' ),
                        number_format( count( $status['locally_modified'] ) )
                    );
                }

                // Files needing updates
                if ( !empty( $status['files_out_of_date'] ) ) {
                    $text .= sprintf(
                        __( ' and %s remote changes', 'quickstart-dashboard' ),
                        number_format( count( $status['files_out_of_date'] ) )
                    );
                }

                return $text;
        }
    }

	/**
	 * Adds a repo to be tracked by the RepoMonitor. The repo must already exist.
	 *
	 * Git credentials are not yet supported.
	 *
	 * Hooks are only supported on git repositories.
	 *
	 * @param array $args The array arguments
	 * @param boolean $add_hooks Whether or not to add repo hooks (git only)
	 * @param boolean $allow_interactive Whether or not to allow the subcommands to give interactive prompts.
	 * @param array $credentials Optional credentials to provide the command (svn only)
	 * @return int\WP_Error The repo ID or WP_Error on failure.
	 */
	function add_repo( $args, $add_hooks = true, $allow_interactive = false, $credentials = array() ) {
		$defaults = array(
			'repo_type'			 => 'git',
			'repo_path'			 => '',
			'repo_friendly_name' => '',
			'warn_out_of_date'   => true,
		);

		$args = array_merge( $defaults, $args );
		
		// Get the real path in case this was a relative path
		$args['repo_path'] = realpath( $args['repo_path'] );

		// Check if another repo exists with this name or path
		$repos = $this->get_repos();
		foreach ( $repos as $repo ) {
			if ( rtrim( $repo['repo_path'], '/' ) == rtrim( $args['repo_path'], '/' ) ) {
				return new WP_Error( 2, __( 'A repo with this path already exists.', 'quickstart-dashboard' ) );
			}

			if ( strcasecmp( $repo['repo_friendly_name'], $args['repo_friendly_name'] ) == 0 ) {
				return new WP_Error( 2, __( 'A repo with this name already exists.', 'quickstart-dashboard' ) );
			}
		}

		// Test that the repo exists by scanning it
		if ( 'svn' == $args['repo_type'] ) {
			$args['repo_status'] = $this->scan_svn_repo( $args['repo_path'], $allow_interactive, $credentials );
		} elseif ( 'git' == $args['repo_type'] ) {
			$args['repo_status'] = $this->scan_git_repo( $args['repo_path'] );
		} elseif ( 'autodetect' == $args['repo_type'] ) {
			$args['repo_status'] = $this->scan_git_repo( $args['repo_path'] );
			$args['repo_type']   = 'git';

			if ( is_wp_error( $args['repo_status'] ) ) {
				$args['repo_status'] = $this->scan_svn_repo( $args['repo_path'], $allow_interactive, $credentials );
				$args['repo_type']   = 'svn';

				if ( is_wp_error( $args['repo_status'] ) ) {
					$args['repo_status'] = new WP_Error( 3, __( 'Error: Could not autodetect repo type.', 'quickstart-dashboard' ) );
				}
			}
		} else {
			return new WP_Error( 1, sprintf( __( 'Error: Unknown repo type %s.', 'quickstart-dashboard' ), $args['repo_type'] ) );
		}

		if ( is_wp_error( $args['repo_status'] ) ) {
			return $args['repo_status'];
		}

		$post_args = array(
			'post_type' => self::REPO_CPT,
			'post_title' => $args['repo_friendly_name'],
			'post_status' => 'publish',
		);

		$id = wp_insert_post( $post_args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		add_post_meta( $id, 'qs_repo_type', $args['repo_type'], true );
		add_post_meta( $id, 'qs_repo_path', $args['repo_path'], true );
		add_post_meta( $id, 'qs_warn_out_of_date', $args['warn_out_of_date'], true );
		$this->set_repo_status( $id, $args['repo_status'] );

		$args['repo_id'] = $id;
		
		$this->repos[] = $args;
		if ( $add_hooks ) {
			$result = $this->setup_repo_hooks( $args );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $id;
	}

	private function setup_repo_hooks( $repo ) {
		$base_path = rtrim( $repo['repo_path'], '/' );
		if ( 'git' === $repo['repo_type'] ) {
			$hook_paths = array(
				'post-update' => $base_path . '/.git/hooks/post-update',
				'post-merge'  => $base_path . '/.git/hooks/post-merge',
				'post-commit' => $base_path . '/.git/hooks/post-commit',
			);
		} elseif ( 'svn' === $repo['repo_type'] ) {
			// Hooks not yet supported for svn repos
			return;
		} else {
			return;
		}

		foreach ( $hook_paths as $slug => $hook_path ) {
			if ( ! file_exists( $hook_path ) ) {
				// Create the hook file
				$success = file_put_contents( $hook_path, "#!/bin/sh\n" );
				if ( false === $success ) {
					return new WP_Error( $success, sprintf( __( 'Unable to create hook %s.', 'quickstart-dashboard' ), $slug ) );
				}
			}

			$command = "/usr/bin/wp dashboard scan_repo {$repo['repo_path']}";
			$success = file_put_contents( $hook_path, "# Quickstart Dashboard Repo Monitor\n$command", FILE_APPEND );
			if ( false === $success ) {
				return new WP_Error( $success, sprintf( __( 'Unable to append to hook hook file %s.', 'quickstart-dashboard' ), $slug ) );
			}

			//   Make sure the file is executable
			chmod( $hook_path, 0755 );
		}
	}
	
	/**
	 * Checks to see if we can *probably* update the repo. This checks for an uncommitted
	 * changes inside the working directory.
	 *
	 * Ignores untracked files.
	 *
	 * @param array $repo
	 * @return boolean Returns true if the working directory is clean, or false otherwise.
	 */
	function can_update_repo( $repo, $force_rescan = false ) {
		if ( !$force_rescan ) {
			if ( isset ( $this->repo_update_cache[$repo['repo_id']] ) ) {
				return $this->repo_update_cache[$repo['repo_id']];
			}
			
			$postmeta_val = get_post_meta( $repo['repo_id'], 'repomonitor_can_update_repo', true );
			if ( $postmeta_val !== '' ) {
				return $postmeta_val;
			}
		}
		
		$dirty_files = $this->get_dirty_files( $repo );

		if ( false === $dirty_files ) {
			return false;
		}
		
		$can_update = empty( $dirty_files['staged'] ) && empty( $dirty_files['unstaged'] );

		// Save the result
		$this->repo_update_cache[$repo['repo_id']] = $can_update;
		update_post_meta( $repo['repo_id'], 'repomonitor_can_update_repo', $can_update );
		
		return $can_update;
	}

	/**
	 * Gets all staged, unstaged and untracked files in a repo.
	 *
	 * The array will have sub-arrays 'staged', 'unstaged', and 'untracked' with
	 * a list of each file matching that criteria.
	 *
	 * If the repo is an SVN repo, all changes will be 'unstaged' except for
	 * untracked files.
	 *
	 * @param array $repo The repo to get dirt files for.
	 * @return array|boolean Returns an array of files on success or false on failure.
	 */
	function get_dirty_files( $repo ) {
		$cwd = getcwd();

		// Variables to load output into
		$output = array();
		$return_value = -1;
		$staged_col = 0;
		$unstaged_col = 1;
		$untracked_char = '?';

		// Go to repository directory
		chdir( $repo['repo_path'] );

		// Fetch the status
		if ( $repo['repo_type'] == 'git' ) {
			exec( 'git status --porcelain', $output, $return_value );
		} elseif ( $repo['repo_type'] == 'svn' ) {
			exec( 'svn status', $output, $return_value );
			$unstaged_col = 0;
		} else {
			return false;
		}

		if ( 0 !== $return_value ) {
			return false;
		}

		// Go back to previous working directory
		chdir( $cwd );

		// Parse the repo status
		$staged = array();
		$unstaged = array();
		$untracked = array();
		foreach ( $output as $line ) {
			// Question marks occur for files that have not been added
			if ( $untracked_char == $line[0] || $untracked_char == $line[1] ) {
				$untracked[] = trim( substr( $line, 2 ) );
				continue;
			}

			// Check the staged column. Only used by git.
			if ( $repo['repo_type'] == 'git' && ! ctype_space( $line[$staged_col] ) ) {
				$staged[] = trim( substr( $line, 2 ) );
			}

			// Check the unstaged column. Used by SVN and git.
			if ( ! ctype_space( $line[$unstaged_col] ) ) {
				$unstaged[] = trim( substr( $line, 2 ) );
			}
		}

		return array( 'staged' => $staged, 'unstaged' => $unstaged, 'untracked' => $untracked );
	}

	private function exec_cmd( $cmd, &$output, &$return_value, $echo = false ) {
		if ( $echo ) {
			printf( '<p>' . __( "Executing command: <code>%s</code>\n", 'quickstart-dashboard' ) . '</p>', esc_html( $cmd ) );
			echo '<pre>';
			passthru( $cmd, $return_value );
			echo '</pre>';
			flush();
		} else {
			exec( $cmd . ' 2>&1', $output, $return_value );
		}
	}

	/**
	 * Updates the given repo to the latest version.
	 * @param array $repo The repo to update
	 * @param boolean $echo Whether the output should be echoed.
	 * @param array $output The output from the executed commands will be loaded into this variable.
	 * @return boolean|WP_Error True on success or false|WP_Error on failure.
	 */
	function update_repo( $repo, $echo = false, &$output = null ) {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You have insufficient permissions to perform that action.', 'quickstart-dashboard' ) );
		}

		// Always check if we can update the repo before we try
		if ( ! $this->can_update_repo( $repo, true ) ) {
			return false;
		}

		// Go to the repo directory
		$cwd = getcwd();

		// Variables to load output into
		$return_value = -1;

		// Grab the repo status
		$status = $this->get_repo_status( $repo['repo_id'] );

		// Go to repository directory
		chdir( $repo['repo_path'] );
		if ( $echo ) {
			printf( '<p>' . __( "Changing to directory: %s\n", 'quickstart-dashboard' ) . '</p>', $repo['repo_path'] );
		}

		// Okay, looks like we're good to go. LEEEROY JEEEEEENKINS!!
		if ( 'git' == $repo['repo_type'] ) {
			// Fetch updates
			$this->exec_cmd( 'git fetch origin', $output, $return_value, $echo );

			if ( 0 !== $return_value ) {
				return new WP_Error( $return_value, __( 'Git fetch failed.', 'quickstart-dashboard' ) );
			}

			// Attempt a merge
			if ( !empty( $status['behind'] ) && !empty( $status['behind']['branch'] ) ) {
				$cmd = sprintf(
					'git merge %s %s',
					$status['behind']['branch'], // from branch
					$status['on_branch'] // to branch
				);
			} elseif ( !empty( $status['diverged'] ) && !empty( $status['diverged']['branch'] ) ) {
				$cmd = sprintf(
					'git merge %s %s',
					$status['diverged']['branch'], // from branch
					$status['on_branch'] // to branch
				);
			} else {
				$cmd = 'git merge';
			}

			$this->exec_cmd( $cmd, $output, $return_value, $echo );

			if ( 0 !== $return_value ) {
				// Uhoh, merge failed. Revert!
				$this->exec_cmd( 'git merge --abort', $output, $revert_return_value, $echo );

				if ( 0 !== $revert_return_value ) {
					// Merge failed. Just give up.
					return new WP_Error( $revert_return_value, __( 'The git merge failed. We tried to undo it but that failed too.', 'quickstart-dashboard' ) );
				}

				return new WP_Error( $return_value, __( 'Merging changes from origin failed, so we aborted the merge. Please try manually.', 'quickstart-dashboard' ) );
			}
		} elseif ( 'svn' == $repo['repo_type'] ) {
			$this->exec_cmd( 'svn merge --dry-run -r BASE:HEAD .', $output, $return_value, $echo );

			if ( 0 !== $return_value ) {
				return new WP_Error( $return_value, __( 'The SVN dry run failed, so we won\'t go any further. Please try to update manually.', 'quickstart-dashboard' ) );
			}

			// Looks like we're good...
			$this->exec_cmd( 'svn up', $output, $return_value, $echo );

			if ( 0 !== $return_value ) {
				return new WP_Error( $return_value, __( 'The SVN update failed. Please take a look at the repository.', 'quickstart-dashboard' ) );
			}
		}

		// Party! We didn't fail...

		chdir( $cwd );
		if ( $echo ) {
			printf( '<p>' . __( "Going back to cwd: %s\n", 'quickstart-dashboard' ) . '</p>', $cwd );
		}

		return true;
	}
}

class RepoMonitorWidgetTable extends DashboardWidgetTable {
	/**
	 * @var RepoMonitor
	 */
	private $repo_monitor = null;

    function __construct( $repo_monitor ) {
		$this->repo_monitor = $repo_monitor;

        parent::__construct( array(
            'singular'  => 'repo',
            'plural'    => 'repos',
            'ajax'      => false
        ) );
    }

	function get_table_classes() {
		$classes = parent::get_table_classes();
		$classes[] = 'vip-dashboard-repomonitor-table';
		return $classes;
	}

	function single_row( $item ) {
		$row_classes = parent::get_row_classes();
		
		if ( $item['warn'] ) {
			$row_classes[] = 'active';
			if ( $item['warn'] && ! $item['can_update'] ) {
				$row_classes[] = 'update';
			}
		} else {
			$row_classes[] = 'inactive';
		}

		echo '<tr id="repo-' . esc_attr( $item['repo_id'] ) . '-status" class="' . implode( ' ', $row_classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Check if the item needs updating. If it can't be updated, tell the user to fix the problem.
		if ( $item['warn'] && ! $item['can_update'] ) {
			$message = '';
			if ( $item['repo_type'] == 'git' ) {
				$message = __( 'Please <code>git stash</code> or <code>git commit</code> your changes to update.', 'quickstart-dashboard' );
			} elseif ( $item['repo_type'] == 'svn' ) {
				$message = __( 'Please <code>svn commit</code> or <code>svn revert</code> your changes to update.', 'quickstart-dashboard' );
			}

			printf( '<tr class="plugin-update-tr"><td class="plugin-update colspanchange" colspan="%s"><div class="update-message">%s</div></td></tr>', $this->get_column_count(), wp_kses( $message, wp_kses_allowed_html( 'post' ) ) );
		}
	}

    function column_default( $item, $column_name ){
		$retval = '';
        switch( $column_name ){
			case 'status':
				$retval = sprintf( '<span class="vip-dashboard-repo-status">%s</span>', esc_html( $this->repo_monitor->get_status_text( $item[$column_name], $item['repo_type'] ) ) );
				break;
			case 'repo_type':
				$retval = sprintf( '<span class="vip-dashboard-repo-type">%s</span>', esc_html( $item['repo_type'] ) );
				break;
            default:
                $retval = $item[$column_name];
        }

		return $retval;
    }

    function column_repo_friendly_name( $item ){
        //Build row actions
        $actions = array();

		// Show the update action only if the repo needs and update and we can update it
		if ( $item['warn']  && $item['can_update'] ) {
			$actions['update'] = sprintf(
				'<a class="repo-update" title="%1$s" href="%2$s">%3$s</a><input type="hidden" class="repo-id" value="%4$s" />',
				__( 'Update this repo', 'quickstart-dashboard' ),
				add_query_arg( array( 'repo_id' => $item['repo_id'], 'no_wp' => true ), menu_page_url( 'repomonitor-update', false ) ),
				__( 'Update', 'quickstart-dashboard' ),
				esc_attr( $item['repo_id'] )
			);
		}

        //Return the title contents
		return sprintf( '<strong>%s</strong>%s', esc_html( $item['repo_friendly_name'] ), $this->row_actions( $actions, true ) );
    }

    function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item['repo_id']
        );
    }

    function get_columns(){
		$cols = array(
            'cb'				 => '<input type="checkbox" />', //Render a checkbox instead of text
            'repo_friendly_name' => __( 'Repo', 'quickstart-dashboard' ),
			'repo_type'			 => '', // Don't show anything in the header
			'status'			 => __( 'Status', 'quickstart-dashboard' ),
        );

        return apply_filters( 'viprepomonitor_table_get_columns', $cols );
    }

    function get_sortable_columns() {
        return apply_filters( 'viprepomonitor_table_get_sortable_columns', array() );
    }

    function get_bulk_actions() {
        return apply_filters( 'viprepomonitor_table_bulk_actions', array(
			'update' => __( 'Update', 'quickstart-dashboard' ),
		) );
    }

    function process_bulk_action() {
		do_action( 'viprepomonitor_table_do_bulk_actions' );
    }

    function prepare_items() {
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();
		
        $total_items = 0;
        $this->items = array();
		foreach ( $this->repo_monitor->get_repos() as $repo ) {
			$status = $this->repo_monitor->get_repo_status( $repo['repo_id'] );
			$warn = $repo['warn_out_of_date'] && $this->repo_monitor->repo_out_of_date( $status, $repo['repo_type'] );

			$this->items[] = array_merge( $repo, array(
				'status'     => $status,
				'warn'	     => $warn,
				'can_update' => ! $warn || $this->repo_monitor->can_update_repo( $repo ),
			) );

			$total_items += 1;
		}

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page),
        ) );
    }
}