
$pmc_themes = ['pmc-plugins', 'pmc-variety', 'pmc-tvline', 'pmc-hollywoodlife', 'pmc-awardsline', 'pmc-411']
$pmc_sites = [
	{ slug => 'variety',       theme => 'pmc-variety' },
	{ slug => 'tvline',        theme => 'pmc-tvline' },
	{ slug => 'awardsline',    theme => 'pmc-awardsline' },
	{ slug => 'variety411',    theme => 'pmc-411' },
	{ slug => 'hollywoodlife', theme => 'pmc-hollywoodlife' },
]

define pmc::clone-theme {
	exec { "clone-theme $name":
		command => "/usr/bin/git clone git@bitbucket.org:penskemediacorp/$name.git /vagrant/www/wp-content/themes/vip/$name",
		user => "vagrant",
		unless  => "/usr/bin/test -d /vagrant/www/wp-content/themes/vip/$name"
	}
}

define pmc::setup-site {
	$slug = $name['slug']
	$theme = $name['theme']
	exec {
		"create-site $slug":
		command => "/usr/bin/wp --path=/vagrant/www/wp site create --slug=$slug --title=$slug --email=admin@vip.dev",
		unless  => "/usr/bin/wp --path=/vagrant/www/wp site list | grep $slug -q",
		require => [ Exec['wp install /vagrant/www/wp'], Exec["clone-theme $theme"] ]
	}
	exec {
		"activate-theme $theme":
		command => "/usr/bin/wp --path=/vagrant/www/wp --url=vip.dev/$slug theme activate vip/$theme",
		onlyif  => "/usr/bin/wp --path=/vagrant/www/wp --url=vip.dev/$slug/ theme status | grep 'I vip/$theme'",
		require => Exec["clone-theme $theme"],
	}
}
		
exec {
	'bitbucket-key':
	command => '/usr/bin/test -f /home/vagrant/.ssh/bitbucket.org_id_rsa',
	unless  => '/usr/bin/test -f /home/vagrant/.ssh/bitbucket.org_id_rsa'
}

pmc::clone-theme { 
	$pmc_themes:
	require => [ Exec['checkout plugins'], Exec['bitbucket-key'] ]
}

pmc::setup-site { 
	$pmc_sites: 
	require => [ Exec['checkout plugins'], Exec['wp install /vagrant/www/wp'], Exec['bitbucket-key'] ]
}


