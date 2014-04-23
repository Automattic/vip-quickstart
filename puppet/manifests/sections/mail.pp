# Postfix
package { 'postfix': ensure => present }
service { 'postfix': ensure => running }
exec { 'configure postfix hostname':
    command => "sed -i 's/precise32/${quickstart_domain}/g' /etc/postfix/main.cf",
    onlyif  => 'cat /etc/postfix/main.cf | grep "precise32"',
    user    => root,
    notify  => Service['postfix']
}
