include nginx

nginx::vhost { 'vip.dev':
	docroot => '/vagrant/www',
	template => 'nginx/vip.dev.erb'
}

file { '/etc/nginx/sites-enabled/default':
	ensure => absent,
	force => true,
	notify => Service['nginx'],
	require => Package['nginx']
}
