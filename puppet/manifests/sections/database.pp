class { "mysql":
	root_password => 'blank',
}

mysql::grant { 'wordpress':
	mysql_privileges => 'ALL',
	mysql_password   => 'wordpress',
	mysql_db         => 'wordpress',
	mysql_user       => 'wordpress',
	mysql_host       => 'localhost',
}
