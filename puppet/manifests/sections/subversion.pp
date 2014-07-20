stage { 'svnupgrade': before => Stage['main'] }
class { 'svnupgrade': stage => svnupgrade }

# svnupgrade
class svnupgrade {
  include apt

  apt::source { 'wheezy-backports':
    location => 'http://http.debian.net/debian',
    release  => 'wheezy-backports',
    repos    => 'main',
  }

  exec { 'svn':
    command => 'sudo apt-get install -t wheezy-backports subversion --yes --force-yes',
    unless  => 'sudo apt-get install -t wheezy-backports subversion | grep "0 upgraded, 0 newly installed"',
    require => Apt::Source['wheezy-backports'],
  }
}

