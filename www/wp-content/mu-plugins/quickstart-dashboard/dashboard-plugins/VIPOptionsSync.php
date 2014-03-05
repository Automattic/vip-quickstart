<?php

class VIPOptionsSync extends Dashboard_Plugin {
	private $action_descriptions = null;
	
	/**
	 * This object describes the WordPress relational database. Specifically it
	 * describes foreign key relationships within the database.
	 * 
	 * It is in the form:
	 *	database_table => ( column_depends_on => ( table_column_depends_on ) )
	 * 
	 * It is assumed that when the dependency is on the primary key of the table
	 * being referred to. Ie: 'wp_postmeta'	=> array( 'post_id' => array( 'wp_posts' ) ),
	 * implies that the 'post_id' column in 'wp_postmeta' depends on the primary key
	 * of 'wp_posts.
	 * 
	 * @var array
	 */
	private $table_dependencies = array(
		'posts'				 => array(),
		'users'				 => array(),
		'terms'				 => array(),
		'links'				 => array(),
		'options'			 => array(),
		'postmeta'			 => array( 'post_id' => array( 'posts' ) ),
		'comments'			 => array( 'comment_post_id' => array( 'posts' ), 'user_id' => array( 'users' ) ),
		'commentmeta'		 => array( 'comment_id' => array( 'comments' ) ),
		'usermeta'			 => array( 'user_id' => array( 'users' ) ),
		'term_taxonomy'		 => array( 'term_id' => array( 'terms' ) ),
		'term_relationships' => array( 'term_taxonomy_id' => array( 'term_taxonomy' ), 'object_id' => array( 'posts', 'links' ) ),
	);
	
	/**
	 * Lists the primary key of each table in the DB.
	 * @var array
	 */
	private $table_primary_keys = array(
		'posts'				 => 'ID',
		'users'				 => 'ID',
		'terms'				 => 'term_id',
		'links'				 => 'link_id',
		'options'			 => 'option_name',
		'postmeta'			 => 'meta_id',
		'comments'			 => 'comment_ID',
		'commentmeta'		 => 'meta_id',
		'usermeta'			 => 'umeta_id',
		'term_taxonomy'		 => 'term_taxonomy_id',
		'term_relationships' => 'object_id',
	);

	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
	
