class { wp::cli:
	ensure => installed,
	install_path => '/vagrant/www/wp-cli'
}

exec { "rsync wp-content":
	command => "rsync -a /vagrant/www/wp/wp-content/ /vagrant/www/wp-content",
	onlyif => "/usr/bin/test -d /vagrant/www/wp-content"
}

exec { 'rm -rf /vagrant/www/wp/wp-content':
	require => Exec['rsync wp-content'],
	onlyif => '/usr/bin/test -d /vagrant/www/wp/wp-content'
}

file { 'local-config.php':
	ensure => present,
	path   => '/vagrant/www/local-config.php'
}

# Install WordPress
exec {"wp install /vagrant/www/wp":
	command => "/usr/bin/wp core multisite-install --base='vip.dev' --title='vip.dev' --admin_email='wordpress@vip.dev' --admin_name='wordpress' --admin_password='wordpress'",
	cwd => '/vagrant/www/wp',
	require => Class['wp::cli']
}

wp::command { 'plugin update --all':
	command  => 'plugin update --all',
	location => '/vagrant/www/wp',
	require => Exec['wp install /vagrant/www/wp']
}

wp::plugin { 'developer':
	location    => '/vagrant/www/wp',
	networkwide => true,
	require => Exec['wp install /vagrant/www/wp']
}

wp::command { 'developer install-plugins':
	command  => 'developer install-plugins --type=wpcom-vip',
	location => '/vagrant/www/wp',
	require  => Wp::Plugin['developer']
}
