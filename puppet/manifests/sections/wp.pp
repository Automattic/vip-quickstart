$plugins = ['developer', 'jetpack', 'mrss']

# Install WordPress
exec {"wp install /srv/www/wp":
	command => "/usr/bin/wp core multisite-install --base='vip.dev' --title='vip.dev' --admin_email='wordpress@vip.dev' --admin_name='wordpress' --admin_password='wordpress'",
	cwd => '/srv/www/wp',
	require => [
		Vcsrepo['/srv/www/wp'],
		Class['wp::cli'],
	]
}

# Install plugins
wp::plugin { $plugins:
	location    => '/srv/www/wp',
	networkwide => true,
	require => Exec['wp install /srv/www/wp']
}

# Install VIP recommended developer plugins
wp::command { 'developer install-plugins':
	command  => 'developer install-plugins --type=wpcom-vip --activate',
	location => '/srv/www/wp',
	require  => Wp::Plugin['developer']
}

# Update all the plugins
wp::command { 'plugin update --all':
	command  => 'plugin update --all',
	location => '/srv/www/wp',
	require => Exec['wp install /srv/www/wp']
}

# Install WP-CLI
class { wp::cli:
	ensure => installed,
	install_path => '/srv/www/wp-cli',
	version => '0.12.1'
}

# VCS Checkout
vcsrepo { '/srv/www/wp':
	ensure   => 'present',
	source   => 'http://core.svn.wordpress.org/trunk/',
	provider => svn,
}

vcsrepo { '/srv/www/wp-content/themes/vip/plugins':
	ensure   => 'present',
	source   => 'https://vip-svn.wordpress.com/plugins/',
	provider => svn,
}

vcsrepo { '/srv/www/wp-content/themes/pub':
	ensure   => 'present',
	source   => 'https://wpcom-themes.svn.automattic.com/',
	provider => svn,
}

vcsrepo { '/srv/www/wp-tests':
	ensure   => 'present',
	source   => 'http://develop.svn.wordpress.org/trunk/',
	provider => svn,
}

# Sync wp-content
exec { "rsync wp-content":
	command => "rsync -a /srv/www/wp/wp-content/ /srv/www/wp-content",
	onlyif => "/usr/bin/test -d /srv/www/wp/wp-content"
}

# Remove wp-content from wp root
exec { 'rm -rf /srv/www/wp/wp-content':
	require => Exec['rsync wp-content'],
	onlyif => '/usr/bin/test -d /srv/www/wp/wp-content'
}

# Create a local config
file { 'local-config.php':
	ensure => present,
	path   => '/srv/www/local-config.php',
	notify => Exec['generate salts']
}

exec { 'generate salts':
	command => 'printf "<?php\n" > /srv/www/local-config.php; curl https://api.wordpress.org/secret-key/1.1/salt/ >> /srv/www/local-config.php',
	refreshonly => true
}
