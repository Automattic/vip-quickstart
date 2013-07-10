include php

class {
	'php::composer':;
	'php::fpm':
		provider => 'apt';
	'php::dev':
		provider => 'apt';
	'php::extension::apc':
		package => 'php-apc',
		provider => 'apt';
}
