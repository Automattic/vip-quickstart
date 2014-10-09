#!/bin/bash

cd `dirname "$0"`

if [ ! -f ~/.ssh/bitbucket.org_id_rsa ]; then
	./bitbucket-gen-key.sh
fi

sed -e '$a\' -e "define('SUBDOMAIN_INSTALL', true );" -e "/define\s*(\s*'SUBDOMAIN_INSTALL'/d" -i /srv/www/local-config.php 
if [ "0" == "`/usr/bin/wp --path=/srv/www/wp network meta get 1 subdomain_install`" ]; then
	/usr/bin/wp --path=/srv/www/wp network meta update 1 subdomain_install 1
fi

export HTTP_USER_AGENT="WP_CLI"
export HTTP_HOST="vip.dev"

if [ ! -d "/srv/www/wp-content/themes/vip/pmc-plugins" ]
then
	printf "\nDownloading pmc-plugins...\n"
	if [ ! -f /home/vagrant/.ssh/bitbucket.org_id_rsa.pub ]
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
	
	STATUS=`/usr/bin/wp --path=/srv/www/wp site list --fields=domain --format=csv | grep "${site_slug}.vip.dev"`
	if [ "" == "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp site create --slug=${site_slug} --title=${site_name}
	fi
	
	STATUS=`/usr/bin/wp --path=/srv/www/wp --url=variety.vip.dev theme status vip/pmc-variety-2014 | grep 'Status: Inactive'`
	if [ "" != "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp theme enable vip/${site_theme} --network
		/usr/bin/wp --path=/srv/www/wp --url=${site_slug}.vip.dev theme activate vip/${site_theme}
	fi
	
done < ./sites

HOSTS="`/usr/bin/wp --path=/srv/www/wp site list --fields=domain --format=csv | sed -e 's/^domain$//g' | tr '\n' ' '`"
sed -e '$a\' -e "10.86.73.80 ${HOSTS} # vip-quickstart pmc setup-sites.sh" -e "/# vip-quickstart pmc setup-sites.sh/d" -i /srv/pmc/hosts
