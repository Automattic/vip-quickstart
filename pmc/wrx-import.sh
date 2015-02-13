#!/bin/bash
DOMAIN='vip.local'

if [ "" == "$1" ] || [ "" == "$2" ]
then
	echo 'syntax: wrx-import.sh <site-slug> <wrx-file>'
	exit
fi

site_slug=$1
wrx_file=$2

site_id=`/usr/bin/wp --path=/srv/www/wp site list --fields=blog_id,domain --format=csv|grep "${site_slug}.${DOMAIN}" | cut -d',' -f1`

if [ "" == "${site_id}" ]
then
	echo 'Site not found'
	exit
fi

for f in $wrx_file
do
	wp --path=/srv/www/wp --url=${site_slug}.${DOMAIN} --require=/srv/pmc/pmc-wp-cli.php import --authors=create $f
done
