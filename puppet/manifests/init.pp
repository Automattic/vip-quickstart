Exec { path => '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin' }

import 'helpers/*'
import 'sections/*'

# Make sure apt-get is up-to-date before we do anything else
stage { 'updates': before => Stage['main'] }
class { 'updates': stage => updates }
class updates {
    exec { 'apt-get update':
        command => 'apt-get update --quiet --yes',
        timeout => 0
    }
}

user { 'vagrant':
    ensure => 'present',
    system => true,
    notify => Service['php5-fpm'],
}

file { '/srv/www/wp-content':
    ensure  => 'directory',
    owner   => 'vagrant',
    group   => 'vagrant',
    recurse => true,
    require => User['vagrant'],
    notify  => Service['nginx'],
}
