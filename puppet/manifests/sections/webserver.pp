include nginx

nginx::vhost { 'wp.dev':
	docroot => '/vagrant/www/wp',
	template => 'nginx/wp.dev.erb'
}

file { '/etc/nginx/sites-available/default':
	ensure => absent,
	require => Package['nginx']
}
