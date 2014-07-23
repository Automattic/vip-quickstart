# Adds a version control repo to the dashboard RepoMonitor plugin so that users are notified when it is out of date.
define repomonitor_repo ( $repo_name, $path = $title ) {
  exec { "Setup repomonitor ${title}":
    command   => "/usr/bin/wp dashboard add_repo '${repo_name}' ${path} --autodetect --url=vip.dev --path=/srv/www/wp --allow-root",
    unless    => "/usr/bin/wp dashboard list_repos | /bin/grep '${path}$' --url=vip.dev --path=/srv/www/wp --allow-root",
    require   => [ Class['wp::cli'], Exec['wp install /srv/www/wp'] ],
  }
}
