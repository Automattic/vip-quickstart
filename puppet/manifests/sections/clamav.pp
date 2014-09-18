# ClamAV
package { 'clamav': ensure => present }

exec { 'update clamav db':
  command => 'sudo freshclam',
  timeout => 0,
  require => Package['clamav']
}
