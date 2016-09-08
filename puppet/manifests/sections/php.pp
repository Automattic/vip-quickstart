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
		'php7.0-mcrypt',
		'php7.0-mysql',
		'php7.0-gd',
		'php7.0-xml',
	]:
	ensure  => present,
	require => Apt::Ppa['ppa:ondrej/php']
}
