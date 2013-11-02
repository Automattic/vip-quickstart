stage { 'svn-upgrade': before => Stage['main'] }
class { 'svn-upgrade': stage => svn-upgrade }
class svn-upgrade {
	line { 'deb svn':
		file => "/etc/apt/sources.list",
		line => "deb http://ppa.launchpad.net/svn/ppa/ubuntu precise main",
	}
	line { 'deb-src svn':
		file => "/etc/apt/sources.list",
		line => "deb-src http://ppa.launchpad.net/svn/ppa/ubuntu precise main ",
	}

	exec { 'apt-get update svn':
		command => 'sudo apt-get update',
		require => [
			Line['deb svn'],
			Line['deb-src svn']
		]
	}

	exec { 'svn':
		command => 'sudo apt-get install subversion --yes --force-yes',
		unless => 'sudo apt-get install subversion | grep "0 upgraded, 0 newly installed"',
		require => [
			Line['deb svn'],
			Line['deb-src svn'],
			Exec['apt-get update svn']
		]
	}
}
