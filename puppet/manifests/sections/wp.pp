$plugins = ['developer', 'jetpack', 'mrss']

# Install WordPress
exec {"wp install /vagrant/www/wp":
	command => "/usr/bin/wp core multisite-install --base='vip.dev' --title='vip.dev' --admin_email='wordpress@vip.dev' --admin_name='wordpress' --admin_password='wordpress'",
	cwd => '/vagrant/www/wp',
	require => Class['wp::cli']
}

# Install plugins
wp::plugin { $plugins:
	location    => '/vagrant/www/wp',
	networkwide => true,
	require => Exec['wp install /vagrant/www/wp']
}

# Install VIP recommended developer plugins
wp::command { 'developer install-plugins':
	command  => 'developer install-plugins --type=wpcom-vip',
	location => '/vagrant/www/wp',
	require  => Wp::Plugin['developer']
}

# Update all the plugins
wp::command { 'plugin update --all':
	command  => 'plugin update --all',
	location => '/vagrant/www/wp',
	require => Exec['wp install /vagrant/www/wp']
}

# Install WP-CLI
class { wp::cli:
	ensure => installed,
	install_path => '/vagrant/www/wp-cli',
	version => '0.12.0'
}

# Sync wp-content
exec { "rsync wp-content":
	command => "rsync -a /vagrant/www/wp/wp-content/ /vagrant/www/wp-content",
	onlyif => "/usr/bin/test -d /vagrant/www/wp-content"
}

# Remove wp-content from wp root
exec { 'rm -rf /vagrant/www/wp/wp-content':
	require => Exec['rsync wp-content'],
	onlyif => '/usr/bin/test -d /vagrant/www/wp/wp-content'
}

# Create a local config
file { 'local-config.php':
	ensure => present,
	path   => '/vagrant/www/local-config.php'
}
