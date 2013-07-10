import 'sections/*'

# Do some setup here
file { 'www-directory':
	ensure => directory,
	path   => '/vagrant/www',
	owner  => 'vagrant',
	mode   => 0755,
	before => File['www-directory-sym-link'],
}

file { 'www-directory-sym-link':
	ensure  => link,
	path    => '/srv/www',
	target  => '/vagrant/www',
	mode    => 0755,
	require => File['www-directory'],
}

# Install SVN
package { 'subversion': ensure => present }

# Checkout core
exec { "svn co wordpress trunk":
	command => "/usr/bin/svn co https://core.svn.wordpress.org/trunk wp",
	cwd     => "/vagrant/www",
	creates => "/vagrant/www/wp",
	require => Package["subversion"]
}

# svn up
exec { "svn up":
	cwd     => "/vagrant/www/wp",
	require => Exec["svn co wordpress trunk"]
}
