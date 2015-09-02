#!/bin/bash

DOMAIN='vip.local'
export HTTP_USER_AGENT="WP_CLI"
export HTTP_HOST="${DOMAIN}"

cd `dirname "$0"`
sudo usermod -a -G www-data vagrant

#####################
# Required packages #
#####################

# mcrypt
if [[ -z "`dpkg -s php5-mcrypt | grep "Status: install ok installed"`" ]]; then
	apt-get -y install php5-mcrypt mcrypt
	ln -s /etc/php5/mods-available/mcrypt.ini /etc/php5/cli/conf.d/20-mcrypt.ini
	ln -s /etc/php5/mods-available/mcrypt.ini /etc/php5/fpm/conf.d/20-mcrypt.ini
	service php5-fpm restart
fi;

# composer
if [ ! -f /usr/local/bin/composer ]; then
	curl -sS https://getcomposer.org/installer | php
	php composer.phar install
	if [ ! /usr/local/bin/composer ]; then
		mv composer.phar /usr/local/bin/composer
		chmod +x /usr/local/bin/composer
	fi
fi;

# nodejs
if [ -z "`which npm`" ]; then
	curl -sL https://deb.nodesource.com/setup | sudo bash -
	sudo apt-get install -y nodejs
fi

# mobify client
if [ -z "`mobify`" ]; then
	sudo npm -g install mobify-client
fi

# compass
if [ -z "`which compass`" ]; then
	sudo apt-get install rubygems -y
	sudo apt-get install ruby -y
	sudo gem install sass compass compass-rgbapng compass-photoshop-drop-shadow sassy-strings compass-import-once
fi

######################
# Wordpress Projects #
######################

# workaround pmc_analystics required this theme to be at this location.
if [ ! -e /srv/www/wp-content/themes/twentyfourteen ]; then
	ln -s /srv/www/wp-content/themes/pub/twentyfourteen/ /srv/www/wp-content/themes/twentyfourteen
fi

# eventbrite-venue theme required for pmc-conference
if [ ! -e /srv/www/wp-content/themes/eventbrite-venue ]; then
	svn co https://wpcom-themes.svn.automattic.com/eventbrite-venue/ /srv/www/wp-content/themes/eventbrite-venue
fi

if [ ! -f ~/.ssh/bitbucket.org_id_rsa ]; then
	bash /srv/pmc/bitbucket-gen-key.sh
	sudo chmod 600 ~/.ssh/bitbucket.org_id_rsa
fi

sed -e '$a\' -e "define('SUBDOMAIN_INSTALL', true );" -e "/define\s*(\s*'SUBDOMAIN_INSTALL'/d" -i /srv/www/local-config.php
sed -e '$a\' -e "define('AUTOMATIC_UPDATER_DISABLED', false );" -e "/define\s*(\s*'AUTOMATIC_UPDATER_DISABLED'/d" -i /srv/www/local-config.php
sed -e '$a\' -e "define('WP_CACHE_KEY_SALT', \$_SERVER['HTTP_HOST'] );" -e "/define\s*(\s*'WP_CACHE_KEY_SALT'/d" -i /srv/www/local-config.php

if [ "0" == "`/usr/bin/wp --path=/srv/www/wp network meta get 1 subdomain_install`" ]; then
	/usr/bin/wp --path=/srv/www/wp network meta update 1 subdomain_install 1
fi

sudo /usr/bin/wp --allow-root --path=/srv/www/wp --url=${DOMAIN} pmc-site set-domain ${DOMAIN}

if [ ! -d "/srv/www/wp-content/themes/vip/pmc-plugins" ]
then
	printf "\nDownloading pmc-plugins...\n"
	if [ ! -f /home/vagrant/.ssh/bitbucket.org_id_rsa ]
	then
		printf "\nSkipping this step, SSH key has not been created.\n"
	else
		git clone git@bitbucket.org:penskemediacorp/pmc-plugins.git /srv/www/wp-content/themes/vip/pmc-plugins
	fi
fi

