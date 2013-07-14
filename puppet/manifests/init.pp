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
