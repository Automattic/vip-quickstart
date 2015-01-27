#!/bin/bash

while IFS=$',\n\r' read site_slug site_name site_theme
do
	[[ $site_slug = \#* ]] && continue

	if [ "" != "$1" ]; then
		[[ "$1" != "${site_slug}" ]] && continue
	fi
	
	bash /srv/pmc/db-import.sh "$site_slug" "/srv/pmc/sql/${site_slug}.sql"

done < /srv/pmc/sites
