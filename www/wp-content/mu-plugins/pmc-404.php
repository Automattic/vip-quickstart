<?php

// Throw generic 404 message for images, css, js files to avoid wp from processing 404 page.
$ext = pathinfo( $_SERVER['REQUEST_URI'], PATHINFO_EXTENSION );
if ( in_array( $ext, array('jpeg','jpg','png','gif','css','js','html') ) ) {
	header("HTTP/1.0 404 Not Found");
	die('File not found');
}
