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

	# SVN checkout Minileven
	exec { 'checkout Minileven':
		command => 'svn co https://wpcom-themes.svn.automattic.com/minileven/ /vagrant/www/wp-content/themes/pub/minileven',
		unless => 'test -d /vagrant/www/wp-content/themes/pub/minileven && ls -A /vagrant/www/wp-content/themes/pub/minileven',
		require => Package['subversion']
	}

	exec { 'svn up Minileven':
		cwd => '/vagrant/www/wp-content/themes/pub/minileven',
		command => 'svn up',
		onlyif => 'svn info',
		require => Exec['checkout Minileven']
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
	path   => '/vagrant/www/local-config.php',
	notify => Exec['generate salts']
}

exec { 'generate salts':
	command => 'printf "<?php\n" > /vagrant/www/local-config.php; curl https://api.wordpress.org/secret-key/1.1/salt/ >> /vagrant/www/local-config.php',
	refreshonly => true
}