	function admin_enqueue_scripts() {
		if ( isset( $_REQUEST['page'] ) && 'dashboard_options_sync' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'options_sync_js', get_bloginfo( 'wpurl' ) . '/wp-content/mu-plugins/quickstart-dashboard/js/options_sync.js', array( 'jquery' ) );
			wp_localize_script( 'options_sync_js', 'options_sync_settings', array(
				'action_descriptions' => $this->get_action_descriptions(),
				'table_dependencies'  => $this->table_dependencies,
				'translations'		  => array(
					'table_dependency_conflict' => __( 'This table is dependant on the {other_table} table which has the more restrictive {other_table_action} merge action applied to it. This could cause unexpected results and errors.', 'quickstart-dashboard' ),
					'table_dependency_skipped'  => __( 'This table is dependant on the {other_table} table which is being skipped, so this table must be skipped as well.', 'quickstart-dashboard' ),
				),
			) );
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

			$action = $this->get_file_action( $file, false );
			$table_items[] = array(
				'data' => array(
					'file'		  => $file,
					'action'	  => $this->generate_action_select_box( $file, $action ),
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
		$os_table->disable_output_escaping();
		$os_table->prepare_items();

		$_SESSION['options-sync-path'] = esc_attr( $_REQUEST['options-sync-path'] );

		?>
		<h3><?php _e( 'Actions to be Undertaken:', 'quickstart-dashboard' ); ?></h3>
		<form action="<?php menu_page_url( 'dashboard-options-sync' ); ?>" method="POST">
			<input type="hidden" id="action" name="action" value="import" />
			<input type="hidden" id="no-preview" name="no-preview" value="1" />
			<input type="hidden" name="options-sync-path" id="options-sync-path" value="<?php echo $_SESSION['options-sync-path']; ?>" />
			<?php wp_nonce_field( 'dashboard-options-sync'  ) ?>
			<?php $os_table->display(); ?>
			<p>
				<input class="button-primary" type="submit" name="options-sync-import" value="<?php _e( 'Continue Import', 'quickstart-dashboard' ) ?>" />
				<a class="button-secondary" href="<?php menu_page_url( 'vip-dashboard' ) ?>"><?php _e( 'Cancel', 'quickstart-dashboard' ); ?></a>
			</p>
		</form>
		<?php
	}
	
	private function generate_action_select_box( $file, $file_action ) {
		$action_descriptions = $this->get_action_descriptions();
		
		$actions_str = '';
		foreach ( $action_descriptions as $action => $description ) {
			$text = $action;
			
			switch ( $action ) {
				case 'merge-import':
					$text = __( 'Merge Import', 'quickstart-dashboard' );
					break;
				
				case 'destructive-import':
					$text = __( 'Destructive Import', 'quickstart-dashboard' );
					break;
				
				case 'skip':
					$text = __( 'Skip', 'quickstart-dashboard' );
					break;
				
				default:
			}
			
			$actions_str .= sprintf( 
				'<option value="%s"%s>%s</option>',
				esc_attr( $action ),
				$action == $file_action ? ' selected="selected"' : '',
				esc_attr( $text )
			);
		}
		
		return sprintf( '<select id="%1$s" name="%1$s" class="options-action-select">%2$s</select><div class="actiong-warning-explanation"></div>', esc_attr( 'options-action-select-' . $this->get_file_table( $file ) ), $actions_str );
	}

	/**
	 * Imports the GT bundle given by $filepath.
	 * 
	 * @global WPDB $wpdb
	 * @param string $filepath The path to the GT bundle.
	 */
	function import_bundle( $filepath ) {
		global $wpdb;
		
		set_time_limit( 0 );
		
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
		unset( $files[0] ); // '.'
		unset( $files[1] ); // '..'
		
		$import_table_prefix = $this->compute_table_prefix( $files );

		// Import each file
		$results = array();
		foreach ( $files as $file ) {
			if ( '.' == $file || '..' == $file ) {
				continue;
			}

			$result = array(
				'table'	 => str_replace( $import_table_prefix, '', $this->get_file_table( $file ) ),
				'action' => $this->get_file_action( $file ),
				'file'	 => $file,
				'import' => false,
				'merge'  => null,
			);

			switch ( $result['action'] ) {
				case 'merge-import':
				case 'destructive-import':
					$result['import'] = $this->import_sql_file( $destination . $file );
					break;

				case 'skip':
				default:
					$result['action'] = 'skip';
					$result['import'] = true;
					break;
			}

			$results[$result['table']] = $result;
		}
		
		// Now merge each file. We need to respect the fact that some tables
		// depend on others, so we need to do this in an order such that each table
		// with a dependant is merged before the dependant table. We then keep
		// tract of the list of IDs that have been merged so that if a row in the
		// parent table was skipped, the child rows in the dependant table are also
		// skipped.
		
		// Merged data contains the list of data that has been inserted into the DB.
		// It is in the format 'tablename => array of IDS'
		$merged_data = array();
		$unmerged_tables = $this->table_dependencies;
		while ( ! empty( $unmerged_tables ) ) {
			foreach ( $unmerged_tables as $table => $deps ) {
				// Check that the import for this table succeeded
				if ( true !== $results[$table]['import'] ) {
					$merged_data[$table] = array();
					continue;
				}
				
				// Check if this table's dependencies are satisfied
				if ( ! empty( $deps ) ) {
					foreach ( $deps as $dep_col => $dep_tables ) {
						foreach ( $dep_tables as $tablename ) {
							if ( 'skip' == $results[$tablename]['action'] ) {
								// This table has a dependency whose import was skipped, skip this one too.
								$results[$table]['action'] = 'forced-skip';
								break 2;
							}
							
							if ( !array_key_exists( $tablename, $merged_data ) ) {
								// This tables' dependencies aren't satisfied
								continue 3;
							}
						}
					}
				}
				
				// Table dependencies appear to be satisfied. Do the merge.
				switch ( $results[$table]['action'] ) {
					case 'merge-import':
						$results[$table]['merge'] = $this->do_merge_import( $table, $import_table_prefix . $table, $wpdb->prefix . $table, $merged_data, false );
						break;

					case 'destructive-import':
						$results[$table]['merge'] = $this->do_destructive_import( $table, $import_table_prefix . $table, $wpdb->prefix . $table, $merged_data );
						break;

					case 'skip':
					default:
						// Fake adding data for dependency resolution
						$merged_data[$table] = array();
						$results[$table]['merge'] = true;
						break;
				}
			}

			// Mark the tables that have been merged as such
			foreach ( $merged_data as $merged_table => $ids ) {
				if ( isset( $unmerged_tables[$merged_table] ) ) {
					unset( $unmerged_tables[$merged_table] );
				}
			}
		}

		// Summarize the results in a table
		$errors_occured = false;
		$cols = array(
			'file' => __( 'File', 'quickstart-dashboard' ),
			'action' => __( 'Action', 'quickstart-dashboard' ),
			'result_text' => __( 'Result', 'quickstart-dashboard' ),
		);

		// Prepare data for the table
		$table_results = array();
		foreach ( $results as $result ) {
			if ( $result['import'] !== true ) {
				$errors_occured = true;
			}

			$success = false;
			if ( 'skip' == $result['action'] ) { 
				$result['result_text'] = __( 'Skipped.', 'quickstart-dashboard' );
				$success = true;
				
			} elseif ( 'forced-skip' == $result['action'] ) {
				$result['result_text'] = __( 'Skipped due to a skipped dependency.', 'quickstart-dashboard' );
				
			} else {
				// Get the error text if this was an error
				$import_success = false;
				if ( is_wp_error( $result['import'] ) ) {
					$result['import'] = __( 'Import failed: ', 'quickstart-dashboard' ) . $result['import']->get_error_message();
				} elseif ( true === $result['import'] ) {
					$import_success = true;
					$result['import'] = __( 'Import succeeded.', 'quickstart-dashboard' );
				} else {
					$result['import'] = __( 'Import failed.', 'quickstart-dashboard' );
				}

				$merge_success = false;
				if ( is_wp_error( $result['merge'] ) ) {
					$result['merge'] = __( 'Merge failed: ', 'quickstart-dashboard' ) . $result['merge']->get_error_message();
				} elseif ( true === $result['merge'] ) {
					$merge_success = true;
					$result['merge'] = __( 'Merge succeeded.', 'quickstart-dashboard' );
				} elseif ( false === $result['merge'] ) {
					$result['merge'] = __( 'Merge failed.', 'quickstart-dashboard' );
				} else {
					$result['merge'] = __( 'Merge not run.', 'quickstart-dashboard' );
				}

				$result['result_text'] = $result['import'] . ' ' . $result['merge'];
				$success = $import_success && $merge_success;
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
	 * Executes the SQL in a SQL file against the database, ensuring that each
	 * query succeeds.
	 * 
	 * @global WPDB $wpdb
	 * @param string $file The path to the sql file to import.
	 * @return boolean|\WP_Error True on success of WP_Error on failure.
	 */
	function import_sql_file( $file ) {
		global $wpdb;
		$sql = file_get_contents( $file );

		if ( false === $sql ) {
			return new WP_Error( 1, sprintf( __( 'An error occured reading the source file %s.', 'quickstart-dashboard' ), esc_attr( $file ) ) );
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

	/**
	 * Copies the rows from a freshly imported database table into the quickstart 
	 * table.
	 *
	 * After the merge is complete, we delete the old table.
	 * 
	 * @global WPDB $wpdb
	 * @param string $base_tbl_name The root tablename (without prefix) of the table to import. Ie: 'posts'
	 * @param string $src_table The source tablename of the table to import from.
	 * @param string $dest_table The destination tablename.
	 * @param array $merged_data The array of already merged data. See `import_bundle()`
	 * @param boolean $overwrite Whether to overwrite the existing value in the destination when there is a key conflict.
	 * @return boolean|\WP_Error True on success or WP_Error on failure.
	 */
	function do_merge_import( $base_tbl_name, $src_table, $dest_table, &$merged_data, $overwrite = true ) {
		global $wpdb;

		$table_pk = $this->table_primary_keys[$base_tbl_name];

		$src_table_escaped = esc_sql( $src_table );
		$dest_table_escaped = esc_sql( $dest_table );
		$table_pk_escaped = esc_sql( $table_pk );
		
		// Get the importable IDs
		$import_ids = $this->get_importable_ids( $base_tbl_name, $src_table, $dest_table, $merged_data, $overwrite );
		
		$error = null;
		if ( ! empty( $wpdb->last_error ) ) {
			// Mark that no IDs will be imported due to the error
			$import_ids = array();
			$error = new WP_Error( 1, sprintf( __( 'An error occured fetching data from the source table for merge: %s', 'quickstart-dashboard' ), $wpdb->last_error ) );
		}
		
		if ( ! empty( $import_ids ) ) {
			$ids_str = $this->prepare_sql_in_statement( $import_ids );

			// Get ready to insert data by deleting any conflicting rows in the destination DB
			$wpdb->query( sprintf( "DELETE FROM `$dest_table_escaped` WHERE `$dest_table_escaped`.`$table_pk_escaped` IN (%s)", $ids_str ) );

			if ( empty( $wpdb->last_error ) ) {
				// Get the column list (made up of columns common to both tables)
				$dest_cols = array_intersect( $this->get_table_cols( $dest_table ), $this->get_table_cols( $src_table ));
				$insert_cols = array();
				foreach ( $dest_cols as $dest_col ) {
					$insert_cols[] = esc_sql( $dest_col );
				}
				
				// Insert the data from the source table
				$sql = sprintf( "INSERT INTO `$dest_table_escaped` (%1\$s) SELECT DISTINCT %1\$s FROM `$src_table_escaped` WHERE `$src_table_escaped`.`$table_pk_escaped` IN (%2\$s)", implode( ',', $insert_cols ), $ids_str );
				
				// Add the ON DUPLICATE KEY handling
				if ( $overwrite ) {
					if ( ! empty( $dest_cols ) ) {
						// Remove key columns
						$key_cols = array_merge( array( $table_pk ), array_keys( $this->table_dependencies[$base_tbl_name] ) );
						foreach ( $key_cols as $key ) {
							$position = array_search( $key, $dest_cols );
							if ( false !== $position ) {
								unset( $dest_cols[$position] );
							}
						}

						// Add the update clauses. Dest_col already escaped.
						$clauses = array();
						foreach ( $insert_cols as $dest_col ) {
							$clauses[] = "`$dest_table_escaped`.`$dest_col`=`$src_table_escaped`.`$dest_col`";
						}

						// Add the on update clause if we have things to say
						if ( !empty( $clauses ) ) {
							$sql .= ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $clauses );
						}
					}
				} else {
					$sql .= " ON DUPLICATE KEY UPDATE `$dest_table_escaped`.`$table_pk_escaped`=`$src_table_escaped`.`$table_pk_escaped`";
				}

				$wpdb->query( $sql );
			}
		}

		if ( ! empty( $wpdb->last_error ) ) {
			$import_ids = array();
			$error = new WP_Error( 1, sprintf( __( 'A database error occured during merge: %s', 'quickstart-dashboard' ), $wpdb->last_error ) );
		}

		// Finally, drop the source table. Do this even if errors have occured to clean up.
		$wpdb->query( sprintf( "DROP TABLE `$src_table_escaped`;" ) );

		// Mark which data was imported
		$merged_data[$base_tbl_name] = $import_ids;
		
		if ( !is_null( $error ) ) {
			return $error;
		}
		
		return true;
	}
	
	/**
	 * Completely replaces the $dest_table with the $src_table.
	 * 
	 * @global WPDB $wpdb
	 * @param string $base_tbl_name The root tablename (without prefix) of the table to import. Ie: 'posts'
	 * @param string $src_table The source tablename of the table to import from.
	 * @param string $dest_table The destination tablename.
	 * @param array $merged_data The array of already merged data. See `import_bundle()`
	 * @param boolean $force Whether or not to force the operation. Stops the sanity check from happening.
	 * @return boolean|\WP_Error True on success or WP_Error on failure.
	 */
	function do_destructive_import( $base_tbl_name, $src_table, $dest_table, &$merged_data, $force = false ) {
		global $wpdb;
		
		// Make sure that this replacement is sane. Ie: they should at least share some columns.
		if ( ! $force ) {
			$shared_tables = array_intersect( $this->get_table_cols( $dest_table ), $this->get_table_cols( $src_table ) );
			if ( empty( $shared_tables ) ) {
				return new WP_Error( 1, sprintf( 
						__( 'Cannot perform destructive import because %s is not a sane replacement for %s. Set $force=true if you would really like to do this.', 'quickstart-dashboard' ),
						esc_attr( $src_table ),
						esc_attr( $dest_table )
					) 
				);
			}
		}
		
		$table_pk = $this->table_primary_keys[$base_tbl_name];

		$src_table_escaped = esc_sql( $src_table );
		$dest_table_escaped = esc_sql( $dest_table );
		$table_pk_escaped = esc_sql( $table_pk );
		
		// Get the importable IDs
		$merged_data[$base_tbl_name] = array();
		if ( ! empty( $this->table_dependencies[$base_tbl_name] ) ) {
			$merged_data[$base_tbl_name] = $this->get_importable_ids( $base_tbl_name, $src_table, $dest_table, $merged_data, true );
		}
		
		// Assemble the queries to be executed
		$queries = array(
			'START TRANSACTION;',
			
			// Drop the destination
			"DROP TABLE `$dest_table_escaped`;",
			
			// Rename the source
			"RENAME TABLE `$src_table_escaped` TO `$dest_table_escaped`;",
		);
		
		// If this table has dependencies, delete all the rows that do not have satisfied dependencies
		if ( ! empty( $this->table_dependencies[$base_tbl_name] ) ) {
			if ( empty( $merged_data[$base_tbl_name] ) ) {
				$queries[] = "DELETE FROM `$dest_table_escaped`";
			} else {
				$queries[] = sprintf( "DELETE FROM `$dest_table_escaped` WHERE `$dest_table_escaped`.`$table_pk_escaped` NOT IN (%s);", $this->prepare_sql_in_statement( $merged_data[$base_tbl_name] ) );
			}
		}
		
		// Execute each query. Roll back in case of error.
		foreach ( $queries as $query ) {
			if ( false === $wpdb->query( $query ) ) {
				$error = new WP_Error( 1, sprintf( __( 'Executing the query failed. The error was: %s', 'quickstart-dashboard' ), $wpdb->last_error ) );

				$wpdb->query( 'ROLLBACK;' );

				return $error;
			}
		}
		
		$wpdb->query( 'COMMIT' );
		
		// Select the ids that were inserted if we don't already have a list.
		if ( empty( $merged_data[$base_tbl_name] ) ) {
			$merged_data[$base_tbl_name] = $wpdb->get_col( "SELECT `$dest_table_escaped`.`$table_pk_escaped` FROM `$dest_table_escaped`;" );
		}
		
		return true;
	}
	
	/**
	 * Gets a list of IDs from the $src_table that may be safely imported because
	 * their primary and foreign key restraints are satisfied.
	 * 
	 * @global WPDB $wpdb
	 * @param string $base_tbl_name The root tablename (without prefix) of the table to import. Ie: 'posts'
	 * @param string $src_table The source tablename of the table to import from.
	 * @param string $dest_table The destination tablename.
	 * @param array $merged_data The array of already merged data. See `import_bundle()`
	 * @param boolean $overwrite Whether to overwrite the existing value in the destination when there is a key conflict.
	 * @return array The list of IDs from $src_table that may be safely imported
	 */
	function get_importable_ids( $base_tbl_name, $src_table, $dest_table, $merged_data, $overwrite ) {
		global $wpdb;
		
		$table_pk = $this->table_primary_keys[$base_tbl_name];
		$src_table_escaped = esc_sql( $src_table );
		$dest_table_escaped = esc_sql( $dest_table );
		$table_pk_escaped = esc_sql( $table_pk );
		
		// Get a list of IDs to be merged. Can't use $wpdb->prepare() because we need column names
		$key_cols = array_merge( array( $table_pk ), array_keys( $this->table_dependencies[$base_tbl_name] ) );

		// Escape colmn names
		foreach ( $key_cols as $index => $col ) {
			$key_cols[$index] = "`$src_table_escaped`.`" . esc_sql( $col ) . '`';
		}

		$query = sprintf( "SELECT %s FROM `$src_table_escaped`", implode( ',', $key_cols ) );
		
		// If we aren't supposed to overwrite, add that clause in.
		if ( ! $overwrite ) {
			$query .= " WHERE `$src_table_escaped`.`$table_pk_escaped` NOT IN ( SELECT `$dest_table_escaped`.`$table_pk_escaped` FROM `$dest_table_escaped` )";
		}

		// Get the rows for which dependencies are satisfied
		$import_ids = array();
		if ( ! empty( $this->table_dependencies[$base_tbl_name] ) ) {
			$source_rows = $wpdb->get_results( $query, ARRAY_A );
			
			foreach ( $source_rows as $row ) {
				foreach ( $row as $colname => $col ) {
					// The pk cannot have a dependency
					if ( $colname === $table_pk ) {
						continue;
					}

					// Check that this ID is not zero. Zero IDs are used when there is not a foreign key
					if ( '0' === $col ) {
						continue;
					}

					// Check that this dependency is filled
					$dep_tables = $this->table_dependencies[$base_tbl_name][$colname];

					foreach ( $dep_tables as $table ) {
						if ( ! in_array( $col, $merged_data[$table] ) ) {
							// This dependency is not satisfied. Break out of this row
							continue 3;
						}
					}
				}
				
				// This rows' dependencies are satisfied, add it to the list of import IDs
				$import_ids[] = $row[$table_pk];
			}
		} else {
			// This table has no dependencies, insert all IDs from the DB
			return $wpdb->get_col( $query );
		}
		
		return $import_ids;
	}
	
	/**
	 * Gets the columns for the given database table.
	 * 
	 * @global WPDB $wpdb
	 * @param string $table The name of the table to fetch columns for.
	 * @return array An array of tablenames inside the table.
	 */
	function get_table_cols( $table ) {
		global $wpdb;

		$results = $wpdb->get_col( 'SHOW COLUMNS FROM `' . esc_sql( $table ) . '`' );

		return $results;
	}
	
	/*
	 * Creates an escape list of values to place inside of a SQL "IN" clause.
	 * 
	 * Assumes that all data in the array are of the same type.
	 * 
	 * @global type $wpdb
	 * @param array $array The array of values to prepare
	 * @return string The prepared list.
	 */
	private function prepare_sql_in_statement( $array ) {
		global $wpdb;
		if ( empty( $array ) ) {
			return '';
		}
		
		$digit_str = '%s';
		if ( is_numeric( $array[0] ) ) {
			$digit_str = '%d';
		}
		
		$base_str = implode(',', array_fill( 0, count( $array ), $digit_str ) );
		
		return $wpdb->prepare( $base_str, $array );
	}
	
	/**
	 * Takes a list of files and computes the table prefix for the tables that
	 * the file will generate.
	 * 
	 * Worst case running time is roughly O( n*(m-1) ) where n is the length of 
	 * the shortest string in $files and m is the number of elements in $files.
	 * 
	 * @param array $files The list of files from scandir()
	 * @return string|boolean The path prefix on success of false if $files is empty
	 */
	function compute_table_prefix( $files ) {
		if ( empty( $files ) ) {
			return false;
		}

		$common = array();

		// Set the prefix to the first file
		$base = array_pop( $files );

		foreach ( str_split( $base ) as $index => $chr ) {
			foreach ( $files as $file ) {
				if ( $file[$index] !== $chr ) {
					return implode( '', $common );
				}
			}
			
			$common[] = $chr;
		}

		return implode( '', $common );
	}

	/**
	 * Returns the action that should be taken for a file in a GT dump.
	 *
	 * @param string $file The filename
	 * @param boolean $check_request Whether to check the request to see if this action has been set
	 * @return string The action to perform
	 */
	function get_file_action( $file, $check_request = true ) {
		$match = $this->get_file_table( $file );
		if ( false === $match || empty( $match ) ) {
			return 'skip';
		}
		
		// Check if this action has been set in the request.
		if ( $check_request && isset( $_REQUEST["options-action-select-$match"] ) ) {
			$action_descriptions = $this->get_action_descriptions();
			
			if ( array_key_exists( $_REQUEST["options-action-select-$match"], $action_descriptions ) ) {
				return $_REQUEST["options-action-select-$match"];
			}
		}

		switch ( $match ) {
			case 'users':
			case 'usermeta':
			case 'options':
				return 'merge-import';

			default:
				return 'destructive-import';
		}
	}
	
	/**
	 * Computes what the affected tablename is for a GT dump file.
	 * 
	 * The file is expected to be of the format "wp_xxxxx_posts.sql" where "posts"
	 * would be the tablename returned. The number of numeric digits does not matter.
	 * 
	 * @param string $file The name of the file
	 * @return string|boolean The tablename on success or false if the filename is not in the expected format.
	 */
	function get_file_table( $file ) {
		if ( preg_match( '/wp_\d+_(?<tablename>\w+)\.sql$/', $file, $matches ) ) {
			return $matches['tablename'];
		}
		
		return false;
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
			'merge-import'		 => __( 'This file will be merged. Content in this file will be merged with the existing database, but existing entries will not be overwritten.', 'quickstart-dashboard' ),
			'destructive-import' => __( 'This file will be merged. Any content in the existing table will be lost.', 'quickstart-dashboard' ),
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