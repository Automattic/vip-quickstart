# Make sure apt-get is up-to-date before we do anything else
stage { 'updates': before => Stage['main'] }
class { 'updates': stage => updates }
class updates {
    exec { 'apt-get update':
        command => 'apt-get update --quiet --yes',
        timeout => 0
    }
}
