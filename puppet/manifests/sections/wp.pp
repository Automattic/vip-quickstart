class { wp::cli:
	ensure => installed,
	install_path => '/vagrant/www/wp-cli'
}
