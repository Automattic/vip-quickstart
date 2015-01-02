class { 'nginx':
  template             => 'nginx/nginx.conf.erb',
  disable_default_site => true,
  client_max_body_size => '1024m',
  types_hash_max_size  => '2048',
}

nginx::vhost { 'qz.dev':
  docroot    => '/srv/www',
  template   => 'nginx/vip.dev.erb',
  owner      => 'www-data',
  groupowner => 'www-data',
}

# todo: find a better template
nginx::vhost { 'app.qz.dev':
  docroot    => '/srv/www/qzapp',
  template   => 'nginx/vip.dev.erb',
  owner      => 'www-data',
  groupowner => 'www-data',
}
