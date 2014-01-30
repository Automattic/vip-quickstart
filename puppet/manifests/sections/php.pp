# Use PHP 5.4.24 like WordPress.com
$php_version = '5.4.24-1+sury.org~precise+1'

include php
include apt


apt::source { 'php54':
	location => 'http://ppa.launchpad.net/ondrej/php5-oldstable/ubuntu',
	release => 'precise',
	repos => 'main',
	key => '14aa40ec0831756756d7f66c4f4ea0aae5267a6c',
	key_server => 'keyserver.ubuntu.com',
	include_src => true
}

class {
	'php::cli':
		provider => 'apt',
		ensure => $php_version,
		require => Apt::Source['php54'];
	'php::composer':;
	'php::dev':
		provider => 'apt',
		ensure => $php_version,
		require => Apt::Source['php54'];
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

# Turn on html_errors
exec { 'html_errors = On':
	command => 'sed -i "s/html_errors = Off/html_errors = On/g" /etc/php5/fpm/php.ini',
	unless => 'cat /etc/php5/fpm/php.ini | grep "html_errors = On"',
	user => root,
	notify => Service['php5-fpm']
}
