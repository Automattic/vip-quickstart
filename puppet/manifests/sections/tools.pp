$tools = [
    'ack-grep',
    'vim',
    'unzip',
    'zip',
    'autojump',
]

package { $tools: }

# Install ack as ack
file { '/usr/local/bin/ack':
    ensure  => 'link',
    target  => '/usr/bin/ack-grep',
    require => Package['ack-grep'],
}

# Install Autojump as `j`
file { '/usr/local/bin/j':
    ensure  => 'link',
    target  => '/usr/bin/autojump',
    require => Package['autojump'],
}