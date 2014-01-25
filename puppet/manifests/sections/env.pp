env { 'WP_CLI_CONFIG_PATH': value => '/srv/www/wp-cli.yml' }
env { 'WP_TESTS_DIR': value => '/srv/www/wp-tests/tests/phpunit/' }

# Set vip.dev in hosts file:
line { 'hosts':
   file => '/etc/hosts',
   line => '127.0.0.1 vip.dev'
}
