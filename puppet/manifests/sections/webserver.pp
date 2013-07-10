class { 'nginx': }

nginx::resource::vhost { 'wp.dev':
	ensure   => present,
	www_root => '/srv/www',
}
