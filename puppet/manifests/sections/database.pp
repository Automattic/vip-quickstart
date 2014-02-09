mysql::grant { 'wordpress':
  mysql_privileges => 'ALL',
  mysql_password   => 'wordpress',
  mysql_db         => 'wordpress',
  mysql_user       => 'wordpress',
  mysql_host       => 'localhost',
}

mysql::grant { 'wptests':
  mysql_privileges => 'ALL',
  mysql_password   => 'wptests',
  mysql_db         => 'wptests',
  mysql_user       => 'wptests',
  mysql_host       => 'localhost',
}

package { 'phpmyadmin':
  ensure  => present,
  require => Package['nginx']
}
