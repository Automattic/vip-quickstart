#!/bin/bash

cd `dirname "$0"`
./bitbucket-gen-key.sh

export HTTP_USER_AGENT="WP_CLI"
export HTTP_HOST="vip.dev"

while IFS=$',\n\r' read site_slug site_name site_theme
do           
	[[ $site_slug = \#* ]] && continue
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
	
	STATUS=`/usr/bin/wp --path=/srv/www/wp site list | grep /${site_slug}/`
	if [ "" == "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp site create --slug=${site_slug} --title=${site_name}
	fi
	
	STATUS=`/usr/bin/wp --path=/srv/www/wp --url=vip.dev/${site_slug} theme status | grep "A vip/${site_theme} "`
	if [ "" == "${STATUS}" ]; then
		/usr/bin/wp --path=/srv/www/wp --url=vip.dev/${site_slug} theme activate vip/${site_theme}
	fi
	
done < ./sites
