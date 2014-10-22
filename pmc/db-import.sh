#!/bin/bash

if [ "" == "$1" ] || [ "" == "$2" ]
then
	echo 'syntax: db-import.sh <site-slug> <sql-file>'
	exit
fi

site_slug=$1
sql_file=$2

if [ ! -f "${sql_file}" ]; then
	echo "File not found: ${sql_file}"
	exit
fi

site_id=`/usr/bin/wp --path=/srv/www/wp site list --fields=blog_id,domain --format=csv|grep "${site_slug}.local.dev" | cut -d',' -f1`

if [ "" != "${site_id}" ]
then
	echo "Importing ${sql_file} into ${site_slug} site id ${site_id}"
	sed -e "s/CREATE TABLE \`wp_/CREATE TABLE \`wp_${site_id}_/g" -e "s/DROP TABLE IF EXISTS \`wp_/DROP TABLE IF EXISTS \`wp_${site_id}_/g" ${sql_file} | mysql -uroot wordpress
	/usr/bin/wp --path=/srv/www/wp --require=/srv/pmc/pmc-wp-cli.php  pmc-site fix "${site_slug}.local.dev" --title="${site_slug}"
else
	echo 'Site not found'
fi
