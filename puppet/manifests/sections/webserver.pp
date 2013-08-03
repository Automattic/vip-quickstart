include nginx

nginx::vhost { 'wp.dev':
	docroot => '/vagrant/www',
	template => 'nginx/wp.dev.erb'
}

file { '/etc/nginx/sites-enabled/default':
	ensure => absent,
	force => true,
	notify => Service['nginx'],
	require => Package['nginx']
}
