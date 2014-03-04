<?php

class VIPOptionsSync extends Dashboard_Plugin {
	private $action_descriptions = null;

	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function name() {
		return __( 'Options Sync', 'quickstart-dashboard' );
	}

	function admin_menu() {
		add_submenu_page( null, $this->name(), $this->name(), 'manage-options', 'dashboard_options_sync', array( $this, 'show_sync_page' ) );
	}

	function admin_init() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'dashboard_options_sync' && isset( $_REQUEST['action'] ) ) {
			session_start();
		}
	}

	function show_sync_page() {
		if ( !current_user_can( 'manage-options') ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'quickstart-dashboard' ) );
		}

		echo '<div class="wrap"><h2>' . __( 'Options Sync', 'quickstart-dashboard' ) . '</h2>';

		$show_main_ui = true;
		if ( isset( $_REQUEST['action'] ) ) {
			if ( $_REQUEST['action'] == 'import' ) {
				check_admin_referer( 'dashboard-options-sync' );
				$filepath = $_REQUEST['options-sync-path'];
				$preview = ! isset( $_REQUEST['no-preview'] );
				$show_main_ui = false;
				
				if ( $preview ) {
					$this->preview_import_bundle( $filepath );
				} else {
					// Check that the session var checks out, meaning that the user
					// has gone through the correct process to import this file
					if ( empty( $_SESSION['options-sync-path'] ) || ( $_SESSION['options-sync-path'] !== $filepath ) ) {
						wp_nonce_ays( 'dashboard-options-sync' );
						return;
					}

//					unset( $_SESSION['options-sync-path'] );

					$this->import_bundle( $filepath );
				}
			}
		}

		if ( $show_main_ui ) {
			?>
			<form action="<?php menu_page_url( 'dashboard_options_sync' ) ?>" method="POST">
				<?php wp_nonce_field( 'dashboard-options-sync' ); ?>
				<input type="hidden" id="action" name="action" value="import" />

				<table class="form-table">
					<tr>
						<td><label for="options-sync-path">WP.com Export File Path:</label></td>
						<td><input type="text" name="options-sync-path" id="options-sync-path" value="/srv/www/wp-content/uploads/2014/03/03/viptest.wordpress.com.tar.gz" /></td>
					</tr>
				</table>

				<input class="button-primary" type="submit" name="options-sync-import" value="<?php _e( 'Import', 'quickstart-dashboard' ) ?>" />
			</form>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Shows the user what will happen when a bundle is imported.
	 * 
	 * @param string $filepath Path to the dump file
	 */
	function preview_import_bundle( $filepath ) {
		$destination = $this->get_extracted_filename( $filepath );
		
		// Extract the file
		if ( ! $this->is_file_extracted( $filepath ) ) {
			$extract_result = $this->extract_file( $filepath, $destination );

			if ( is_wp_error( $extract_result ) ) {
				?>
				<div class="error"><p><?php echo esc_html( $extract_result->get_error_message() ); ?></p></div>
				<?php
				
				return;
			}
		}

		if ( !$this->is_file_extracted( $filepath ) ) {
			return;
		}

		// List files in dir
		$files = scandir( $destination );
		$table_items = array();

		// Loop over the files in the directory, getting the action that will be performed
		 foreach ( $files as $file ) {
			if ( '.' == $file || '..' == $file ) {
				continue;
			}

			$action = $this->get_file_action( $file );
			$table_items[] = array(
				'data' => array(
					'file'		  => $file,
					'action'	  => $action,
					'description' => $this->get_action_description( $action ),
				),
			);
		}

		// Setup the data table
		$cols = array(
            'file'		  => __( 'File', 'quickstart-dashboard' ),
			'action'	  => __( 'Action', 'quickstart-dashboard' ),
			'description' => __( 'Description', 'quickstart-dashboard' ),
        );

		$os_table = new DashboardDataTable( $cols, $table_items );
		$os_table->prepare_items();

		$_SESSION['options-sync-path'] = esc_attr( $_REQUEST['options-sync-path'] );

		?>
		<h3><?php _e( 'Actions to be Undertaken:', 'quickstart-dashboard' ); ?></h3>
		<?php $os_table->display(); ?>
		<form action="<?php menu_page_url( 'dashboard-options-sync' ); ?>" method="POST">
			<input type="hidden" id="action" name="action" value="import" />
			<input type="hidden" id="no-preview" name="no-preview" value="1" />
			<input type="hidden" name="options-sync-path" id="options-sync-path" value="<?php echo $_SESSION['options-sync-path']; ?>" />
			<?php wp_nonce_field( 'dashboard-options-sync'  ) ?>
			<p>
				<input class="button-primary" type="submit" name="options-sync-import" value="<?php _e( 'Continue Import', 'quickstart-dashboard' ) ?>" />
				<a class="button-secondary" href="<?php menu_page_url( 'vip-dashboard' ) ?>"><?php _e( 'Cancel', 'quickstart-dashboard' ); ?></a>
			</p>
		</form>
		<?php
	}

	function import_bundle( $filepath ) {
		$destination = $this->get_extracted_filename( $filepath );

		// Extract the file
		if ( ! $this->is_file_extracted( $filepath ) ) {
			$extract_result = $this->extract_file( $filepath, $destination );

			if ( is_wp_error( $extract_result ) ) {
				?>
				<div class="error"><p><?php echo esc_html( $extract_result->get_error_message() ); ?></p></div>
				<?php

				return;
			}
		}

		if ( !$this->is_file_extracted( $filepath ) ) {
			return;
		}

		// List files in dir
		$files = scandir( $destination );

		// Import each file
		$results = array();
		foreach ( $files as $file ) {
			if ( '.' == $file || '..' == $file ) {
				continue;
			}

			$result = array(
				'action' => $this->get_file_action( $file ),
				'file'	 => $file,
				'result' => false,
			);

			switch ( $result['action'] ) {
				case 'merge-import':
					$result['result'] = $this->do_merge_import( $destination . $file );
					break;

				case 'destructive-import':
					$result['result'] = $this->do_destructive_import( $destination . $file );
					break;

				case 'skip':
				default:
					$result['action'] = 'skip';
					$result['result'] = true;
					break;
			}

			$results[] = $result;
		}

		// Summarize the results in a table
		$errors_occured = false;
		$cols = array(
			'file' => __( 'File', 'quickstart-dashboard' ),
			'action' => __( 'Action', 'quickstart-dashboard' ),
			'result' => __( 'Result', 'quickstart-dashboard' ),
		);

		// Prepare data for the table
		$table_results = array();
		foreach ( $results as $result ) {
			if ( $result['result'] !== true ) {
				$errors_occured = true;
			}

			// Get the error text if this was an error
			$success = false;
			if ( is_wp_error( $result['result'] ) ) {
				$result['result'] = $result['result'] ->get_error_message();
			} elseif ( true === $result['result'] ) {
				$success = true;
				$result['result'] = __( 'Action succeeded.', 'quickstart-dashboard' );
			}

			$table_results[] = array(
				'warn'   => ! $success,
				'active' => $result['action'] !== 'skip' && $success,
				'data'   => $result,
			);
		}

		$table = new DashboardDataTable( $cols, $table_results );
		$table->show_check_column( false );
		$table->disable_output_escaping();
		$table->prepare_items();

		?>
		<h2><?php $errors_occured ? _e( 'Sync Failed', 'quickstart-dashboard' ) : _e( 'Sync Succeeded', 'quickstart-dashboard' ); ?></h2>
		<?php $table->display(); ?>
		<p><a class="button-primary" href="<?php menu_page_url( 'vip-dashboard' ); ?>"><?php _e( 'Return to Dashboard', 'quickstart-dashboard' ); ?></a></p>
		<?php
	}

	/**
	 * Does a merge import of the file. This means that the SQL in the table is
	 * executed, generating a new table with the name of the file.
	 *
	 * We then copy the rows from the new table into the corresponding WP table.
	 * If there's a row with the same ID or key, overwrite it.
	 *
	 * After the merge is complete, we delete the old table.
	 *
	 * @param type $file
	 */
	function do_merge_import( $file ) {
		global $wpdb;
		$sql = file_get_contents( $file );

		if ( false === $sql ) {
			return new WP_Error( 1, sprintf( __( 'An error occured reading the merge source file %s.', 'quickstart-dashboard' ), esc_attr( $file ) ) );
		}

		// Iterate over each line, removing comments
		$query = array();
		$query_lines = explode( "\n", $sql );
		foreach ( $query_lines as $line ) {
			// Does this line look like a comment or is it empty?
			if ( empty( $line ) || preg_match( '/\/\*.*\*\/;?$/', $line ) ) {
				// Skip this line, its a comment
				continue;
			}

			$query[] = $line;

			// Does the line end in a semicolon? If not its not the end of the statement
			if ( substr( trim( $line ) , -1 ) !== ';' ) {
				continue;
			}

			// Pick stuff up, put in mouth
			if ( false === $wpdb->query( implode( "\n", $query ) ) ) {
				return new WP_Error( 2, sprintf( __( 'The database query failed on line %s while executing <p><code>%s</code></p> The result was: <p>%s</p>', 'quickstart-dashboard' ), $line_no, $line, $wpdb->last_error ) );
			}

			// Reset the query
			$query = array();
		}

		return true;
	}

	function do_destructive_import( $file ) {
		return true;
	}

	/**
	 * Returns the action that should be taken for a file in a GT dump.
	 *
	 * @param string $file The filename
	 * @return string The action to perform
	 */
	function get_file_action( $file ) {
		$match = preg_match( '/wp_\d+_(?<tablename>\w+)\.sql$/', $file, $matches );
		if ( !$match || empty( $matches['tablename'] ) ) {
			return 'skip';
		}

		switch ( $matches['tablename'] ) {
			case 'options':
				return 'merge-import';

			case 'users':
			case 'usermeta':
				return 'skip';

			default:
				return 'destructive-import';
		}
	}

	/**
	 * Gets the human-readable description for an action.
	 *
	 * @param string $action The action
	 * @return string The action description
	 */
	function get_action_description( $action ) {
		if ( is_null( $this->action_descriptions ) ) {
			$this->get_action_descriptions();
		}

		if ( isset( $this->action_descriptions[$action] ) ) {
			return $this->action_descriptions[$action];
		}

		return '';
	}

	function get_action_descriptions() {
		$this->action_descriptions = apply_filters( 'vipoptionssync_action_descriptions', array(
			'merge-import'		 => __( 'This file will be merged. Settings in this file will overwrite settings in the existing table.', 'quickstart-dashboard' ),
			'destructive-import' => __( 'This file will be merged. Any content in the existing table will be list.', 'quickstart-dashboard' ),
			'skip'				 => __( 'This file will not be merged.', 'quickstart-dashboard' ),
		) );

		return $this->action_descriptions;
	}

	private function is_file_extracted( $filepath ) {
		$directory = $this->get_extracted_filename( $filepath );
		return file_exists( $directory ) && is_dir( $directory );
	}

	private function get_extracted_filename( $filepath ) {
		return preg_replace( '/(\.zip|\.tar\.gz)$/', '/', $filepath );
	}

	private function extract_file( $filepath, $destination = null ) {
		if ( is_null( $destination ) ) {
			$destination = $this->get_extracted_filename( $filepath );
		}

		// Check that the source file exists
		if ( ! file_exists( $filepath ) ) {
			return new WP_Error( 1, __( 'Source file does not exist', 'quickstart-dashboard' ) );
		} elseif ( ! is_readable( $filepath ) ) {
			return new WP_Error( 2, __( 'Source file is not readable', 'quickstart-dashboard' ) );
		}

		// If the destination file/directory exists, remove it
		if ( file_exists( $destination ) ) {
			// Delete the destination
			$delete_result = false;
			if ( is_file( $destination ) ) {
				$delete_result = unlink( $destination );
			} elseif ( is_dir( $destination ) ) {
				$delete_result = $this->remove_directory( $destination );
			}
			
			if ( ! $delete_result ) {
				return new WP_Error( 3, __( 'Destination exists and could not be removed. Please delete it manually and try again.', 'quickstart-dashboard' ) );
			}
		}

		// Create the destination directory
		mkdir( $destination );

		// Extract the file
		exec( sprintf( 'tar -xvf %s -C %s', escapeshellarg( $filepath ), escapeshellarg( $destination ) ), $output, $return_value );

		if ( 0 !== $return_value ) {
			return new WP_Error( $return_value, sprintf( __( 'Error: Extraction failed with output: %s', 'quickstart-dashboard' ), implode( "\n", $output ) ) );
		}

		return true;
	}

	private function remove_directory( $directory ) {
		foreach ( glob( $directory . '/*' ) as $file ) {
			if ( is_dir( $file ) ) {
				rrmdir( $file );
			} else {
				unlink( $file );
			}
		}

		rmdir( $dir );
	}
}