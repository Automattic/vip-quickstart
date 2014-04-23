if 'virtualbox' != $virtual {
    # SSH
    include ssh::server

    # UFW
    include ufw
    ufw::limit { 22: }
    ufw::allow { 'allow-all-http':
        port => 80,
        ip   => 'any',
    }
    ufw::allow { 'allow-dns-over-udp':
        port  => 53,
        proto => 'udp',
    }
}
