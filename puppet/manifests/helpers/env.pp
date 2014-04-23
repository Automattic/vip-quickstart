# env
define env($value) {
  line { $title:
    file => '/etc/environment',
    line => "${title}='${value}'",
  }
}
