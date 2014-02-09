$tools = [
    'vim',
    'ack-grep',
]

package { $tools: }

# Install ack as ack
file { '/usr/local/bin/ack':
    ensure  => 'link',
    target  => '/usr/bin/ack-grep',
    require => Package['ack-grep'],
}
