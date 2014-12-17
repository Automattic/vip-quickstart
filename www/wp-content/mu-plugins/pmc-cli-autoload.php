<?php
if ( !defined('WP_CLI') || ! WP_CLI ) {
	return;
}

if ( !function_exists( 'pmc_wp_cli_classes' ) ) {
	function pmc_wp_cli_classes( $cli_classes = array() ) {

		// Auto load all wp cli commands from class folder
		$files = glob( get_template_directory() . '/cli/class/class-*wp-cli*.php' );

		foreach ( $files as $fn ) {
			$cli_classes[] = pathinfo( $fn, PATHINFO_FILENAME );
		}
		
		return array_unique( $cli_classes );
	}
}
