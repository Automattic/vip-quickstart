$plugins = [
  'log-deprecated-notices',
  'monster-widget',
  'query-monitor',
  'user-switching',
  'wordpress-importer',

  # WordPress.com
  'keyring',
  'mrss',
  'polldaddy',
  'rewrite-rules-inspector',
]

$github_plugins = {
    'vip-scanner' => 'https://github.com/Automattic/vip-scanner',

    # WordPress.com
    'jetpack'        => 'https://github.com/Automattic/jetpack',
    'media-explorer' => 'https://github.com/Automattic/media-explorer',
    'writing-helper' => 'https://github.com/automattic/writing-helper',
}

include database::settings

# Delete broken plugins
file { '/srv/www/wp-content/plugins/log-viewer':
  ensure => 'absent',
  force  => true,
  before => Wp::Site['/srv/www/wp'],
}

# Install WordPress
wp::site { '/srv/www/wp':
  url             => $quickstart_domain,
  sitename        => $quickstart_domain,
  admin_user      => 'wordpress',
  admin_password => 'wordpress',
  network         => true,
  require         => [
    Vcsrepo['/srv/www/wp'],
    Line['path:/srv/www/wp'],
  ]
}

# Install GitHub Plugins
$github_plugin_keys = keys( $github_plugins )
gitplugin { $github_plugin_keys:
    git_urls => $github_plugins
}

# Install plugins
wp::plugin { $plugins:
  location    => '/srv/www/wp',
  networkwide => true,
  require     => [
    Wp::Site['/srv/www/wp'],
    File['/srv/www/wp-content/plugins'],
    Gitplugin[ $github_plugin_keys ],
  ]
}

# Update all the plugins
wp::command { 'plugin update --all':
  command  => 'plugin update --all',
  location => '/srv/www/wp',
  require  => Wp::Site['/srv/www/wp'],
}

# Symlink db.php for Query Monitor
file { '/srv/www/wp-content/db.php':
  ensure  => 'link',
  target  => 'plugins/query-monitor/wp-content/db.php',
  require => Wp::Plugin['query-monitor']
}

# Install WP-CLI
class { 'wp::cli': ensure  => installed }

# Make sure the wp-content directories exists
$wp_content_dirs = [
  '/srv/www/wp-content/themes',
  '/srv/www/wp-content/plugins',
  '/srv/www/wp-content/upgrade',
  '/srv/www/wp-content/uploads',
]

file { '/srv/www/wp-content':
    ensure  => directory,
    recurse => false,
    mode    => 0775,
    owner   => 'www-data',
    group   => 'www-data',
}


file { $wp_content_dirs:
    ensure  => directory,
    recurse => true,
    mode    => 0664,
    owner   => 'www-data',
    group   => 'www-data',
}

# VCS Checkout
vcsrepo { '/srv/www/wp':
  ensure   => latest,
  source   => 'https://core.svn.wordpress.org/trunk/',
  provider => svn,
}

cron { '/srv/www/wp':
  command => '/usr/bin/svn up /srv/www/wp > /dev/null 2>&1',
  minute  => '0',
  hour    => '*',
}

vcsrepo { '/srv/www/wp-content/themes/vip/plugins':
  ensure   => latest,
  source   => 'https://vip-svn.wordpress.com/plugins/',
  provider => svn,
}

cron { '/srv/www/wp-content/themes/vip/plugins':
  command => '/usr/bin/svn up /srv/www/wp-content/themes/vip/plugins > /dev/null 2>&1',
  minute  => '0',
  hour    => '*',
}

vcsrepo { '/srv/www/wp-content/themes/pub/twentyfifteen':
  ensure   => latest,
  source   => 'https://wpcom-themes.svn.automattic.com/twentyfifteen',
  provider => svn,
}

vcsrepo { '/srv/www/wp-tests':
  ensure   => latest,
  source   => 'https://develop.svn.wordpress.org/trunk/',
  provider => svn,
}

cron { '/srv/www/wp-tests':
  command => '/usr/bin/svn up /srv/www/wp-tests > /dev/null 2>&1',
  minute  => '0',
  hour    => '*',
}

if 'physical' == $::virtual {
  # Create a local config
  file { 'local-config.php':
    ensure => present,
    path   => '/srv/www/local-config.php',
    notify => Exec['SUBDOMAIN_INSTALL'],
  }

  exec { 'SUBDOMAIN_INSTALL':
    command     => 'echo "define(\'SUBDOMAIN_INSTALL\', true);" >> /srv/www/local-config.php',
    unless      => 'grep "SUBDOMAIN_INSTALL /srv/www/local-config.php',
    refreshonly => true,
    require     => Exec['local config header'],
  }
} else {
  # Create a local config
  file { 'local-config.php':
    ensure => present,
    path   => '/srv/www/local-config.php',
  }
}

$jetpack_dev_debug = $::virtual != 'physical'
file_line { 'JETPACK_DEV_DEBUG':
  line    => "define('JETPACK_DEV_DEBUG', ${jetpack_dev_debug});",
  path    => '/srv/www/local-config.php',
  match   => 'JETPACK_DEV_DEBUG',
  require => File['local-config.php'],
}

# Add default path to local WP-CLI config
line { 'path:/srv/www/wp':
  line => 'path:/srv/www/wp',
  file => '/srv/www/wp-cli.yml',
}

# Add default domain to local WP-CLI config
if ( $quickstart_domain ) {
  line { "url:${quickstart_domain}":
    line => "url:${quickstart_domain}",
    file => '/srv/www/wp-cli.yml',
  }
}

exec { 'local config header':
  command => 'printf "<?php\n" > /srv/www/local-config.php;',
  unless  => 'grep "<?php" /srv/www/local-config.php',
  require => File['local-config.php'],
}

exec { 'generate salts':
  command => 'curl https://api.wordpress.org/secret-key/1.1/salt/ >> /srv/www/local-config.php',
  unless  => 'grep "AUTH_KEY" /srv/www/local-config.php',
  require => [
    File['local-config.php'],
    Exec['local config header'],
  ]
}

# Add MySQL password created in database.pp to local config
file_line { 'Add DB_PASSWORD to local-config.php':
  line    => "define(\'DB_PASSWORD\', \'${database::settings::mysql_password}\');",
  path    => '/srv/www/local-config.php',
  match   => 'DB_PASSWORD',
  require => [
    File['local-config.php'],
    Exec['local config header'],
  ]
}
