Exec { path => '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin' }

# Make sure apt-get is up-to-date before we do anything else
stage { 'updates': before => Stage['main'] }
class { 'updates': stage => updates }
class updates {
	exec { 'apt-get update':
		command => 'apt-get update --quiet --yes',
		timeout => 0
	}
}

import 'sections/*'

# Additional packages
package { 'postfix': ensure => present }
