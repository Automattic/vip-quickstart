<?php

class RepoMonitor extends Dashboard_Plugin {

	const REPO_CPT = 'qs_repo';

	private $repos = null;

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
		
		add_filter( 'cron_schedules', array( $this, 'repo_15_min_cron_interval' ) );
    }
	
	function admin_init() {
		// Add the cron job to check for updates
		if ( ! wp_next_scheduled( 'repomonitor_scan_repos' ) ) {
			wp_schedule_event( time(), 'qs-dashboard-15-min-cron-interval', 'repomonitor_scan_repos' );
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
        wp_add_dashboard_widget( 'quickstart_dashboard_repomonitor', $this->name(), array( $this, 'show' ) );
    }

	function show() {
		?>

		<style>
			.vip-dashboard-repomonitor-table .vip-dashboard-repo-type {
				display: inline-block;
				background: #ececec;
				border: 1px solid #ddd;
				border-radius: 3px;
				padding: 3px;
				font-size: 0.8em;
				margin: 3px;
				width: 20px;
				text-align: center;
				float: right;
			}
			
			.vip-dashboard-repomonitor-table .column-status {
				width: 60%;
			}
        </style>

		<h4><?php _e( 'Monitored Repositories', 'quickstart-dashboard' ); ?></h4>
		<?php
		
		$table = new RepoMonitorWidgetTable( $this );
		$table->prepare_items();
		$table->display();
	}

	function scan_repositories() {
		foreach ( $this->get_repos() as $repo ) {
			// Run the command to determine if it needs an update
			if ( 'svn' == $repo['repo_type'] ) {
				$results = $this->scan_svn_repo( $repo['repo_path'], true );
			} elseif ( 'git' == $repo['repo_type'] ) {
				$results = $this->scan_git_repo( $repo['repo_path'] );
			}

			if ( is_wp_error( $results) ) {
				return;
			}

			// Save the new repo status
			$this->set_repo_status( $repo['repo_id'], $results );
		}
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

	function scan_svn_repo( $repo_path, $allow_interactive = false ) {
		$cwd = getcwd();
		
		// Variables to load output into
		$output = array();
		$return_value = -1;
		
		chdir( $repo_path );

        // Execute info command to get info about local repo
		$command_args = '';
		if ( ! $allow_interactive ) {
			$command_args .= '--non-interactive';
		}
		
        exec( sprintf( 'svn info %s', $command_args ), $output, $return_value );
        
        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn info. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }
        
        $info = $this->parse_svn_info( $output );
        
		$command_args = '-u';
		if ( ! $allow_interactive ) {
			$command_args .= ' --non-interactive';
		}
		
		// Execute status command to get file into
		exec( sprintf( 'svn status %s', $command_args ), $output, $return_value );
        
        if ( 0 != $return_value ) {
            return new WP_Error( 
                $return_value, 
                sprintf( __( 'Error fetching svn status. SVN returned %s', 'quickstart-dashboard' ), $return_value )
            );
        }
		
		// Go back to the previous working directory
		chdir( $cwd );

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

	function add_repo( $args, $add_hooks = true ) {
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
}

if( ! class_exists( 'WP_List_Table' ) ){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class RepoMonitorWidgetTable extends WP_List_Table {
	/**
	 * @var RepoMonitor
	 */
	private $repo_monitor = null;

    function __construct( $repo_monitor ) {
		$this->repo_monitor = $repo_monitor;

        parent::__construct( array(
            'singular'  => 'theme',
            'plural'    => 'themes',
            'ajax'      => false
        ) );
    }

	function get_table_classes() {
		return array( 'widefat', 'fixed', $this->_args['plural'], 'vip-dashboard-repomonitor-table', 'plugins' );
	}

	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? 'alternate' : '' );

		$row_classes = array( $row_class );
		if ( $item['warn'] ) {
			$row_classes[] = 'active update';
		}

		echo '<tr class="' . implode( ' ', $row_classes ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

    function column_default( $item, $column_name ){
		$retval = '';
        switch( $column_name ){
			case 'status':
				$retval = $this->repo_monitor->get_status_text( $item[$column_name], $item['repo_type'] );
				break;
            default:
                $retval = $item[$column_name];
        }

		return $retval;
    }

    function column_repo_friendly_name( $item ){
        //Build row actions
        $actions = array();

        //Return the title contents
		return sprintf( "<strong>%s</strong><span class='vip-dashboard-repo-type'>%s</span>", esc_html( $item['repo_friendly_name'] ), esc_html( $item['repo_type'] ), $this->row_actions( $actions ) );
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
			'status'			 => __( 'Status', 'quickstart-dashboard' ),
        );

        return apply_filters( 'viprepomonitor_table_get_columns', $cols );
    }

    function get_sortable_columns() {
        return apply_filters( 'viprepomonitor_table_get_sortable_columns', array() );
    }

    function get_bulk_actions() {
        return apply_filters( 'viprepomonitor_table_bulk_actions', array() );
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
				'status'    => $status,
				'warn'	    => $warn,
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