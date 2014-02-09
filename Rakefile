require 'puppet-lint/tasks/puppet-lint'

# Ignore modules, they should test themselves
PuppetLint.configuration.ignore_paths = ["puppet/modules/**/*.pp"]

# Disable 80 chars check for now
PuppetLint.configuration.send("disable_80chars")
PuppetLint.configuration.send("disable_autoloader_layout")

task :default => [:lint]
