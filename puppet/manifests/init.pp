Exec { path => '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin' }

import 'helpers/*'
import 'sections/*'

# Make sure apt-get is up-to-date before we do anything else
stage { 'updates': before => Stage['main'] }
class { 'updates': stage => updates }

# updates
class updates {
    exec { 'apt-get update':
        command => 'apt-get update --quiet --yes',
        timeout => 0
    }

    if 'virtualbox' != $virtual {
        exec { 'apt-get upgrade':
            command => 'apt-get upgrade --quiet --yes',
            timeout => 0
        }
    }
}

# Vagrant user
user { 'vagrant':
    ensure => 'present',
    system => true,
    shell  => '/bin/bash',
    notify => Service['php5-fpm'],
}

# Update SVN
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
