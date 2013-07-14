include php

class {
	'php::composer':;
	'php::fpm':
		provider => 'apt';
	'php::dev':
		provider => 'apt';
	'php::pear':
		provider => 'apt';
	'php::extension::imagick':
		package => 'php5-imagick',
		provider => 'apt';
	'php::extension::xdebug':
		package => 'php5-xdebug',
		provider => 'apt';
	'php::extension::mcrypt':
		package => 'php5-mcrypt',
		provider => 'apt';
	'php::extension::mysql':
		package => 'php5-mysql',
		provider => 'apt';
	'php::extension::curl':
		package => 'php5-curl',
		provider => 'apt';
	'php::extension::gd':
		package => 'php5-gd',
		provider => 'apt';
	# 'php::extension::apc':
	# 	package => 'php-apc',
	# 	provider => 'apt';
}

php::fpm::conf { 'www': user => 'vagrant' }

package { 'memcached': ensure => present }
package { 'php5-memcache': ensure => present }
