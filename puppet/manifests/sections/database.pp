
class database::settings {
    $mysql_password = generate('/bin/sh', '-c', "< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c\${1:-64};echo -n;")
}

include database::settings

mysql::grant { 'wordpress':
  mysql_privileges => 'ALL',
  mysql_password   => $database::settings::mysql_password,
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

mysql::augeas {
  'mysqld/log_slow_queries':
    value => '/var/log/mysql/mysql-slow.log',
    require => Package['mysql'];
}
