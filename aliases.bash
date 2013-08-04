run() {
	vagrant ssh -c "$argv"
}

wp() {
	run "cd /vagrant/www/wp; /usr/bin/wp $argv"
}

log() {
	run "tail /var/log/nginx/wp.dev.error.log"
}
