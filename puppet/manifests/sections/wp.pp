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
	command  => 'developer install-plugins --type=wpcom-vip --activate',
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
	version => '0.12.1'
}

stage { 'svn': before => Stage['main'] }
class { 'svn': stage => svn }
class svn {
	package { 'subversion': ensure => present }

	# SVN checkout WordPress
	exec { 'checkout WordPress':
		command => 'svn co http://core.svn.wordpress.org/trunk/ /vagrant/www/wp; svn rm --keep-local /vagrant/www/wp/wp-content',
		unless => 'test -d /vagrant/www/wp && ls -A /vagrant/www/wp',
		require => Package['subversion']
	}

	exec { 'svn up WordPress':
		cwd => '/vagrant/www/wp',
		command => 'svn up',
		onlyif => 'svn info',
		require => Exec['checkout WordPress']
	}

	# SVN checkout VIP plugins
	exec { 'checkout plugins':
		command => "svn co https://vip-svn.wordpress.com/plugins/ /vagrant/www/wp-content/themes/vip/plugins --username='${svn_username}' --password='${svn_password}' --non-interactive",
		unless => "test -d/vagrant/www/wp-content/themes/vip/plugins && ls -A /vagrant/www/wp-content/themes/vip/plugins || test -z '${svn_username}' || test -z '${svn_password}'",
		require => Package['subversion']
	}

	exec { 'svn up VIP plugins':
		cwd => '/vagrant/www/wp-content/themes/vip/plugins',
		command => "svn up --username='${svn_username}' --password='${svn_password}' --non-interactive",
		unless => "test -z '${svn_username}' || test -z '${svn_password}'",
		onlyif => 'svn info',
		require => Exec['checkout plugins']
	}
}

# Sync wp-content
exec { "rsync wp-content":
	command => "rsync -a /vagrant/www/wp/wp-content/ /vagrant/www/wp-content",
	onlyif => "/usr/bin/test -d /vagrant/www/wp/wp-content"
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
