define gitplugin {
    vcsrepo { "/srv/www/wp-content/plugins/${title}" :
        ensure   => 'present',
        source   => $github_plugins[$title],
        provider => git,
        require => [
            Exec['wp install /srv/www/wp'],
            File['/srv/www/wp-content/plugins'],
        ]
    }

    wp::plugin { $title :
        location    => '/srv/www/wp',
        networkwide => true,
        require => Vcsrepo["/srv/www/wp-content/plugins/${title}"]
    }
}

