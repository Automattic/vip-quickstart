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

file { '/vagrant/www/phpmyadmin':
	ensure => 'link',
	target => '/usr/share/phpmyadmin',
	require => Package['phpmyadmin']
}
