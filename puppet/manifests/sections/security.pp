include ufw

ufw::limit { 22: }

ufw::allow { "allow-all-http":
    port => 80
}

ufw::allow { "allow-dns-over-udp":
    port => 53,
    proto => "udp",
}
