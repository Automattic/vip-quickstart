# PHP 5.6 + extensions
include php
include apt

apt::ppa { 'ppa:ondrej/php': }

package { [ 'php7.0-fpm', 'php7.0-cli' ]:
	ensure  => present,
	require => Apt::Ppa['ppa:ondrej/php']
}
package { [
		'php-memcache',
		'php-imagick',

		'php7.0-curl',
		'php7.0-mbstring',
		'php7.0-mcrypt',
		'php7.0-mysql',
		'php7.0-imap',
		'php7.0-json',
		'php7.0-soap',
		'php7.0-ssh2',
		'php7.0-gd',
		'php7.0-xml',
		'php7.0-zip',
	]:
	ensure  => present,
	require => Apt::Ppa['ppa:ondrej/php']
}

file_line { 'php error_log':
	path  => '/etc/php/7.0/fpm/php.ini',
	line  => 'error_log = /tmp/php-errors',
	match => '^error_log',
	require => Package['php7.0-fpm']
}

# Bump max_input_vars to match WordPress.com
file_line { 'max_input_vars = 6144':
	path  => '/etc/php/7.0/fpm/php.ini',
	line  => 'max_input_vars = 6144',
	match => '^(; max_input_vars|max_input_vars)',
	require => Package['php7.0-fpm']
}
