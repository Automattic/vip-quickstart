class { wp::cli:
	ensure => installed,
	install_path => '/vagrant/www/wp-cli'
}

# Install SVN
package { 'subversion': ensure => present }

# Checkout core
exec { "svn co wordpress trunk":
	command => "svn co https://core.svn.wordpress.org/trunk wp",
	cwd     => "/vagrant/www",
	creates => "/vagrant/www/wp",
	require => Package["subversion"],
	notify  => Exec['rsync wp-content']
}

# svn up
exec { "svn up":
	cwd     => "/vagrant/www/wp",
	require => Exec["svn co wordpress trunk"],
	notify  => Exec['rsync wp-content']
}

exec { "rsync wp-content":
	command => "rsync -a /vagrant/www/wp/wp-content/ /vagrant/www/wp-content",
	refreshonly => true
}

file { 'local-config.php':
	ensure => present,
	path   => '/vagrant/www/local-config.php'
}

# Install WordPress
wp::site { '/vagrant/www/wp':
	url            => 'vip.dev',
	sitename       => 'vip.dev',
	admin_user     => 'wordpress',
	admin_password => 'wordpress',
	admin_email    => 'wordpress@vip.dev',
	network        => true,
	subdomains     => false,
	require        => Exec['svn co wordpress trunk']
}

wp::command { 'plugin update-all':
	command  => 'plugin update-all',
	location => '/vagrant/www/wp',
	require  => Wp::Site['/vagrant/www/wp']
}

wp::plugin { 'developer':
	location    => '/vagrant/www/wp',
	networkwide => true
}

wp::command { 'developer install-plugins':
	command  => 'developer install-plugins --type=wpcom-vip',
	location => '/vagrant/www/wp',
	require  => Wp::Plugin['developer']
}
