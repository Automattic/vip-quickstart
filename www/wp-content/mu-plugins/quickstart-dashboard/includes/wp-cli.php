<?php

WP_CLI::add_command( 'dashboard', 'Quickstart_Dashboard_CLI' );

class Quickstart_Dashboard_CLI extends WP_CLI_Command {
	/**
	 * @var string Type of command triggered so we can keep track of killswitch cleanup.
	 */
	private $command = '';

	/**
	 * @var bool Flag whether or not execution should be stopped.
	 */
	private $halt = false;

	/**
	 * @subcommand load_plugins
	 */
	function load_plugins( $args, $assoc_args ) {
		WP_CLI::line( 'Loading plugins...' );
		$instance = Quickstart_Dashboard::get_instance();

		$plugins = $instance->load_plugins();

		WP_CLI::line( 'Plugins loaded: ' );
		foreach ( $plugins as $name => $plugin ) {
			WP_CLI::line( "	$name: {$plugin->name()}" );
		}
	}
}
