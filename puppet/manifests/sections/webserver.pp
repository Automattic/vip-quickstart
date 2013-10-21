class { "nginx":
	template => 'nginx/conf.d/nginx.conf.erb',
	disable_default_site => true,
	client_max_body_size => '1024m'
}

nginx::vhost { 'vip.dev':
	docroot => '/vagrant/www',
	template => 'nginx/vip.dev.erb'
}
