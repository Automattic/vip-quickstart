stage { 'pmc': }
Stage['main'] -> Stage['pmc']
class {'pmc': stage => pmc }

class pmc {
	exec {
		"pmc-setup-sites":
		command => "/bin/bash /srv/pmc/setup-sites.sh",
		cwd     => '/srv/pmc/',
		user    => 'vagrant',
		logoutput => "on_failure",
		require => Exec['wp install /srv/www/wp']
	}
}
