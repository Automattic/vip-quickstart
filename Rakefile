require 'puppet-lint/tasks/puppet-lint'

# Ignore modules, they should test themselves
PuppetLint.configuration.ignore_paths = ["puppet/modules/**/*.pp"]

# Disable 80 chars check
PuppetLint.configuration.send("disable_80chars")
PuppetLint.configuration.send("disable_autoloader_layout")

# Disable documentation check
PuppetLint.configuration.send('disable_documentation')

task :default => [:lint]
