# ClamAV
package { 'clamav': ensure => present }

exec { 'update clamav db':
  command => 'sudo freshclam',
  require => Package['clamav']
}
