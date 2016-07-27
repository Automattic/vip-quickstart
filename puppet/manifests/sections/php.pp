# PHP 5.6 + extensions
include php
include apt

apt::source { 'php56':
  location    => 'http://ppa.launchpad.net/ondrej/php5-5.6/ubuntu',
  release     => 'precise',
  repos       => 'main',
  key         => '14aa40ec0831756756d7f66c4f4ea0aae5267a6c',
  key_server  => 'hkp://keyserver.ubuntu.com:80',
  include_src => true
}

class {
  'php::cli':
    ensure  => latest,
    require => Apt::Source['php56'];
  'php::composer':;
  'php::dev':
    ensure  => latest,
    require => Apt::Source['php56'];
  'php::fpm':
    require => Apt::Source['php56'];
  'php::pear':
    ensure  => latest,
    require => Apt::Source['php56'];
  'php::phpunit':;

  # Extensions
  'php::extension::curl':
    require => Package['php5-common'];
  'php::extension::gd':
    require => Package['php5-common'];
  'php::extension::imagick':
    require => Package['php5-common'];
  'php::extension::mcrypt':
    require => Package['php5-common'];
  'php::extension::memcache':
    require => Package['php5-common'];
  'php::extension::mysql':
    require => Package['php5-common'];
  'php::extension::xdebug':
    settings => [
        "set .anon/zend_extension '/usr/lib/php5/20131226/xdebug.so'",
        'set .anon/xdebug.idekey QUICKSTART',
        'set .anon/xdebug.remote_enable 1',
        'set .anon/xdebug.remote_connect_back 1',
        'set .anon/profiler_enable_trigger 1',
    ],
    require => Package['php5-common'];
}

php::fpm::pool {
  'www':
    user       => 'www-data',
    log_errors => false,
    php_value  => {
      'error_log' => '/var/log/php-fpm/www-error.log'
    },
    php_flag   => {
      'log_errors' => 'on'
    },
    require    => Class['php::fpm']
}

php::fpm::config {
  'html_errors':
    setting => 'html_errors',
    value   => 'On',
    require => Class['php::fpm']
}

# Install PHP_CodeSniffer
exec { 'download phpcs':
  command => 'wget https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar -O /usr/local/bin/phpcs',
  creates => '/usr/local/bin/phpcs',
  user    => root,
  path    => [ '/bin/', '/sbin/' , '/usr/bin/', '/usr/sbin/' ],
  require => Package['php5-common']
}

file { '/usr/local/bin/phpcs':
  mode    => '0555',
  owner   => root,
  group   => root,
  require => Exec['download phpcs'],
}

vcsrepo { '/usr/share/php/PHP/CodeSniffer/Standards/WordPress':
  ensure   => 'present',
  source   => 'https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards',
  provider => 'git',
  require  => File['/usr/local/bin/phpcs']
}

# Add WordPress coding standards to PHP_CodeSniffer config
exec { 'add wordpress cs to phpcs':
  command => 'phpcs --config-set installed_paths /usr/share/php/PHP/CodeSniffer/Standards/WordPress',
  unless  => 'phpcs -i | grep "WordPress"',
  user    => root,
  require => Vcsrepo['/usr/share/php/PHP/CodeSniffer/Standards/WordPress']
}

# Set PHP-FPM log ownership
exec { 'Set PHP-FPM log ownership':
  command => 'touch /var/log/php-fpm-www-error.log && chown www-data:www-data /var/log/php-fpm-www-error.log',
  creates => '/var/log/php-fpm-www-error.log',
  user    => root,
  require => Class['php::fpm']
}

# Bump max_input_vars to match WordPress.com
exec { 'max_input_vars = 6144':
  command => 'sed -i "s/;* *max_input_vars *= *[0-9]*/max_input_vars = 6144/g" /etc/php5/fpm/php.ini',
  unless  => 'cat /etc/php5/fpm/php.ini | grep "max_input_vars = 6144"',
  user    => root,
  notify  => Service['php5-fpm']
}
