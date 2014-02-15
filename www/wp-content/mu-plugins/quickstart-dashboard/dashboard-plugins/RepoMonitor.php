<?php

class RepoMonitor extends Dashboard_Plugin {

	const REPO_CPT = 'qs_repo';

	private $repos = null;

	function __construct() {
		// Register the dashboard plugin

		// Setup cron
	}

	function name() {
		return 'Repo Monitor';
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

		// Execute command
		exec( 'svn status -u', $output, $return_value);

		// Check return value
		echo "	SVN command returned $return_value\n";

		// Echo output
		print_r( $output );

		return array();
	}

	function scan_git_repo( $repo_path ) {
		$cwd = getcwd();

		// Variables to load output into
		$output = array();
		$return_value = -1;

		// Go to repository directory
		chdir( $repo_path );

		// Start by updating remotes
		exec( 'git remote update origin' );

		// Now check the repo status
		exec( 'git status -u no', $output, $return_value );

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

		return $this->parse_git_status_text( $status );
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
     * @return string The textual representation of the repo status
     */
    function get_status_text( $status ) {
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
        }
        
        return $text;
    }

	function add_repo( $args ) {
		$defaults = array(
			'repo_type'			 => 'git',
			'repo_path'			 => '',
			'repo_friendly_name' => '',
			'warn_out_of_date'   => true,
		);

		$args = array_merge( $defaults, $args );

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

		$this->repos[] = $args;

		return $id;
	}
}
