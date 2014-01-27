include php

class {
	'php::cli':
		provider => 'apt';
	'php::composer':;
	'php::dev':
		provider => 'apt';
	'php::fpm':;
	'php::pear':;
	'php::phpunit':;

	# Extensions
	'php::extension::apc':
		ensure => absent,
		provider => 'apt';
	'php::extension::curl':
		provider => 'apt';
	'php::extension::gd':
		provider => 'apt';
	'php::extension::imagick':
		provider => 'apt';
	'php::extension::mcrypt':
		provider => 'apt';
	'php::extension::memcache':
		provider => 'apt';
	'php::extension::mysql':
		provider => 'apt';
	'php::extension::xdebug':
		provider => 'apt';
}

php::fpm::conf { 'www': user => 'vagrant' }

file { '/etc/php5/conf.d/apc.ini': ensure => absent }

# TODO: Make this not gross
exec { 'configure php5-xdebug':
	command => 'echo "zend_extension=`sudo find / -name \'xdebug.so\' | head -1`" | sudo tee -a /etc/php5/conf.d/xdebug.ini',
	unless => 'test -f /etc/php5/conf.d/xdebug.ini && cat /etc/php5/conf.d/xdebug.ini | grep zend_extension',
	notify => Service['php5-fpm'],
	require => Package['php5-xdebug']
}

# Turn on html_errors
exec { 'html_errors = On':
	command => 'sed -i "s/html_errors = Off/html_errors = On/g" /etc/php5/fpm/php.ini',
	unless => 'cat /etc/php5/fpm/php.ini | grep "html_errors = On"',
	user => root,
	notify => Service['php5-fpm']
}
