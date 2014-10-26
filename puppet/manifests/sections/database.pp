
class database::settings {
    $mysql_password = generate('/bin/sh', '-c', '/usr/bin/openssl rand -base64 64 | xargs echo -n')
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
  mysql_password   => $database::settings::mysql_password,
  mysql_db         => 'wptests',
  mysql_user       => 'wptests',
  mysql_host       => 'localhost',
}

