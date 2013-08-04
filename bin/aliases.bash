run() {
	vagrant ssh -c "$argv"
}

wp() {
	run "cd /vagrant/www/wp; /usr/bin/wp $argv"
}

log() {
	run "tail /var/log/nginx/wp.dev.error.log"
}

wpup() {
	# svn up plugins directory
	\cd www/wp-content/themes/vip/plugins
	svn up
	\cd -
}
