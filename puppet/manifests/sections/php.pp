# PHP 5.4 + extensions
include php
include apt

apt::source { 'php54':
  location    => 'http://ppa.launchpad.net/ondrej/php5-oldstable/ubuntu',
  release     => 'precise',
  repos       => 'main',
  key         => '14aa40ec0831756756d7f66c4f4ea0aae5267a6c',
  key_server  => 'hkp://keyserver.ubuntu.com:80',
  include_src => true
}

class {
  'php::cli':
    ensure  => latest,
    require => Apt::Source['php54'];
  'php::composer':;
  'php::dev':
    ensure  => latest,
    require => Apt::Source['php54'];
  'php::fpm':;
  'php::pear':;
  'php::phpunit':;

  # Extensions
  'php::extension::apc':
    ensure => absent;
  'php::extension::curl':;
  'php::extension::gd':;
  'php::extension::imagick':;
  'php::extension::mcrypt':;
  'php::extension::memcache':;
  'php::extension::mysql':;
  'php::extension::xdebug':;
}

# Install PHP_CodeSniffer and the WordPress coding standard
package { 'pear.php.net/PHP_CodeSniffer':
  ensure   => 'installed',
  provider => 'pear',
}

vcsrepo { '/usr/share/php/PHP/CodeSniffer/Standards/WordPress':
  source   => 'https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards',
  provider => 'git',
  ensure   => 'present',
  require  => Package['pear.php.net/PHP_CodeSniffer'],
}

php::fpm::conf { 'www': user => 'vagrant' }

file { '/etc/php5/conf.d/apc.ini': ensure => absent }

# Turn on html_errors
exec { 'html_errors = On':
  command => 'sed -i "s/html_errors = Off/html_errors = On/g" /etc/php5/fpm/php.ini',
  unless  => 'cat /etc/php5/fpm/php.ini | grep "html_errors = On"',
  user    => root,
  notify  => Service['php5-fpm']
}

# Xdebug Remote Configuration
class php::extension::xdebug::params {
  $ensure      = $php::params::ensure
  $package     = 'php5-xdebug'
  $provider    = undef
  $inifile     = '/etc/php5/conf.d/xdebug.ini'
  $settings = {
    set => {
      '.anon/xdebug.collect_includes' => 1,
      '.anon/xdebug.collect_params' => 1,
      '.anon/xdebug.dump_globals' => 1,
      '.anon/xdebug.idekey' => "VIPDEBUG",
      '.anon/xdebug.profiler_enable_trigger' => 1,
      '.anon/xdebug.profiler_output_name' => "cachegrind.out.%t-%s",
      '.anon/xdebug.remote_host' => "${host_ip_address}",
      '.anon/xdebug.remote_autostart' => 1,
      '.anon/xdebug.remote_enable' => 1,
      '.anon/xdebug.remote_log' => "/tmp/xdebug-remote.log",
      '.anon/xdebug.remote_port' => 9000,
      '.anon/xdebug.var_display_max_children' => -1,
      '.anon/xdebug.var_display_max_data' => -1,
      '.anon/xdebug.var_display_max_depth' => -1,
    }
  }
}

# Turn Xdebug Off
exec { "turn-xdebug-off":
  command  => "php5dismod xdebug"
}
exec { "restart-php5-fpm":
  command  => "service php5-fpm restart"
}