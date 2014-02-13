<?php

WP_CLI::add_command( 'quickstart-dashboard', 'Quickstart_Dashboard_CLI' );

class Quickstart_Dashboard_CLI extends WP_CLI_Command {
	/**
	 * @var string Type of command triggered so we can keep track of killswitch cleanup.
	 */
	private $command = '';

	/**
	 * @var bool Flag whether or not execution should be stopped.
	 */
	private $halt = false;
}
