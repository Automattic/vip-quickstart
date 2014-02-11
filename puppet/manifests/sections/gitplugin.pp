define gitplugin ( $slug = $title, $git_urls ) {
    vcsrepo { "/srv/www/wp-content/plugins/${title}" :
        ensure   => 'present',
        source   => $git_urls[$title],
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

