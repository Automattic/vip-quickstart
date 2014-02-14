#!/usr/local/bin/php
<?php

function die_with_error( $message ) {
	global $file;
	echo "Error in file: " . $file . PHP_EOL;
	echo $message;
	exit( $exitcode );
}

if ( isset( $_SERVER['argv'][1] ) && $_SERVER['argv'][1] ) {
	$file = realpath( $_SERVER['argv'][1] );
	if ( !$file )
		die("what now?\r\n");
	// empty files are ok... i guess
	if ( !filesize( $file ) )
		die();
	$fp = fopen( $file, 'r' );
	$first_byte = fread( $fp, 1 );
	fclose($fp);
	$data = file_get_contents( $file );
} else {
	die("Usage: " . basename( __FILE__ ) . " /path/to/file" . PHP_EOL );
}

switch ( ord($first_byte) ) {
	case 0:
	case 255:
	case 254:
	case 187:
	case 191:
		die_with_error( "--> Found Byte Order Mark -- http://unicode.org/faq/utf_bom.html#BOM" . PHP_EOL );
}

$tokens = token_get_all( $data );

$token_1 = array_pop( $tokens ); // Get the very last php token
$token_2 = array_pop( $tokens ); // And the second to last php token

unset($tokens);

// Make sure there's no whitespace before the opening <?php tag by comparing the trimmed file contents to the untrimmed.
// If trimmed starts with <?php and untrimmed does not, there are preceding whitespace characters. There's no match if non-whitespace
// characters appear before the <?php, so this is safe for themes that have markup before opening <?php tags.
if ( '<?php' == substr( trim( $data ), 0, 5 ) && '<?php' !== substr( $data, 0, 5 ) )
	die_with_error( 2, "--> File did not begin with a php opening tag." . PHP_EOL );

// if the last token is a string, like "}" then we're still in code and thats ok...
if ( is_string( $token_1 ) )
	die();

// if the last token is a php closing tag then thats good
if ( token_name( $token_1[0] ) == "T_CLOSE_TAG" )
	die();

// if the last token isnt outside the interpreted code... then we havent closed the php tag and thats ok
if ( token_name( $token_1[0] ) != "T_INLINE_HTML" )
	die();

// if the text of the last token consists of only trimmable chars then its probably an error
if ( trim($token_1[1]) != "" ) 
	die();

die_with_error( "--> Found trailing whitespaces after the last php closing tag" . PHP_EOL );

