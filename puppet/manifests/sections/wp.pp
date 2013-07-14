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
	target  => '/vagrant/www/content',
	mode    => 0755,
	force   => true,
	require => Exec['svn co wordpress trunk']
}
