<?php

$minileven = dirname( __DIR__ ) . '/plugins/jetpack/modules/minileven/minileven.php';
if ( file_exists( $minileven ) ) {
	require_once( $minileven );
	// workaround: jetpack check did action before loading module via require
	do_action( 'jetpack_module_loaded_minileven' );
}
