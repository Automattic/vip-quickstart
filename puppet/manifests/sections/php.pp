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

php::fpm::pool { 'www': user => 'www-data' }

# Install PHP_CodeSniffer and the WordPress coding standard
package { 'pear.php.net/PHP_CodeSniffer':
  ensure   => 'installed',
  provider => 'pear',
}

vcsrepo { '/usr/share/php/PHP/CodeSniffer/Standards/WordPress':
  ensure   => 'present',
  source   => 'https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards',
  provider => 'git',
  require  => Package['pear.php.net/PHP_CodeSniffer'],
}

# Add WordPress coding standards to PHP_CodeSniffer config
exec { 'add wordpress cs to phpcs':
  command => 'phpcs --config-set installed_paths /usr/share/php/PHP/CodeSniffer/Standards/WordPress',
  unless  => 'phpcs -i | grep "WordPress"',
  user    => root,
  require => Vcsrepo['/usr/share/php/PHP/CodeSniffer/Standards/WordPress']
}

# Turn on html_errors
exec { 'html_errors = On':
  command => 'sed -i "s/html_errors = Off/html_errors = On/g" /etc/php5/fpm/php.ini',
  unless  => 'cat /etc/php5/fpm/php.ini | grep "html_errors = On"',
  user    => root,
  notify  => Service['php5-fpm'],
  require => Class['php::fpm']
}

# Enable PHP-FPM error_log Override
exec { 'Enable PHP-FPM error_log Override':
  command => 'sed -i "s/php_admin_value\[error_log\]/php_value\[error_log\]/g" /etc/php5/fpm/pool.d/www.conf',
  unless  => 'cat /etc/php5/fpm/pool.d/www.conf | grep "php_value[error_log]"',
  user    => root,
  require  => Class['php::fpm']
}

# Enable PHP-FPM log_errors Override
exec { 'Enable PHP-FPM log_errors Override':
  command => 'sed -i "s/php_admin_flag\[log_errors\]/php_flag\[log_errors\]/g" /etc/php5/fpm/pool.d/www.conf',
  unless  => 'cat /etc/php5/fpm/pool.d/www.conf | grep "php_value[log_errors]"',
  user    => root,
  require  => Class['php::fpm']
}

# Set PHP-FPM log ownership
exec { 'Set PHP-FPM log ownership':
  command => 'touch /var/log/php-fpm-www-error.log && chown www-data:www-data /var/log/php-fpm-www-error.log',
  creates  => '/var/log/php-fpm-www-error.log',
  user    => root,
  require  => Class['php::fpm']
}
