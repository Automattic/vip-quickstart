class { "nginx":
	disable_default_site => true,
}

nginx::vhost { 'vip.dev':
	docroot => '/vagrant/www',
	template => 'nginx/vip.dev.erb'
}
