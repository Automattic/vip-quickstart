$plugins = ['developer', 'jetpack', 'mrss']

# Install WordPress
exec {"wp install /srv/www/wp":
	command => "/usr/bin/wp core multisite-install --base='vip.dev' --title='vip.dev' --admin_email='wordpress@vip.dev' --admin_name='wordpress' --admin_password='wordpress'",
	cwd => '/srv/www/wp',
	require => Class['wp::cli']
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

stage { 'svn': before => Stage['main'] }
class { 'svn': stage => svn }
class svn {
	package { 'subversion': ensure => present }

	# SVN checkout WordPress
	exec { 'checkout WordPress':
		command => 'svn co http://core.svn.wordpress.org/trunk/ /srv/www/wp; svn rm --keep-local /srv/www/wp/wp-content',
		unless => 'test -d /srv/www/wp && ls -A /srv/www/wp',
		require => Package['subversion']
	}

	exec { 'svn up WordPress':
		cwd => '/srv/www/wp',
		command => 'svn up',
		onlyif => 'svn info',
		require => Exec['checkout WordPress']
	}

	# SVN checkout VIP plugins
	exec { 'checkout plugins':
		command => "svn co https://vip-svn.wordpress.com/plugins/ /srv/www/wp-content/themes/vip/plugins --username='${svn_username}' --password='${svn_password}' --non-interactive",
		unless => "test -d/srv/www/wp-content/themes/vip/plugins && ls -A /srv/www/wp-content/themes/vip/plugins || test -z '${svn_username}' || test -z '${svn_password}'",
		require => Package['subversion']
	}

	exec { 'svn up VIP plugins':
		cwd => '/srv/www/wp-content/themes/vip/plugins',
		command => "svn up --username='${svn_username}' --password='${svn_password}' --non-interactive",
		unless => "test -z '${svn_username}' || test -z '${svn_password}'",
		onlyif => 'svn info',
		require => Exec['checkout plugins']
	}

	# SVN checkout WordPress.com Public Themes 
	exec { 'checkout WordPress.com Public Themes':
		command => 'svn co https://wpcom-themes.svn.automattic.com/ /srv/www/wp-content/themes/pub',
		unless => 'svn info /srv/www/wp-content/themes/pub',
		require => Package['subversion']
	}

	exec { 'svn up WordPress.com Public Themes':
		cwd => '/srv/www/wp-content/themes/pub',
		command => 'svn up',
		onlyif => 'svn info',
		require => Exec['checkout WordPress.com Public Themes']
	}
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
