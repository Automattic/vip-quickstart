env { 'WP_CLI_CONFIG_PATH': value => '/srv/www/wp-cli.yml' }
env { 'WP_TESTS_DIR': value => '/srv/www/wp-tests/tests/phpunit/' }

# Set an environment variable for staging environments
if 'physical' == $::virtual {
  env { 'QUICKSTART_ENV': value => 'physical' }
} else {
  env { 'QUICKSTART_ENV': value => 'virtual' }
}

# Setup hosts file:
if ( $quickstart_domain ) {
  line { 'hosts':
    file   => '/etc/hosts',
    line   => "127.0.0.1 ${quickstart_domain}",
    notify => Service['networking'],
  }
}

# Set up Zeroconf (Bonjour)
package { 'libnss-mdns': ensure => present }

# Ensure networking service is running
service { 'networking': ensure => running }
