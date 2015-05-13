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

add_action( 'init', function() {
	$cli_classes = pmc_wp_cli_classes();
	foreach ( $cli_classes as $class ) {
		if ( file_exists( get_template_directory() . '/cli/class/'. sanitize_file_name( $class ) . '.php' ) ) {
			require_once get_template_directory() . '/cli/class/'. sanitize_file_name( $class ) . '.php';
		}
	}
}, 11 );
