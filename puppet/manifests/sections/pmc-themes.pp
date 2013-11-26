$pmc_themes = ['pmc-plugins']

$pmc_sites = [
	{ slug => 'variety',       theme => 'pmc-variety' },
	{ slug => 'tvline',        theme => 'pmc-tvline' },
	{ slug => 'awardsline',    theme => 'pmc-awardsline' },
	{ slug => 'movieline',     theme => 'pmc-movieline' },
	{ slug => 'variety411',    theme => 'pmc-411' },
	{ slug => 'hollywoodlife', theme => 'pmc-hollywoodlife' },
]

define pmc::clone-theme {
	exec { "clone-theme $name":
		command => "/usr/bin/git clone git@bitbucket.org:penskemediacorp/$name.git /srv/www/wp-content/themes/vip/$name",
		user => "vagrant",
		unless  => "/usr/bin/test -d /srv/www/wp-content/themes/vip/$name",
		onlyif => "/usr/bin/test -f /home/srv/.ssh/bitbucket.org_id_rsa",
	}
}

define pmc::setup-site {
	$slug = $name['slug']
	$theme = $name['theme']
	pmc::clone-theme { 
		$theme:
	}
	exec {
		"create-site $slug":
		command => "/usr/bin/wp --path=/srv/www/wp site create --slug=$slug --title=$slug --email=admin@vip.dev",
		unless  => "/usr/bin/wp --path=/srv/www/wp site list | grep $slug -q",
		require => [ Exec['wp install /srv/www/wp'], Exec["clone-theme $theme"] ]
	}
	exec {
		"activate-theme $theme":
		command => "/usr/bin/wp --path=/srv/www/wp --url=vip.dev/$slug theme activate vip/$theme",
		onlyif  => "/usr/bin/wp --path=/srv/www/wp --url=vip.dev/$slug/ theme status | grep 'I vip/$theme'",
		require => Exec["create-site $slug"]
	}
}
		
pmc::clone-theme { 
	$pmc_themes:
	require => Exec['checkout plugins']
}

pmc::setup-site { 
	$pmc_sites: 
	require => [ Exec['checkout plugins'], Exec['wp install /srv/www/wp'] ]
}


