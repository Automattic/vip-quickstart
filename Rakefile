require 'puppet-lint/tasks/puppet-lint'

# Ignore modules, they should test themselves
PuppetLint.configuration.ignore_paths = ["puppet/modules/**/*.pp"]

task :default => [:lint]
