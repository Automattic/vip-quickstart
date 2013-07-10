Exec { path => '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin' }

import 'sections/*'

# Upgrade system packages
stage { 'updates': before => Stage['main'] }
class { 'updates': stage => updates }
class updates {
	exec { 'apt-get update':
		command => 'apt-get update --quiet --yes',
		timeout => 0
	}
	exec { 'apt-get upgrade':
		command => 'apt-get upgrade --quiet --yes',
		timeout => 0,
		require => Exec['apt-get update']
	}
}


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
