<?php
/*
 * Concatenation script inspired by Nginx's ngx_http_concat and Apache's modconcat modules.
 *
 * It follows the same pattern for enabling the concatenation. It uses two ?, like this:
 * http://example.com/??style1.css,style2.css,foo/style3.css
 *
 * If a third ? is present it's treated as version string. Like this:
 * http://example.com/??style1.css,style2.css,foo/style3.css?v=102234
 *
 * It will also replace the relative paths in CSS files with absolute paths.
 */

require __DIR__ . '/cssmin.php';

/* Config */
$concat_max_files = 150;
$concat_unique = true;
$concat_types = array(
	'css' => 'text/css',
	'js' => 'application/x-javascript'
);

/* Constants */
// By default determine the document root from this scripts path in the plugins dir (you can hardcode this define)
define( 'CONCAT_FILES_ROOT', substr( dirname( __DIR__ ), 0, strpos( dirname( __DIR__ ), '/wp-content' ) ) );
define( 'CONCAT_WP_DIR', '/wp' );

function concat_http_status_exit( $status ) {
	switch ( $status ) {
		case 200:
			$text = 'OK';
			break;
		case 400:
			$text = 'Bad Request';
			break;
		case 403:
			$text = 'Forbidden';
			break;
		case 404:
			$text = 'Not found';
			break;
		case 500:
			$text = 'Internal Server Error';
			break;
		default:
			$text = '';
	}

	$protocol = $_SERVER['SERVER_PROTOCOL'];
	if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
		$protocol = 'HTTP/1.0';

	@header( "$protocol $status $text", true, $status );
	exit();
}

function concat_get_mtype( $file ) {
	global $concat_types;

	$lastdot_pos = strrpos( $file, '.' );
	if ( false === $lastdot_pos )
		return false;

	$ext = substr( $file, $lastdot_pos + 1 );

	return isset( $concat_types[$ext] ) ? $concat_types[$ext] : false;
}

function concat_get_path( $uri ) {
	if ( ! strlen( $uri ) )
		concat_http_status_exit( 400 );

	if ( false !== strpos( $uri, '..' ) || false !== strpos( $uri, "\0" ) )
		concat_http_status_exit( 400 );

	return CONCAT_FILES_ROOT . ( '/' != $uri[0] ? '/' : CONCAT_WP_DIR ) . $uri;
}

/* Main() */
if ( !in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ) ) )
	concat_http_status_exit( 400 );

// /_static/??/foo/bar.css,/foo1/bar/baz.css?m=293847g
// or
// /_static/??-eJzTT8vP109KLNJLLi7W0QdyDEE8IK4CiVjn2hpZGluYmKcDABRMDPM=
$args = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY );
if ( ! $args || false === strpos( $args, '?' ) )
	concat_http_status_exit( 400 );

$args = substr( $args, strpos( $args, '?' ) + 1 );

// /foo/bar.css,/foo1/bar/baz.css?m=293847g
// or
// -eJzTT8vP109KLNJLLi7W0QdyDEE8IK4CiVjn2hpZGluYmKcDABRMDPM=
if ( '-' == $args[0] )
	$args = gzuncompress( base64_decode( substr( $args, 1 ) ) );

// /foo/bar.css,/foo1/bar/baz.css?m=293847g
$version_string_pos = strpos( $args, '?' );
if ( false !== $version_string_pos )
	$args = substr( $args, 0, $version_string_pos );

// /foo/bar.css,/foo1/bar/baz.css
$args = explode( ',', $args );
if ( ! $args )
	concat_http_status_exit( 400 );

// array( '/foo/bar.css', '/foo1/bar/baz.css' )
if ( 0 == count( $args ) || count( $args ) > $concat_max_files )
	concat_http_status_exit( 400 );

$last_modified = 0;
$pre_output = '';
$output = '';

$css_minify = new CSSmin();

foreach ( $args as $uri ) {
	$fullpath = concat_get_path( $uri );

	if ( ! file_exists( $fullpath ) )
		concat_http_status_exit( 404 );

	$mime_type = concat_get_mtype( $fullpath );
	if ( ! in_array( $mime_type, $concat_types ) )
		concat_http_status_exit( 400 );

	if ( $concat_unique ) {
		if ( ! isset( $last_mime_type ) )
			$last_mime_type = $mime_type;

		if ( $last_mime_type != $mime_type )
			concat_http_status_exit( 400 );
	}

	$stat = stat( $fullpath );
	if ( false === $stat )
		concat_http_status_exit( 500 );

	if ( $stat['mtime'] > $last_modified )
		$last_modified = $stat['mtime'];

	$buf = file_get_contents( $fullpath );
	if ( false === $buf )
		concat_http_status_exit( 500 );

	if ( 'text/css' == $mime_type ) {
		$dirpath = dirname( $uri );

		// url(relative/path/to/file) -> url(/absolute/and/not/relative/path/to/file)
		$buf = preg_replace(
			'/(:?\s*url\s*\()\s*(?:\'|")?\s*([^\/\'"\s\)](?:(?<!data:|http:|https:).)*)[\'"\s]*\)/isU',
			'$1' . ( $dirpath == '/' ? '/' : $dirpath . '/' ) . '$2)',
			$buf
		);

		// AlphaImageLoader(...src='relative/path/to/file'...) -> AlphaImageLoader(...src='/absolute/path/to/file'...)
		$buf = preg_replace(
			'/(Microsoft.AlphaImageLoader\s*\([^\)]*src=(?:\'|")?)([^\/\'"\s\)](?:(?<!http:|https:).)*)\)/isU',
			'$1' . ( $dirpath == '/' ? '/' : $dirpath . '/' ) . '$2)',
			$buf
		);

		// The @charset rules must be on top of the output
		if ( 0 === strpos( $buf, '@charset' ) ) {
			preg_replace_callback(
				'/(?P<charset_rule>@charset\s+[\'"][^\'"]+[\'"];)/i',
				function ( $match ) {
					global $pre_output;

					if ( 0 === strpos( $pre_output, '@charset' ) )
						return '';

					$pre_output = $match[0] . "\n" . $pre_output;

					return '';
				},
				$buf
			);
		}

		// Move the @import rules on top of the concatenated output.
		// Only @charset rule are allowed before them.
		if ( false !== strpos( $buf, '@import' ) ) {
			$buf = preg_replace_callback(
				'/(?P<pre_path>@import\s+(?:url\s*\()?[\'"\s]*)(?P<path>[^\'"\s](?:https?:\/\/.+\/?)?.+?)(?P<post_path>[\'"\s\)]*;)/i',
				function ( $match ) use ( $dirpath ) {
					global $pre_output;

					if ( 0 !== strpos( $match['path'], 'http' ) && '/' != $match['path'][0] )
						$pre_output .= $match['pre_path'] . ( $dirpath == '/' ? '/' : $dirpath . '/' ) .
							$match['path'] . $match['post_path'] . "\n";
					else
						$pre_output .= $match[0] . "\n";

					return '';
				},
				$buf
			);
		}

		$buf = $css_minify->run( $buf );
	}

	if ( 'application/x-javascript' == $mime_type )
		$output .= "$buf;\n";
	else
		$output .= "$buf";
}

header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $last_modified ) . ' GMT' );
header( 'Content-Length: ' . ( strlen( $pre_output ) + strlen( $output ) ) );
header( "Content-Type: $mime_type" );

echo $pre_output . $output;
