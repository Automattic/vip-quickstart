<?php

// adjust pmc adm host targetting to corresponding live site for domain name *.vip.local
add_filter( 'pmc_adm_hostname', function() {
	$parts = explode( '.', $_SERVER['HTTP_HOST'] );	
	switch ( end( $parts ) ) {
		case 'local':
			return reset( $parts ) .'.com';
			break;
	}
	return $_SERVER['HTTP_HOST'];
} );
