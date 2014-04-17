# Adds a version control repo to the dashboard RepoMonitor plugin so that users are notified when it is out of date.
define repomonitor_repo ( $path = $title, $repo_name ) {
	exec { "Setup repomonitor ${title}":
		command => "/usr/bin/wp dashboard add_repo \"${repo_name}\" $path --autodetect",
		cwd		=> '/srv/www/wp-content',
		unless  => '/usr/bin/wp dashboard list_repos | grep "$path"',
		require => [
			Exec['wp install /srv/www/wp'],
		]
	}
}
