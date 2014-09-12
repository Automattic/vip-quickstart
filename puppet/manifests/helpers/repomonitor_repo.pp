# Adds a version control repo to the dashboard RepoMonitor plugin so that users are notified when it is out of date.
define repomonitor_repo ( $repo_name, $path = $title, $type = '' ) {
  if ( $type ) {
    $command = "/usr/bin/wp quickstart add_repo '${repo_name}' ${path} --type=${type}"
  } else {
    $command = "/usr/bin/wp quickstart add_repo '${repo_name}' ${path}"
  }

  exec { "Setup repomonitor ${title}":
    command   => $command,
    unless    => "/usr/bin/wp quickstart list_repos | /bin/grep '${path}$'",
    require   => [
      Class['wp::cli'],
      Exec['wp install /srv/www/wp']
    ],
    user => 'vagrant',
  }
}