while IFS=$',\n\r' read site_slug site_name site_theme
do
	[[ $site_slug = \#* ]] && continue

	if [ "" != "$1" ]; then
		[[ "$1" != "${site_slug}" ]] && continue
	fi

	repo=${site_theme}
	echo "Setting up site: ${site_slug}"

	if [ ! -d "/srv/www/wp-content/themes/vip/${site_theme}" ]
	then
		printf "\nDownloading $repo theme...\n"
		if [ ! -f /home/vagrant/.ssh/bitbucket.org_id_rsa.pub ]
		then
			printf "\nSkipping this step, SSH key has not been created.\n"
		else
			git clone git@bitbucket.org:penskemediacorp/${repo}.git /srv/www/wp-content/themes/vip/${site_theme}
		fi
	fi

	STATUS=`/usr/bin/wp --path=/srv/www/wp site list --fields=domain --format=csv | grep "${site_slug}.${DOMAIN}"`
	if [ "" == "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp site create --slug=${site_slug} --title=${site_name}
	fi

	STATUS=`/usr/bin/wp --path=/srv/www/wp --url=${site_slug}.${DOMAIN} theme status vip/${site_theme} | grep 'Status: Inactive'`
	if [ "" != "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp theme enable vip/${site_theme} --network
		/usr/bin/wp --path=/srv/www/wp --url=${site_slug}.${DOMAIN} theme activate vip/${site_theme}
	fi

done < ./sites

######################
# Additional plugins #
######################

if [ -d /srv/www/wp-content/plugins/pmc-theme-unit-test ]; then
	pushd /srv/www/wp-content/plugins/pmc-theme-unit-test && git pull && popd
else
	git clone https://github.com/Penske-Media-Corp/pmc-theme-unit-test.git /srv/www/wp-content/plugins/pmc-theme-unit-test
	/usr/bin/wp --path=/srv/www/wp plugin activate pmc-theme-unit-test --network
fi

###############
# Local hosts #
###############

cp -f /etc/hosts /srv/pmc/vagrant_hosts
sed -e "/#pmcsetup/d" -i /srv/pmc/hosts
sed -e "/#pmcsetup/d" -i /srv/pmc/vagrant_hosts

/usr/bin/wp --path=/srv/www/wp site list --fields=domain --format=csv | sed -e '/^domain$/d' -e 's/^\(.\+\)/[ip] \1 #pmcsetup\n[ip] live.\1 #pmcsetup/' > server_hosts

# custom non-vip site domain
echo '[ip] varietyarchive.local #pmcsetup
[ip] vscoreserver.local #pmcsetup
[ip] uls.vip.local #pmcsetup
[ip] dd-wwd.vip.local #pmcsetup
' >> server_hosts

sed -e 's/\[ip\]/10.86.73.80/' server_hosts >> /srv/pmc/hosts

# need this for wp cron to work
sed -e 's/\[ip\]/127.0.0.1/' server_hosts >> /srv/pmc/vagrant_hosts
sudo cp -f /srv/pmc/vagrant_hosts /etc/hosts

# ssl key and certificate
sudo cp /srv/pmc/dev-san-domain* /etc/ssl/

# nginx ssl
sudo sed -e "/http {/a\ \ ssl_certificate     /etc/ssl/dev-san-domain-chained.crt;\n\ \ ssl_certificate_key /etc/ssl/dev-san-domain.key;" -e '/ssl_certificate/d' -i /etc/nginx/nginx.conf
sudo sed -e "/listen 80;/a\ \ listen 443 ssl;" -e "/listen 443/d" -i /etc/nginx/sites-available/50-_.conf
sudo sed -e "/listen 80;/a\ \ listen 443 ssl;" -e "/listen 443/d" -i /etc/nginx/sites-enabled/50-_.conf

# php.ini default to display errors
sudo sed -e 's/^display_errors = Off/display_errors = On/g' -e  's/^display_startup_errors = Off/display_startup_errors = On/g' -i  /etc/php5/fpm/php.ini

# non vip sites nginx conf file
sudo cp /srv/pmc/nginx-sites.conf /etc/nginx/sites-enabled/sites.conf

#######################
# Non Wordpress Sites #
#######################

# uls.vip.local wwd paywall server
if [ ! -d /srv/www/htdocs/pmc-wwd-uls ]; then
	echo "Setting up uls.vip.local"
	git clone git@bitbucket.org:penskemediacorp/pmc-wwd-uls.git /srv/www/htdocs/pmc-wwd-uls
	cp /srv/pmc/uls.env.local.php /srv/www/htdocs/pmc-wwd-uls/.env.local.php
	cp /srv/pmc/uls.env.local.php /srv/www/htdocs/pmc-wwd-uls/.env.php
	mysql -uroot -e 'create database uls_wwd_local;'
	cd /srv/www/htdocs/pmc-wwd-uls
	composer install
	php artisan migrate --package=krafthaus/bauhaususer
	php artisan migrate
	# email password first last
	php artisan  bauhaus:user:register pmc@pmc.com pmc pmc pmc
fi

# dd-wwd.vip.local (wwd digital daily)
if [ ! -d /srv/www/htdocs/wwd-digital-daily ]; then
	git clone git@bitbucket.org:penskemediacorp/wwd-digital-daily.git /srv/www/htdocs/wwd-digital-daily
	cp /srv/pmc/wwd-digital-daily.env /srv/www/htdocs/wwd-digital-daily/.env
	cd /srv/www/htdocs/wwd-digital-daily/
	mysql -uroot -e 'create database wwd_digital_daily;'
	composer install
	php artisan migrate
	php artisan role:setup
	# email password name
	php artisan user:add pmc@pmc.com pmc pmc
	php artisan user:role pmc@pmc.com superadmin
fi

# wwd mobify
if [ ! -d /srv/www/htdocs/wwd-mobify ]; then
	git clone git@bitbucket.org:penskemediacorp/wwd-mobify.git /srv/www/htdocs/wwd-mobify
	cd /srv/www/htdocs/wwd-mobify
	mobify build
	sudo ln -fs /srv/www/htdocs/wwd-mobify/bld /srv/www/wp-content/themes/vip/pmc-wwd-2015/static/mobify
fi

# restart nginx
sudo service nginx reload
sudo service php5-fpm restart

echo "Site setup finished."
