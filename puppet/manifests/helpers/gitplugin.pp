# GitPlugin clones a git repo into the www/wp-content/plugins directory and activates it in WordPress
define gitplugin ( $git_urls ) {
    vcsrepo { "/srv/www/wp-content/plugins/${title}" :
        ensure   => present,
        force    => true,
        source   => $git_urls[$title],
        provider => git,
        require  => [
            Wp::Site['/srv/www/wp'],
            File['/srv/www/wp-content/plugins'],
        ]
    }

    wp::command { "plugin activate ${title}":
        command  => "plugin activate ${title} --network",
        location => '/srv/www/wp',
        require  => Vcsrepo["/srv/www/wp-content/plugins/${title}"],
    }
}
