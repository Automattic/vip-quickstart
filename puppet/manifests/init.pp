Exec { path => '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin' }

import 'sections/*'

# Set vip.dev in hosts file:
exec { 'setup hosts':
	command => 'sudo printf "\n# VIP Quickstart\n127.0.0.1 vip.dev\n" | sudo tee -a /etc/hosts',
	unless => 'cat /etc/hosts | grep vip.dev'
}

# Setup environment vars
file { "/etc/environment":
    content => inline_template('WP_CLI_CONFIG_PATH="/srv/www/wp-cli.yml"')
}
