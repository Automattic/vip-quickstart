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

  # Install the 1.8 svn repository
  exec { 'open source repository':
    command => 'sudo sh -c \'echo "# WANdisco Open Source Repo" >> /etc/apt/sources.list.d/WANdisco.list\'',
    require => [
      Line['deb svn'],
      Line['deb-src svn']
    ]
  }

  exec { 'open svn 1.8 repository':
    command => 'sudo sh -c \'echo "deb http://opensource.wandisco.com/ubuntu precise svn18" >> /etc/apt/sources.list.d/WANdisco.list\'',
    require => [
      Exec['open source repository']
    ]
  }

  exec { 'wget svn 1.8 repository':
    command => 'wget -q http://opensource.wandisco.com/wandisco-debian.gpg -O- | sudo apt-key add -',
    require => [
      Exec['open svn 1.8 repository']
    ]
  }

  # Update svn on apt-get
  exec { 'apt-get update svn':
    command     => 'sudo apt-get update',
    refreshonly => true,
    require     => [
      Exec['wget svn 1.8 repository']
    ]
  }

  exec { 'svn':
    command => "sudo apt-get install subversion=${svn_version} --yes --force-yes",
    unless  => "sudo apt-get install subversion=${svn_version} | grep '0 upgraded, 0 newly installed'",
    require => [
      Line['deb svn'],
      Line['deb-src svn'],
      Exec['apt-get update svn']
    ]
  }
}
