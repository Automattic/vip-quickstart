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
}

if 'physical' == $::virtual {
    exec { 'apt-get upgrade':
        command => 'apt-get upgrade --quiet --yes',
        timeout => 0
    }
}

file { '/srv/www/wp-content':
    ensure  => directory,
    recurse => true,
    mode    => 0664,
    owner   => 'www-data',
    group   => 'www-data',
}

user { ['vagrant', 'ubuntu']:
    groups => 'www-data',
}
