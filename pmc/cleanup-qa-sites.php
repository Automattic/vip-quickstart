<?php
if ( empty($argv[1]) ) {
	die("Syntax: php {$argv[0]} <csv-file>\n");
} 
$csv_file = $argv[1];

$excludes = array( 'live', 'adops', 'adops1', 'adops2', 'adops3', 'adops4', 'adops5' );
$content_path = '/srv/www/wp-content-sites';

$branches = parse_csv( $csv_file );
if ( empty($branches) ) {
	die("Nothing to cleanup\n");
}

$paths = glob( $content_path ."/*" );
foreach ( $paths as $path ) {
	$branch = basename( $path );
	if ( in_array( $branch, $excludes ) ) {
		continue;
	}
	if ( empty( $branches[ $branch ] ) ) {
		echo "Remove: {$path}\n";
		exec('rm -rf '. escapeshellarg( $path ) );
		continue;
	}
	if ( file_exists( $path . '/themes/vip' ) ) {
		$theme_paths = glob( $path . '/themes/vip/*' );
		foreach ( $theme_paths as $theme_path ) {
			$theme = basename( $theme_path );
			if ( !in_array( $theme, $branches[$branch] ) ) {
				echo "Remove: {$theme_path}\n";
				exec('rm -rf '. escapeshellarg( $theme_path ) );
			}
		}
	}
}

die();

function parse_csv( $file ) {
	$branches = array();
	if ( ! $fh = fopen( $file, 'r') ) {
		return false;
	}
	$headers = fgetcsv($fh);
	if ( empty( $headers) || count( $headers ) != 2 || ! in_array( 'theme', $headers) || ! in_array( 'branch', $headers ) ) {
		fclose( $fh );
		return;
	}

	while ( $row = fgetcsv($fh) ) {
		if ( empty( $row ) || count( $row ) != count( $headers ) ) {
			continue;
		}
		$data = array_map('strtolower', array_combine( $headers, $row ) );
		$branches[ $data['branch'] ][] = $data['theme'];
	}
	fclose($fh);
	return $branches;
}
