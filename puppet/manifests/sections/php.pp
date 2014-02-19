# Use PHP 5.4.24 like WordPress.com (except that 5.4.25 is what's available on oldstable)
$php_version = '5.4.25-1+sury.org~precise+2'

include php
include apt

apt::source { 'php54':
  location    => 'http://ppa.launchpad.net/ondrej/php5-oldstable/ubuntu',
  release     => 'precise',
  repos       => 'main',
  key         => '14aa40ec0831756756d7f66c4f4ea0aae5267a6c',
  key_server  => 'keyserver.ubuntu.com',
  include_src => true
}

class {
  'php::cli':
    ensure  => $php_version,
    require => Apt::Source['php54'];
  'php::composer':;
  'php::dev':
    ensure  => $php_version,
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
