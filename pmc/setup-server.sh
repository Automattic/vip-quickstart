#!/bin/bash
sudo mkdir /srv/www/wp-content-sites
sudo chown www-data:www-data /srv/www/wp-content-sites
sudo mkdir /home/www-data
sudo chown www-data:www-data /home/www-data
sudo service nginx stop
sudo service php5-fpm stop
sudo usermod -d /home/www-data www-data
sudo service php5-fpm start
sudo service nginx start

/usr/bin/wp --path=/srv/www/wp core multisite-install --subdomains --url=qa.pmc.com --title='PMC QA' --admin_email='dist.dev@pmc.com' --admin_name=wordpress --admin_password=wordpress

################
# Local config #
################

sudo sed -e '$a\' -e "if ( empty( \$_SERVER['HTTP_HOST'] ) ) { \$_SERVER['HTTP_HOST'] = 'qa.pmc.com'; }" -e "/if ( empty( \$_SERVER['HTTP_HOST'] ) )/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "if ( empty( \$_SERVER['REQUEST_URI'] ) ) { \$_SERVER['REQUEST_URI'] = '/'; }" -e "/if ( empty( \$_SERVER['REQUEST_URI'] ) )/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('SUBDOMAIN_INSTALL', true );" -e "/define\s*(\s*'SUBDOMAIN_INSTALL'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('WP_ALLOW_MULTISITE', true );" -e "/define\s*(\s*'WP_ALLOW_MULTISITE'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('DOMAIN_CURRENT_SITE', 'qa.pmc.com' );" -e "/define\s*(\s*'DOMAIN_CURRENT_SITE'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('WP_CACHE_KEY_SALT', \$_SERVER['HTTP_HOST'] );" -e "/define\s*(\s*'WP_CACHE_KEY_SALT'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "require_once( __DIR__ . '/wp-content/mu-plugins/pmc-branch-switch-config.php');" -e "/require_once.*\/pmc-branch-switch-config/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "\$base = '/';" -e "/\$base = /d" -i /srv/www/local-config.php

#############
# nginx ssl #
#############
if [ -f /etc/ssl/qa-san-domain/qa-san-domain-chained.crt ]; then
	sudo sed -e "/http {/a\ \ ssl_certificate     /etc/ssl/qa-san-domain/qa-san-domain-chained.crt;\n\ \ ssl_certificate_key /etc/ssl/qa-san-domain/qa-san-domain.key;" -e '/ssl_certificate/d' -i /etc/nginx/nginx.conf
	sudo sed -e "/listen 80;/a\ \ listen 443 ssl;" -e "/listen 443/d" -i /etc/nginx/sites-available/50-_.conf
	sudo sed -e "/listen 80;/a\ \ listen 443 ssl;" -e "/listen 443/d" -i /etc/nginx/sites-enabled/50-_.conf
fi

######################
# Additional plugins #
######################

wget https://downloads.wordpress.org/plugin/post-meta-inspector.1.1.1.zip /tmp/post-meta-inspector.1.1.1.zip
sudo unzip /tmp/post-meta-inspector.1.1.1.zip -d /srv/www/wp-content/plugins/
/usr/bin/wp --path=/srv/www/wp plugin activate post-meta-inspector --network

if [ -d /srv/www/wp-content/plugins/pmc-theme-unit-test ]; then
	pushd /srv/www/wp-content/plugins/pmc-theme-unit-test && git pull && popd
else
	git clone https://github.com/Penske-Media-Corp/pmc-theme-unit-test.git /srv/www/wp-content/plugins/pmc-theme-unit-test
	/usr/bin/wp --path=/srv/www/wp plugin activate pmc-theme-unit-test --network
fi
