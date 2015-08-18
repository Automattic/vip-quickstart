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

sudo /usr/bin/wp --path=/srv/www/wp core multisite-install --subdomains --url=qa.pmc.com --title='PMC QA' --admin_email='dist.dev@pmc.com' --admin_name=wordpress --admin_password=wordpress

sudo sed -e '$a\' -e "define('SUBDOMAIN_INSTALL', true );" -e "/define\s*(\s*'SUBDOMAIN_INSTALL'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('WP_ALLOW_MULTISITE', true );" -e "/define\s*(\s*'WP_ALLOW_MULTISITE'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('DOMAIN_CURRENT_SITE', 'qa.pmc.com' );" -e "/define\s*(\s*'DOMAIN_CURRENT_SITE'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "define('WP_CACHE_KEY_SALT', \$_SERVER['HTTP_HOST'] );" -e "/define\s*(\s*'WP_CACHE_KEY_SALT'/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "require_once( __DIR__ . '/wp-content/mu-plugins/pmc-branch-switch-config.php');" -e "/require.*?\/pmc-branch-switch-config/d" -i /srv/www/local-config.php
sudo sed -e '$a\' -e "\$base = '/';" -e "/\$base = /d" -i /srv/www/local-config.php
