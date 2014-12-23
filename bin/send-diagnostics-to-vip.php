<?php

/**
 *
 * Send diagnostic information about this VIP Quickstart instance to the WordPress.com VIP team.
 * Retrieves information about the instance via command line tools and database queries,
 * then either outputs to the terminal for copy/paste or (if network connectivity) emails it to
 * the WordPress.com VIP support team's Zendesk instance.
 *
 * Run interactively like
 *   $ php send-diagnostics-to-vip.php
 * or with command line args like
 *   $ php send-diagnostics-to-vip.php --send_mail --from_address me@domain.com --ticket_id 12345
 *
 */

// Default support email address
$default_support_email = 'vip-support@wordpress.com';

// Optionally specify mail should be sent, and set which ticket to send the report to
$options = getopt( "", array( "ticket_id:", "send_mail", "from_address:" ) );

$wp_config_string = '';
$local_config_string = '';
$report_output = '';

// Get some basic system info
$system_info = php_uname();
$hostname = gethostname();
$report_date = date("F j, Y, g:i a T");

// Start creating our report
$report_output .= 'WordPress.com VIP Quickstart Diagnostics Report' . "\n";
$report_output .= 'Generated ' . $report_date . ' on host ' . $hostname . "\n\n";

$report_output .= 'System Info:' . "\n";
$report_output .= '########################################' . "\n";
$report_output .= $system_info . "\n\n";

// Get the local-config.php used by Quickstart
if ( file_exists( __DIR__ . '/../www/local-config.php' ) ) {
	include __DIR__ . '/../www/local-config.php';
	$local_config_string = file_get_contents( __DIR__ . '/../www/local-config.php' );
} else {
	die("Could not find local-config.php, quickstart init probably did not complete.\n");
}

if ( file_exists( __DIR__ . '/../www/wp-config.php' ) ) {
// It would be nice to get the full wp-config.php too so we can use the right DB username, etc.
// But this isn't path aware and so doing an include means other statements in wp-config.php fail
//	include __DIR__ . '/../www/wp-config.php';
	$wp_config_string = file_get_contents( __DIR__ . '/../www/wp-config.php' );
} else {
	die("Could not find wp-config.php, quickstart init probably did not complete.\n");
}

// Confirm that we can connect to the DB server
if ( !$wpdb = mysql_connect('localhost', 'wordpress', constant("DB_PASSWORD") ) ) {
	die('Could not connect to MySQL WordPress database: ' . mysql_error());
	exit;
}

// and that we can connect to the right DB
if ( !mysql_select_db( 'wordpress', $wpdb ) ) {
	die('Could not select MySQL WordPress database: ' . mysql_error());
	exit;
}

// Get some information about the active theme and active plugins
$query = "
	SELECT *
	FROM wp_options
	WHERE
	option_name = 'template'
	OR option_name = 'stylesheet'
	OR option_name = 'current_theme'
	OR option_name = 'active_plugins'";

$result = mysql_query( $query, $wpdb );

// If we couldn't run that query, something is pretty wrong.
if (!$result) {
	$message  = 'Invalid query: ' . mysql_error() . "\n";
	$message .= 'Whole query: ' . $query . "\n";
	die($message);
}

$report_output .= 'WordPress options values:' . "\n";
$report_output .= '########################################' . "\n";

// Add the database values to the report
while ($row = mysql_fetch_assoc($result)) {
	$report_output .= $row['option_name'] . ': ' . $row['option_value'] . "\n";
}

$report_output .= "\n\n";

$report_output .= 'Contents of local-config.php:' . "\n";
$report_output .= '########################################' . "\n";
$report_output .= $local_config_string . "\n";
$report_output .= '########################################' . "\n\n";

$report_output .= 'Contents of wp-config.php:' . "\n";
$report_output .= '########################################' . "\n";
$report_output .= $wp_config_string . "\n";
$report_output .= '########################################' . "\n\n";

$from_address = '';
$ticket_id = '';

if ( ! isset( $options['send_mail'] ) ) {

	$email_prompt = read_cli_option( "Send diagnostic info about your Quickstart instance
by email to the WordPress.com VIP team, including
any sensitive config file settings? [y/N] ");

	if ( 'y' === strtolower( $email_prompt ) ) {
		$options['send_mail'] = 1;
	}
}

if ( isset( $options['send_mail'] ) ) {

	// If an email address was passed by command line arg, use that, otherwise prompt
	if ( isset( $options['from_address'] ) ) {
		$from_address = $options['from_address'];
	} else {
		$from_address = read_cli_option( 'Your email address, same as on any existing support ticket: ' );
	}

	// If it doesn't look like a real email address, let's not use it
	if (! filter_var($from_address, FILTER_VALIDATE_EMAIL) ) {
		$from_address = $default_support_email;
	}

	// If a ticket ID was passed by command line arg, use that, otherwise prompt
	if ( isset( $options['ticket_id'] ) ) {
		$ticket_id = $options['ticket_id'];
	} else {
		$ticket_id = read_cli_option( 'Ticket ID (blank if none exists yet): ' );
	}

	$ticket_id = intval($ticket_id);

	// Set destination email and subject line based on what we know
	if ( is_int( $ticket_id ) && ( $ticket_id > 0 ) ) {

		$vip_zendesk_email = 'support+id' . $ticket_id . '@wordpressvip.zendesk.com';
		$subject           = 'VIP Quickstart diagnostic report from ' . $from_address . ' for #' . $ticket_id . '';

	} else {

		$vip_zendesk_email = $default_support_email;
		$subject           = 'VIP Quickstart diagnostic report from ' . $from_address;

	}

	$headers = "From: $from_address\r\n";

	// should we try to test network access before sending?

	mail( $vip_zendesk_email, $subject, $report_output, $headers );

	echo "Sent report by email.\n";

} else {

	print $report_output;

}

exit;

function read_cli_option( $prompt = "" ) {
	echo $prompt;
	$out = "";
	$char = "";
	while ( "\r" != $char && "\n" != $char ) {
		$out.= $char;
		$char = fread( STDIN, 1 );
	}
	return $out;
}

?>
