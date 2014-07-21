stage { 'svnupgrade': before => Stage['main'] }
class { 'svnupgrade': stage => svnupgrade }

# svnupgrade
class svnupgrade {
  line { 'deb svn':
    file   => '/etc/apt/sources.list',
    line   => 'deb http://ppa.launchpad.net/svn/ppa/ubuntu precise main',
    notify => Exec['apt-get update svn']
  }
  line { 'deb-src svn':
    file   => '/etc/apt/sources.list',
    line   => 'deb-src http://ppa.launchpad.net/svn/ppa/ubuntu precise main',
    notify => Exec['apt-get update svn']
  }

  exec { 'apt-get update svn':
    command     => 'sudo apt-get update',
    refreshonly => true,
    require     => [
      Line['deb svn'],
      Line['deb-src svn']
    ]
  }

  exec { 'svn':
    command => 'sudo apt-get install subversion --yes --force-yes',
    unless  => 'sudo apt-get install subversion | grep "0 upgraded, 0 newly installed"',
    require => [
      Line['deb svn'],
      Line['deb-src svn'],
      Exec['apt-get update svn']
    ]
  }
}
