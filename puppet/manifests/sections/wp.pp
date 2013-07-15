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
	require => Package["subversion"]
}

# svn up
exec { "svn up":
	cwd     => "/vagrant/www/wp",
	require => Exec["svn co wordpress trunk"]
}

file { 'wp-content-sym-link':
	ensure  => link,
	path    => '/vagrant/www/wp/wp-content',
	target  => '/vagrant/www/wp-content',
	mode    => 0755,
	force   => true,
	require => Exec['svn co wordpress trunk']
}

# Install WordPress
wp::site { '/vagrant/www/wp':
	url            => 'wp.dev',
	sitename       => 'wp.dev',
	admin_user     => 'wordpress',
	admin_password => 'wordpress',
	network        => true,
	subdomains     => true,
	require        => Exec['svn co wordpress trunk']
}

wp::plugin { 'developer':
	location => '/vagrant/www/wp',
	networkwide => true
}
wp::plugin { 'jetpack':
	location => '/vagrant/www/wp',
	networkwide => true
}
wp::plugin { 'mp6':
	location => '/vagrant/www/wp',
	networkwide => true
}
