#!/bin/bash

if [ "" == "$1" ] || [ "" == "$2" ]
then
	echo 'syntax: db-export.sh <site-slug> <sql-file>'
	exit
fi

site_slug=$1
sql_file=$2

site_id=`/usr/bin/wp --path=/srv/www/wp site list --fields=blog_id,domain --format=csv|grep "${site_slug}.local.dev" | cut -d',' -f1`

if [ "" != "${site_id}" ]
then
	echo "Exporting ${site_slug} site id ${site_id} to ${sql_file}"
	mysqldump -uroot wordpress --tables wp_${site_id}_commentmeta wp_${site_id}_comments wp_${site_id}_links wp_${site_id}_options wp_${site_id}_postmeta wp_${site_id}_posts wp_${site_id}_term_relationships wp_${site_id}_term_taxonomy wp_${site_id}_terms | sed -e "s/CREATE TABLE IF NOT EXISTS \`wp_${site_id}_/CREATE TABLE IF NOT EXISTS \`wp_/g" -e "s/ INTO \`wp_${site_id}_/ INTO \`wp_/g" -e "s/ TABLE \`wp_${site_id}_/ TABLE \`wp_/g" -e "s/table \`wp_${site_id}_/table \`wp_/g" -e "s/LOCK TABLES \`wp_${site_id}_/LOCK TABLES \`wp_/g" -e "s/DROP TABLE IF EXISTS \`wp_${site_id}_/DROP TABLE IF EXISTS \`wp_/g" > ${sql_file} 
else
	echo 'Site not found'
fi
