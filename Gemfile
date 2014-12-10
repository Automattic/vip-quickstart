source 'https://rubygems.org'

if ENV.key?('PUPPET_VERSION')
    puppetversion = "= #{ENV['PUPPET_VERSION']}"
else
    puppetversion = ['>= 2.7']
end

gem 'rake'
gem 'puppet-lint', '=1.0.1'
gem 'rspec-puppet'
gem 'puppet', puppetversion
