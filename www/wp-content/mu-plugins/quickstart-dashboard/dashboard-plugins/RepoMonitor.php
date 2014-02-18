<?php

class RepoMonitor extends Dashboard_Plugin {

	const REPO_CPT = 'qs_repo';

	private $repos = null;

	function __construct() {
		// Register the dashboard plugin

		// Setup cron
	}

	function name() {
		return __( 'Repo Monitor', 'quickstart-dashboard' );
	}
    
    function init() {
        add_action( 'quickstart_dashboard_setup', array( $this, 'dashboard_setup' ) );
    }

	function dashboard_setup() {
        wp_add_dashboard_widget( 'quickstart_dashboard_repomonitor', $this->name(), array( $this, 'show' ) );
    }

	function show() {
		?>

		<style>
            .vip-dashboard-repo-row {
                display: block;
				margin: 0;
				padding: 5px;
				border: 1px solid #ddd;
            }

			.vip-dashboard-repo-row.vip-dashboard-repo-warn {
				background-color: rgba(255, 0, 0, 0.2);
				color: rgba(255, 0, 0, 0.8);
			}

            .vip-dashboard-repo-row h4 {

			}

			.vip-dashboard-repo-row .vip-dashboard-repo-type {
				display: inline-block;
				background: #ececec;
				border: 1px solid #ddd;
				border-radius: 3px;
				padding: 3px;
				font-size: 0.8em;
				margin: 3px;
			}
        </style>

		<h4><?php _e( 'Monitored Repositories', 'quickstart-dashboard' ); ?></h4>
		<?php 
		foreach ( $this->get_repos() as $repo ):
			$status = $this->get_repo_status( $repo['repo_id'] );
			$warn = $repo['warn_out_of_date'] && $this->repo_out_of_date( $status, $repo['repo_type'] );
			?>
		<div class="vip-dashboard-repo-row <?php echo $warn ? 'vip-dashboard-repo-warn' : '' ?>">
			<h4><?php echo $repo['repo_friendly_name']; ?><span class="vip-dashboard-repo-type"><?php echo $repo['repo_type']; ?></span></h4>
			<span class="vip-dashboard-repo-status"><?php echo $this->get_status_text( $status, $repo['repo_type'] ); ?></span>
		</div>
		<?php endforeach; ?>
		<?php
	}

	function scan_repositories() {
		foreach ( $this->get_repos() as $repo ) {
			// Run the command to determine if it needs an update
			if ( 'svn' == $repo['repo_type'] ) {
				$results = $this->scan_svn_repo( $repo['repo_path'] );
			}
		}

		return 0;
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

	function scan_svn_repo( $repo_path ) {
		// Variables to load output into
		$output = array();
		$return_value = -1;

        // Execute info command to get info about local repo
        exec( 'svn info --non-interactive', $output, $return_value );
        
        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn info. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }
        
        $info = $this->parse_svn_info( $output );
        
		// Execute status command to get file into
		exec( 'svn status -u --non-interactive', $output, $return_value );
        
        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn status. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }

		$status = $status;
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
            if ( !ctype_space( $matches['args'][9] ) ) {
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
			return !empty( $status['files_out_of_date'] ) || $status['remote_revision'] != $status['local_revision'];
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

	function add_repo( $args ) {
		$defaults = array(
			'repo_type'			 => 'git',
			'repo_path'			 => '',
			'repo_friendly_name' => '',
			'warn_out_of_date'   => true,
		);

		$args = array_merge( $defaults, $args );

		// Test that the repo exists by scanning it
		if ( 'svn' == $args['repo_type'] ) {
			$args['repo_status'] = $this->scan_svn_repo( $args['repo_path'] );
		} elseif ( 'git' == $args['repo_type'] ) {
			$args['repo_status'] = $this->scan_git_repo( $args['repo_path'] );
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

		$this->repos[] = $args;

		return $id;
	}
}
