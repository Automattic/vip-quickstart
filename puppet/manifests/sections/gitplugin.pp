# GitPlugin clones a git repo into the www/wp-content/plugins directory and activates it in WordPress
define gitplugin ( $git_urls ) {
    vcsrepo { "/srv/www/wp-content/plugins/${title}" :
        ensure   => latest,
        source   => $git_urls[$title],
        provider => git,
        require  => [
            Exec['wp install /srv/www/wp'],
            File['/srv/www/wp-content/plugins'],
        ]
    }

    exec { "wp plugin activate ${title} --network":
        command => "/usr/bin/wp plugin activate ${title} --network",
        cwd     => '/srv/www/wp',
        unless  => "/usr/bin/wp plugin is-installed ${title}",
        onlyif  => '/usr/bin/wp core is-installed',
        require => [
            Class['wp::cli'],
            Vcsrepo["/srv/www/wp-content/plugins/${title}"],
        ],
    }
}
