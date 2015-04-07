apt::source { 'nodejs':
  ensure     => present,
  location   => 'https://deb.nodesource.com/node_0.10',
  key        => '9FD3B784BC1C6FC31A8A0A1C1655A0AB68576280',
} ->
package { 'nodejs':
  ensure => present,
} ~>
exec { 'npm install VIPSyncServer':
  command     => 'npm install',
  cwd         => '/srv/VIPSyncServer',
  refreshonly => true,
}